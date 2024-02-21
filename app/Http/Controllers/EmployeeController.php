<?php

namespace App\Http\Controllers;

use App\DataTables\EmployeesDataTable;
use App\DataTables\LeaveDataTable;
use App\DataTables\ProjectsDataTable;
use App\DataTables\TasksDataTable;
use App\DataTables\TicketDataTable;
use App\DataTables\TimeLogsDataTable;
use App\Enums\Salutation;
use App\Helper\Files;
use App\Helper\Reply;
use App\Http\Requests\Admin\Employee\ImportProcessRequest;
use App\Http\Requests\Admin\Employee\ImportRequest;
use App\Http\Requests\Admin\Employee\StoreRequest;
use App\Http\Requests\Admin\Employee\UpdateRequest;
use App\Http\Requests\User\CreateInviteLinkRequest;
use App\Http\Requests\User\InviteEmailRequest;
use App\Imports\EmployeeImport;
use App\Jobs\ImportEmployeeJob;
use App\Models\Appreciation;
use App\Models\Attendance;
use App\Models\Designation;
use App\Models\EmployeeDetails;
use App\Models\EmployeeSkill;
use App\Models\LanguageSetting;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Module;
use App\Models\Notification;
use App\Models\Passport;
use App\Models\ProjectTimeLog;
use App\Models\ProjectTimeLogBreak;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\Skill;
use App\Models\Task;
use App\Models\TaskboardColumn;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\UniversalSearch;
use App\Models\User;
use App\Models\UserActivity;
use App\Models\UserInvitation;
use App\Models\VisaDetail;
use App\Scopes\ActiveScope;
use App\Traits\ImportExcel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Mailer\Exception\TransportException;

class EmployeeController extends AccountBaseController
{
    use ImportExcel;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.employees';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('employees', $this->user->modules));

            return $next($request);
        });
    }

    /**
     * @param EmployeesDataTable $dataTable
     * @return mixed|void
     */
    public function index(EmployeesDataTable $dataTable)
    {
        $viewPermission = user()->permission('view_employees');

        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        if (!request()->ajax()) {
            $this->employees = User::allEmployees();
            $this->skills = Skill::all();
            $this->departments = Team::all();
            $this->designations = Designation::allDesignations();
            $this->totalEmployees = count($this->employees);
            $this->roles = Role::where('name', '<>', 'client')
                ->orderBy('id')->get();
        }

        return $dataTable->render('employees.index', $this->data);
    }

    /**
     * XXXXXXXXXXX
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->pageTitle = __('app.addEmployee');

        $addPermission = user()->permission('add_employees');
        abort_403(!in_array($addPermission, ['all', 'added']));


        $this->teams = Team::all();
        $this->designations = Designation::allDesignations();

        $this->skills = Skill::all()->pluck('name')->toArray();
        $this->countries = countries();
        $this->lastEmployeeID = EmployeeDetails::count();
        $this->checkifExistEmployeeId = EmployeeDetails::select('id')->where('employee_id', ($this->lastEmployeeID + 1))->first();
        $this->employees = User::allEmployees(null, true);
        $this->languages = LanguageSetting::where('status', 'enabled')->get();
        $this->salutations = Salutation::cases();

        $userRoles = user()->roles->pluck('name')->toArray();

        if(in_array('admin', $userRoles))
        {
            $this->roles = Role::where('name', '<>', 'client')->get();
        }
        else
        {
            $this->roles = Role::whereNotIn('name', ['admin', 'client'])->get();
        }

        $employee = new EmployeeDetails();

        if ($employee->getCustomFieldGroupsWithFields()) {
            $this->fields = $employee->getCustomFieldGroupsWithFields()->fields;
        }

        $this->view = 'employees.ajax.create';

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('employees.create', $this->data);

    }

    public function assignRole(Request $request)
    {
        $changeEmployeeRolePermission = user()->permission('change_employee_role');

        abort_403($changeEmployeeRolePermission != 'all');

        $userId = $request->userId;
        $roleId = $request->role;
        $employeeRole = Role::where('name', 'employee')->first();

        $user = User::withoutGlobalScope(ActiveScope::class)->findOrFail($userId);

        RoleUser::where('user_id', $user->id)->delete();
        $user->roles()->attach($employeeRole->id);

        if ($employeeRole->id != $roleId) {
            $user->roles()->attach($roleId);
        }

        $user->assignUserRolePermission($roleId);

        $userSession = new AppSettingController();
        $userSession->deleteSessions([$user->id]);

        return Reply::success(__('messages.roleAssigned'));
    }

    /**
     * @param StoreRequest $request
     * @return array
     * @throws \Froiden\RestAPI\Exceptions\RelatedResourceNotFoundException
     */
    public function store(StoreRequest $request)
    {
        $addPermission = user()->permission('add_employees');
        abort_403(!in_array($addPermission, ['all', 'added']));

        DB::beginTransaction();
        try {
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->mobile = $request->mobile;
            $user->country_id = $request->country;
            $user->salutation = $request->salutation;
            $user->country_phonecode = $request->country_phonecode;
            $user->gender = $request->gender;
            $user->locale = $request->locale;

            if ($request->has('login')) {
                $user->login = $request->login;
            }

            if ($request->has('email_notifications')) {
                $user->email_notifications = $request->email_notifications ? 1 : 0;
            }

            if ($request->hasFile('image')) {
                Files::deleteFile($user->image, 'avatar');
                $user->image = Files::uploadLocalOrS3($request->image, 'avatar', 300);
            }

            if ($request->has('telegram_user_id')) {
                $user->telegram_user_id = $request->telegram_user_id;
            }

            $user->save();

            $tags = json_decode($request->tags);

            if (!empty($tags)) {
                foreach ($tags as $tag) {
                    // check or store skills
                    $skillData = Skill::firstOrCreate(['name' => $tag->value]);

                    // Store user skills
                    $skill = new EmployeeSkill();
                    $skill->user_id = $user->id;
                    $skill->skill_id = $skillData->id;
                    $skill->save();
                }
            }

            if ($user->id) {
                $employee = new EmployeeDetails();
                $employee->user_id = $user->id;
                $this->employeeData($request, $employee);
                $employee->save();

                // To add custom fields data
                if ($request->custom_fields_data) {
                    $employee->updateCustomFieldData($request->custom_fields_data);
                }
            }

            $employeeRole = Role::where('name', 'employee')->first();
            $user->attachRole($employeeRole);

            if ($employeeRole->id != $request->role) {
                $otherRole = Role::where('id', $request->role)->first();
                $user->attachRole($otherRole);
            }

            $user->assignUserRolePermission($request->role);
            $this->logSearchEntry($user->id, $user->name, 'employees.show', 'employee');

            // Commit Transaction
            DB::commit();

        } catch (TransportException $e) {
            // Rollback Transaction
            DB::rollback();

            return Reply::error('Please configure SMTP details to add employee. Visit Settings -> notification setting to set smtp '.$e->getMessage(), 'smtp_error');
        } catch (\Exception $e) {
            logger($e->getMessage());
            // Rollback Transaction
            DB::rollback();

            return Reply::error('Some error occurred when inserting the data. Please try again or contact support '. $e->getMessage());
        }


        if (request()->add_more == 'true') {
            $html = $this->create();

            return Reply::successWithData(__('messages.recordSaved'), ['html' => $html, 'add_more' => true]);
        }

        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('employees.index')]);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function applyQuickAction(Request $request)
    {
        switch ($request->action_type) {
        case 'delete':
            $this->deleteRecords($request);

            return Reply::success(__('messages.deleteSuccess'));
        case 'change-status':
            $this->changeStatus($request);

            return Reply::success(__('messages.updateSuccess'));
        default:
            return Reply::error(__('messages.selectAction'));
        }
    }

    private function deleteEmployee(User $user)
    {

        $universalSearches = UniversalSearch::where('searchable_id', $user->id)->where('module_type', 'employee')->get();

        if ($universalSearches) {
            foreach ($universalSearches as $universalSearch) {
                UniversalSearch::destroy($universalSearch->id);
            }
        }


        Notification::whereNull('read_at')
            ->where(function ($q) use ($user) {
                $q->where('data', 'like', '{"id":' . $user->id . ',%');
                $q->orWhere('data', 'like', '%,"name":' . $user->name . ',%');
                $q->orWhere('data', 'like', '%,"user_one":' . $user->id . ',%');
                $q->orWhere('data', 'like', '%,"user_id":' . $user->id . ',%');
            })->delete();

        $deleteSession = new AppSettingController();
        $deleteSession->deleteSessions([$user->id]);
        $user->delete();

    }

    protected function deleteRecords($request)
    {
        abort_403(user()->permission('delete_employees') != 'all');

        $users = User::withoutGlobalScope(ActiveScope::class)->whereIn('id', explode(',', $request->row_ids))->get();

        $users->each(function ($user) {
            $this->deleteEmployee($user);
        });
    }

    protected function changeStatus($request)
    {
        abort_403(user()->permission('edit_employees') != 'all');

        User::withoutGlobalScope(ActiveScope::class)->whereIn('id', explode(',', $request->row_ids))->update(['status' => $request->status]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $this->employee = User::withoutGlobalScope(ActiveScope::class)->with('employeeDetail', 'reportingTeam')->findOrFail($id);

        $this->editPermission = user()->permission('edit_employees');

        $userRoles = $this->employee->roles->pluck('name')->toArray();

        abort_403(!in_array('admin', user_roles()) && in_array('admin', $userRoles));

        abort_403(!($this->editPermission == 'all'
            || ($this->editPermission == 'added' && $this->employee->employeeDetail->added_by == user()->id)
            || ($this->editPermission == 'owned' && $this->employee->id == user()->id)
            || ($this->editPermission == 'both' && ($this->employee->id == user()->id || $this->employee->employeeDetail->added_by == user()->id))
        ));

        $this->pageTitle = __('app.update') . ' ' . __('app.employee');
        $this->skills = Skill::all()->pluck('name')->toArray();
        $this->teams = Team::allDepartments();
        $this->designations = Designation::allDesignations();
        $this->countries = countries();
        $this->languages = LanguageSetting::where('status', 'enabled')->get();
        $exceptUsers = [$id];
        $this->roles = Role::where('name', '<>', 'client')->get();
        $this->userRoles = $this->employee->roles->pluck('name')->toArray();
        $this->salutations = Salutation::cases();

        /** @phpstan-ignore-next-line */
        if (count($this->employee->reportingTeam) > 0) {
            /** @phpstan-ignore-next-line */
            $exceptUsers = array_merge($this->employee->reportingTeam->pluck('user_id')->toArray(), $exceptUsers);
        }

        $this->employees = User::allEmployees($exceptUsers, true);

        if (!is_null($this->employee->employeeDetail)) {
            $this->employeeDetail = $this->employee->employeeDetail->withCustomFields();

            if ($this->employeeDetail->getCustomFieldGroupsWithFields()) {
                $this->fields = $this->employeeDetail->getCustomFieldGroupsWithFields()->fields;
            }
        }

        if (request()->ajax()) {
            $html = view('employees.ajax.edit', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'employees.ajax.edit';

        return view('employees.create', $this->data);

    }

    /**
     * @param UpdateRequest $request
     * @param int $id
     * @return array
     * @throws \Froiden\RestAPI\Exceptions\RelatedResourceNotFoundException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function update(UpdateRequest $request, $id)
    {

        $user = User::withoutGlobalScope(ActiveScope::class)->findOrFail($id);
        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->password != '') {
            $user->password = bcrypt($request->password);
        }

        $user->mobile = $request->mobile;
        $user->country_id = $request->country;
        $user->salutation = $request->salutation;
        $user->country_phonecode = $request->country_phonecode;
        $user->gender = $request->gender;
        $user->locale = $request->locale;

        if (request()->has('status')) {
            $user->status = $request->status;
        }

        if ($id != user()->id) {
            $user->login = $request->login;
        }

        if ($request->has('email_notifications')) {
            $user->email_notifications = $request->email_notifications;
        }

        if ($request->image_delete == 'yes') {
            Files::deleteFile($user->image, 'avatar');
            $user->image = null;
        }

        if ($request->hasFile('image')) {

            Files::deleteFile($user->image, 'avatar');
            $user->image = Files::uploadLocalOrS3($request->image, 'avatar', 300);
        }

        if ($request->has('telegram_user_id')) {
            $user->telegram_user_id = $request->telegram_user_id;
        }

        $user->save();

        cache()->forget('user_is_active_' . $user->id);

        $roleId = request()->role;

        $userRole = Role::where('id', request()->role)->first();

        if ($roleId != '' && $userRole->name != $user->user_other_role) {

            $employeeRole = Role::where('name', 'employee')->first();

            $user = User::withoutGlobalScope(ActiveScope::class)->findOrFail($user->id);

            RoleUser::where('user_id', $user->id)->delete();
            $user->roles()->attach($employeeRole->id);

            if ($employeeRole->id != $roleId) {
                $user->roles()->attach($roleId);
            }

            $user->assignUserRolePermission($roleId);

            $userSession = new AppSettingController();
            $userSession->deleteSessions([$user->id]);
        }

        $tags = json_decode($request->tags);

        if (!empty($tags)) {
            EmployeeSkill::where('user_id', $user->id)->delete();

            foreach ($tags as $tag) {
                // Check or store skills
                $skillData = Skill::firstOrCreate(['name' => $tag->value]);

                // Store user skills
                $skill = new EmployeeSkill();
                $skill->user_id = $user->id;
                $skill->skill_id = $skillData->id;
                $skill->save();
            }
        }

        $employee = EmployeeDetails::where('user_id', '=', $user->id)->first();

        if (empty($employee)) {
            $employee = new EmployeeDetails();
            $employee->user_id = $user->id;
        }

        $this->employeeData($request, $employee);

        $employee->last_date = null;

        if ($request->last_date != '') {
            $employee->last_date = Carbon::createFromFormat($this->company->date_format, $request->last_date)->format('Y-m-d');
        }

        $employee->save();

        // To add custom fields data
        if ($request->custom_fields_data) {
            $employee->updateCustomFieldData($request->custom_fields_data);
        }

        if (user()->id == $user->id) {
            session()->forget('user');
        }

        return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => route('employees.index')]);
    }

    /**
     * @param int $id
     * @return array
     */
    public function destroy($id)
    {
        $user = User::withoutGlobalScope(ActiveScope::class)->findOrFail($id);
        $this->deletePermission = user()->permission('delete_employees');

        abort_403(!($this->deletePermission == 'all' || ($this->deletePermission == 'added' && $user->employeeDetail->added_by == user()->id)));


        if ($user->hasRole('admin') && !in_array('admin', user_roles())) {
            return Reply::error(__('messages.adminCannotDelete'));
        }

        $this->deleteEmployee($user);

        return Reply::success(__('messages.deleteSuccess'));

    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $this->viewPermission = user()->permission('view_employees');

        $this->employee = User::with(['employeeDetail.designation', 'employeeDetail.department','appreciations', 'appreciations.award', 'appreciations.award.awardIcon', 'employeeDetail.reportingTo', 'country', 'emergencyContacts', 'reportingTeam' => function ($query) {
            $query->join('users', 'users.id', '=', 'employee_details.user_id');
            $query->where('users.status', '=', 'active');
        }, 'reportingTeam.user', 'leaveTypes', 'leaveTypes.leaveType', 'appreciationsGrouped', 'appreciationsGrouped.award', 'appreciationsGrouped.award.awardIcon'])
        ->withoutGlobalScope(ActiveScope::class)
        ->withOut('clientDetails', 'role')
        ->withCount('member', 'agents', 'openTasks')
        ->findOrFail($id);

        $this->employeeLanguage = LanguageSetting::where('language_code', $this->employee->locale)->first();

        if (!$this->employee->hasRole('employee')) {
            abort(404);
        }

        if ($this->employee->status == 'deactive' && !in_array('admin', user_roles())) {
            abort(403);
        }

        abort_403(in_array('client', user_roles()));

        $tab = request('tab');

        if (
            $this->viewPermission == 'all'
            || ($this->viewPermission == 'added' && $this->employee->employeeDetail->added_by == user()->id)
            || ($this->viewPermission == 'owned' && $this->employee->employeeDetail->user_id == user()->id)
            || ($this->viewPermission == 'both' && ($this->employee->employeeDetail->user_id == user()->id || $this->employee->employeeDetail->added_by == user()->id))
        ) {

            if ($tab == '') {  // Works for profile

                $this->fromDate = now()->timezone($this->company->timezone)->startOfMonth()->toDateString();
                $this->toDate = now()->timezone($this->company->timezone)->endOfMonth()->toDateString();

                $this->lateAttendance = Attendance::whereBetween(DB::raw('DATE(`clock_in_time`)'), [$this->fromDate, $this->toDate])
                    ->where('late', 'yes')->where('user_id', $id)->count();

                $this->leavesTaken = Leave::selectRaw('count(*) as count, SUM(if(duration="half day", 1, 0)) AS halfday')
                    ->where('user_id', $id)
                    ->where('status', 'approved')
                    ->whereBetween(DB::raw('DATE(`leave_date`)'), [$this->fromDate, $this->toDate])
                    ->first();

                $this->leavesTaken = (!is_null($this->leavesTaken)) ? $this->leavesTaken->count - ($this->leavesTaken->halfday * 0.5) : 0;

                $this->taskChart = $this->taskChartData($id);
                $this->ticketChart = $this->ticketChartData($id);

                if (!is_null($this->employee->employeeDetail)) {
                    $this->employeeDetail = $this->employee->employeeDetail->withCustomFields();

                    $customFields = $this->employeeDetail->getCustomFieldGroupsWithFields();

                    if (!empty($customFields)) {
                        $this->fields = $customFields->fields;
                    }
                }

                $taskBoardColumn = TaskboardColumn::completeColumn();

                $this->taskCompleted = Task::join('task_users', 'task_users.task_id', '=', 'tasks.id')
                    ->where('task_users.user_id', $id)
                    ->where('tasks.board_column_id', $taskBoardColumn->id)
                    ->count();

                $hoursLogged = ProjectTimeLog::where('user_id', $id)->sum('total_minutes');
                $breakMinutes = ProjectTimeLogBreak::userBreakMinutes($id);

                $timeLog = intdiv($hoursLogged - $breakMinutes, 60);

                $this->hoursLogged = $timeLog;
            }

        }

        $this->pageTitle = $this->employee->name;
        $viewDocumentPermission = user()->permission('view_documents');
        $viewImmigrationPermission = user()->permission('view_immigration');

        switch ($tab) {
        case 'tickets':
            return $this->tickets();
        case 'projects':
            return $this->projects();

        case 'tasks':
            return $this->tasks();
        case 'leaves':
            return $this->leaves();
        case 'timelogs':
            return $this->timelogs();
        case 'documents':
            abort_403(($viewDocumentPermission == 'none'));
            $this->view = 'employees.ajax.documents';
            break;
        case 'emergency-contacts':
            $this->view = 'employees.ajax.emergency-contacts';
            break;
        case 'appreciation':
            $viewAppreciationPermission = user()->permission('view_appreciation');
            abort_403(!in_array($viewAppreciationPermission, ['all', 'added', 'owned', 'both']));

            $this->appreciations = $this->appreciation($this->employee->id);
            $this->view = 'employees.ajax.appreciations';
            break;
        case 'leaves-quota':
            $this->leaveQuota($id);
            $this->leavesTakenByUser = Leave::byUserCount($this->employee);
            $this->leaveTypes = LeaveType::byUser($this->employee);
            $this->employeeLeavesQuotas = $this->employee->leaveTypes;
            $this->employeeLeavesQuota = clone $this->employeeLeavesQuotas;

            $totalLeaves = 0;

            foreach($this->leaveTypes as $key => $leavesCount)
            {
                $leavesCountCheck = $leavesCount->leaveTypeCodition($leavesCount, $this->userRole);

                if($leavesCountCheck && $this->employeeLeavesQuotas[$key]->leave_type_id == $leavesCount->id){
                    $totalLeaves += $this->employeeLeavesQuotas[$key]->no_of_leaves;
                }
            }

            $this->allowedLeaves = $totalLeaves;
            $this->view = 'employees.ajax.leaves_quota';
            break;
        case 'shifts':
            abort_403(user()->permission('view_shift_roster') != 'all' || !in_array('attendance', user_modules()));
            $this->view = 'employees.ajax.shifts';
            break;
        case 'permissions':
            abort_403(user()->permission('manage_role_permission_setting') != 'all');

            $this->modulesData = Module::with('permissions')->withCount('customPermissions')->get();
            $this->view = 'employees.ajax.permissions';
            break;

        case 'activity':
            $this->activities = UserActivity::where('user_id', $id)->orderBy('id', 'desc')->get();
            $this->view = 'employees.ajax.activity';
            break;

        case 'immigration':
            abort_403($viewImmigrationPermission == 'none');
            $this->passport = Passport::with('country')->where('user_id', $this->employee->id )->first();
            $this->visa = VisaDetail::with('country')->where('user_id', $this->employee->id)->get();
            $this->view = 'employees.ajax.immigration';
            break;

        default:
            $this->view = 'employees.ajax.profile';
            break;
        }

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();

            return Reply::dataOnly(['views' => $this->view, 'status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->activeTab = $tab ?: 'profile';

        return view('employees.show', $this->data);
    }

    /**
     * XXXXXXXXXXX
     *
     * @return array
     */
    public function taskChartData($id)
    {
        $taskStatus = TaskboardColumn::all();
        $data['labels'] = $taskStatus->pluck('column_name');
        $data['colors'] = $taskStatus->pluck('label_color');
        $data['values'] = [];

        foreach ($taskStatus as $label) {
            $data['values'][] = Task::join('task_users', 'task_users.task_id', '=', 'tasks.id')
                ->where('task_users.user_id', $id)->where('tasks.board_column_id', $label->id)->count();
        }

        return $data;
    }

    /**
     * XXXXXXXXXXX
     *
     * @return array
     */
    public function ticketChartData($id)
    {
        $labels = ['open', 'pending', 'resolved', 'closed'];
        $data['labels'] = [__('app.open'), __('app.pending'), __('app.resolved'), __('app.closed')];
        $data['colors'] = ['#D30000', '#FCBD01', '#2CB100', '#1d82f5'];
        $data['values'] = [];

        foreach ($labels as $label) {
            $data['values'][] = Ticket::where('agent_id', $id)->where('status', $label)->count();
        }

        return $data;
    }

    public function byDepartment($id)
    {
        $users = User::join('employee_details', 'employee_details.user_id', '=', 'users.id');

        if ($id != 0) {
            $users = $users->where('employee_details.department_id', $id);
        }

        $users = $users->select('users.*')->get();

        $options = '';

        foreach ($users as $item) {
            $options .= '<option  data-content="<div class=\'d-inline-block mr-1\'><img class=\'taskEmployeeImg rounded-circle\' src=' . $item->image_url . ' ></div>  ' . $item->name . '" value="' . $item->id . '"> ' . $item->name . ' </option>';
        }

        return Reply::dataOnly(['status' => 'success', 'data' => $options]);
    }

    public function appreciation($employeeID)
    {
        $viewAppreciationPermission = user()->permission('view_appreciation');

        if($viewAppreciationPermission == 'none'){
            return [];
        }

        $appreciations = Appreciation::with(['award','award.awardIcon', 'awardTo'])->select('id', 'award_id', 'award_to', 'award_date', 'image', 'summary', 'created_at');
        $appreciations->join('awards', 'awards.id', '=', 'appreciations.award_id');

        if ($viewAppreciationPermission == 'added') {
            $appreciations->where('appreciations.added_by', user()->id);
        }

        if ($viewAppreciationPermission == 'owned') {
            $appreciations->where('appreciations.award_to', user()->id);
        }

        if ($viewAppreciationPermission == 'both') {
            $appreciations->where(function ($q) {
                $q->where('appreciations.added_by', '=', user()->id);

                $q->orWhere('appreciations.award_to', '=', user()->id);
            });
        }

        $appreciations = $appreciations->select('appreciations.*')->where('appreciations.award_to', $employeeID)->get();

        return $appreciations;
    }

    public function projects()
    {

        $viewPermission = user()->permission('view_employee_projects');
        abort_403(!in_array($viewPermission, ['all']));

        $tab = request('tab');
        $this->activeTab = $tab ?: 'profile';
        $this->view = 'employees.ajax.projects';

        $dataTable = new ProjectsDataTable();

        return $dataTable->render('employees.show', $this->data);

    }

    public function tickets()
    {
        $viewPermission = user()->permission('view_tickets');
        abort_403(!(in_array($viewPermission, ['all']) && in_array('tickets', user_modules())));
        $tab = request('tab');
        $this->activeTab = $tab ?: 'profile';
        $this->tickets = Ticket::all();
        $this->view = 'employees.ajax.tickets';
        $dataTable = new TicketDataTable();

        return $dataTable->render('employees.show', $this->data);

    }

    public function tasks()
    {
        $viewPermission = user()->permission('view_employee_tasks');
        abort_403(!in_array($viewPermission, ['all']));

        $tab = request('tab');
        $this->activeTab = $tab ?: 'profile';
        $this->taskBoardStatus = TaskboardColumn::all();
        $this->view = 'employees.ajax.tasks';

        $dataTable = new TasksDataTable();

        return $dataTable->render('employees.show', $this->data);
    }

    public function leaves()
    {

        $viewPermission = user()->permission('view_leaves_taken');
        abort_403(!in_array($viewPermission, ['all']));

        $tab = request('tab');
        $this->activeTab = $tab ?: 'profile';
        $this->leaveTypes = LeaveType::all();
        $this->view = 'employees.ajax.leaves';

        $dataTable = new LeaveDataTable();

        return $dataTable->render('employees.show', $this->data);
    }

    public function timelogs()
    {

        $viewPermission = user()->permission('view_employee_timelogs');
        abort_403(!(in_array($viewPermission, ['all']) && in_array('timelogs', user_modules())));

        $tab = request('tab');
        $this->activeTab = $tab ?: 'profile';
        $this->view = 'employees.ajax.timelogs';

        $dataTable = new TimeLogsDataTable();

        return $dataTable->render('employees.show', $this->data);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function inviteMember()
    {
        abort_403(!in_array(user()->permission('add_employees'), ['all']));

        return view('employees.ajax.invite_member', $this->data);

    }

    /**
     * XXXXXXXXXXX
     *
     * @return \Illuminate\Http\Response
     */
    public function sendInvite(InviteEmailRequest $request)
    {
        $emails = json_decode($request->email);

        if (!empty($emails)) {
            foreach ($emails as $email) {
                $invite = new UserInvitation();
                $invite->user_id = user()->id;
                $invite->email = $email->value;
                $invite->message = $request->message;
                $invite->invitation_type = 'email';
                $invite->invitation_code = sha1(time() . user()->id);
                $invite->save();
            }
        }

        return Reply::success(__('messages.inviteEmailSuccess'));
    }

    /**
     * XXXXXXXXXXX
     *
     * @return \Illuminate\Http\Response
     */
    public function createLink(CreateInviteLinkRequest $request)
    {
        $invite = new UserInvitation();
        $invite->user_id = user()->id;
        $invite->invitation_type = 'link';
        $invite->invitation_code = sha1(time() . user()->id);
        $invite->email_restriction = (($request->allow_email == 'selected') ? $request->email_domain : null);
        $invite->save();

        return Reply::successWithData(__('messages.inviteLinkSuccess'), ['link' => route('invitation', $invite->invitation_code)]);
    }

    /**
     * @param mixed $request
     * @param mixed $employee
     */
    public function employeeData($request, $employee): void
    {
        $employee->employee_id = $request->employee_id;
        $employee->address = $request->address;
        $employee->hourly_rate = $request->hourly_rate;
        $employee->slack_username = $request->slack_username;
        $employee->department_id = $request->department;
        $employee->designation_id = $request->designation;
        $employee->reporting_to = $request->reporting_to;
        $employee->about_me = $request->about_me;
        $employee->joining_date = Carbon::createFromFormat($this->company->date_format, $request->joining_date)->format('Y-m-d');
        $employee->date_of_birth = $request->date_of_birth ? Carbon::createFromFormat($this->company->date_format, $request->date_of_birth)->format('Y-m-d') : null;
        $employee->calendar_view = 'task,events,holiday,tickets,leaves';
        $employee->probation_end_date = $request->probation_end_date ? Carbon::createFromFormat($this->company->date_format, $request->probation_end_date)->format('Y-m-d') : null;
        $employee->notice_period_start_date = $request->notice_period_start_date ? Carbon::createFromFormat($this->company->date_format, $request->notice_period_start_date)->format('Y-m-d') : null;
        $employee->notice_period_end_date = $request->notice_period_end_date ? Carbon::createFromFormat($this->company->date_format, $request->notice_period_end_date)->format('Y-m-d') : null;
        $employee->marital_status = $request->marital_status;
        $employee->marriage_anniversary_date = $request->marriage_anniversary_date ? Carbon::createFromFormat($this->company->date_format, $request->marriage_anniversary_date)->format('Y-m-d') : null;
        $employee->employment_type = $request->employment_type;
        $employee->internship_end_date = $request->internship_end_date ? Carbon::createFromFormat($this->company->date_format, $request->internship_end_date)->format('Y-m-d') : null;
        $employee->contract_end_date = $request->contract_end_date ? Carbon::createFromFormat($this->company->date_format, $request->contract_end_date)->format('Y-m-d') : null;
    }

    public function importMember()
    {
        $this->pageTitle = __('app.importExcel') . ' ' . __('app.employee');

        $addPermission = user()->permission('add_employees');
        abort_403(!in_array($addPermission, ['all', 'added']));


        if (request()->ajax()) {
            $html = view('employees.ajax.import', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'employees.ajax.import';

        return view('employees.create', $this->data);
    }

    public function importStore(ImportRequest $request)
    {
        $this->importFileProcess($request, EmployeeImport::class);

        $view = view('employees.ajax.import_progress', $this->data)->render();

        return Reply::successWithData(__('messages.importUploadSuccess'), ['view' => $view]);
    }

    public function importProcess(ImportProcessRequest $request)
    {
        $batch = $this->importJobProcess($request, EmployeeImport::class, ImportEmployeeJob::class);

        return Reply::successWithData(__('messages.importProcessStart'), ['batch' => $batch]);
    }

    public function leaveQuota($id)
    {
        $roles = User::with('roles')->findOrFail($id);
        $userRole = [];

        $userRoles = $roles->roles->count() > 1 ? $roles->roles->where('name', '!=', 'employee') : $roles->roles;

        foreach($userRoles as $role){
            $userRole[] = $role->id;
        }

        $this->userRole = $userRole;
    }

}
