<?php

namespace App\Http\Controllers;

use App\DataTables\LeaveDataTable;
use App\Helper\Reply;
use App\Http\Requests\Leaves\ActionLeave;
use App\Http\Requests\Leaves\StoreLeave;
use App\Http\Requests\Leaves\UpdateLeave;
use App\Models\EmployeeDetails;
use App\Models\EmployeeLeaveQuota;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveSetting;
use App\Models\LeaveType;
use App\Models\Permission;
use App\Models\User;
use App\Scopes\ActiveScope;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeaveController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.leaves';
        $this->leaveSetting = LeaveSetting::first();
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('leaves', $this->user->modules));

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(LeaveDataTable $dataTable)
    {
        $viewPermission = user()->permission('view_leave');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        $reportingTo = User::with('employeeDetail')->whereHas('employeeDetail', function ($q) {
            $q->where('reporting_to', user()->id);
        })->get();

        $employee = User::allEmployees(null, true, ($viewPermission == 'all' ? 'all' : null));
        $this->employees = $reportingTo->merge($employee);

        $this->leaveTypes = LeaveType::all();

        return $dataTable->render('leaves.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->addPermission = user()->permission('add_leave');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $this->employees = User::allEmployees(null, true, ($this->addPermission == 'all' ? 'all' : null));

        $this->currentDate = Carbon::now()->format('Y-m-d');

        $leaveQuotas = LeaveType::select('leave_types.*', 'employee_details.notice_period_start_date', 'employee_details.probation_end_date',
        'employee_details.department_id as employee_department', 'employee_details.designation_id as employee_designation',
        'employee_details.marital_status as maritalStatus', 'users.gender as usergender', 'employee_details.joining_date', 'employee_leave_quotas.no_of_leaves as employeeLeave')
            ->join('employee_leave_quotas', 'employee_leave_quotas.leave_type_id', 'leave_types.id')
            ->join('users', 'users.id', 'employee_leave_quotas.user_id')
            ->join('employee_details', 'employee_details.user_id', 'users.id');

        if ($this->addPermission == 'added') {
            $this->defaultAssign = user();
            $this->leaveQuotas = $leaveQuotas->where('users.id', user()->id)->get();
            $this->leaveTypeRole(user()->id);
        }
        else if (isset(request()->default_assign)) {
            $this->defaultAssign = User::findOrFail(request()->default_assign);
            $this->leaveQuotas = $leaveQuotas->where('users.id', request()->default_assign)->get();
            $this->leaveTypeRole(request()->default_assign);
        }
        else {
            $this->leaveTypes = LeaveType::all();
        }

        if (request()->ajax()) {
            $this->pageTitle = __('modules.leaves.addLeave');
            $html = view('leaves.ajax.create', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'leaves.ajax.create';

        return view('leaves.create', $this->data);
    }

    /**
     * @param StoreLeave $request
     * @return array|void
     * @throws \Froiden\RestAPI\Exceptions\RelatedResourceNotFoundException
     */
    public function store(StoreLeave $request)
    {
        $this->addPermission = user()->permission('add_leave');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $redirectUrl = urldecode($request->redirect_url);

        if ($redirectUrl == '') {
            $redirectUrl = route('leaves.index');
        }

        $leaveType = LeaveType::findOrFail($request->leave_type_id);
        $employeeLeaveQuota = EmployeeLeaveQuota::whereUserId($request->user_id)->whereLeaveTypeId($request->leave_type_id)->first();

        $totalAllowedLeaves = ($employeeLeaveQuota) ? $employeeLeaveQuota->no_of_leaves : $leaveType->no_of_leaves;
        $sDate = Carbon::createFromFormat(company()->date_format, $request->multiStartDate);
        $eDate = Carbon::createFromFormat(company()->date_format, $request->multiEndDate);
        $diffInDays = $sDate->diffInDays($eDate) + 1;

        if($totalAllowedLeaves < $diffInDays) {
            return Reply::error(__('messages.leaveLimitError'));
        }

        $uniqueId = Str::random(16);
        $employee = User::with('leaveTypes')->findOrFail($request->user_id);

        $employeeLeavesQuotas = $employee->leaveTypes;
        $allowedLeaves = clone $employeeLeavesQuotas;
        $totalEmployeeLeave = $allowedLeaves->sum('no_of_leaves');

        if ($leaveType->monthly_limit > 0) {
            if ($request->duration != 'multiple') {
                $duration = match ($request->duration) {
                    'first_half', 'second_half' => 'half day',
                    default => $request->duration,
                };

                $leaveTaken = LeaveType::byUser($request->user_id, $request->leave_type_id, array('approved', 'pending'), $request->leave_date);
                $leaveTaken = $leaveTaken->first();

                $dateApplied = Carbon::createFromFormat($this->company->date_format, $request->leave_date);

                /** @phpstan-ignore-next-line */
                $currentMonthFullDay = Leave::whereBetween('leave_date', [$dateApplied->startOfMonth()->toDateString(), $dateApplied->endOfMonth()->toDateString()])
                    ->where('leave_type_id', $leaveType->id)
                    ->where('duration', '<>', 'half day')
                    ->whereIn('status', ['approved', 'pending'])
                    ->where('user_id', $request->user_id)
                    ->get()->count();

                /** @phpstan-ignore-next-line */
                $currentMonthHalfDay = Leave::whereBetween('leave_date', [$dateApplied->startOfMonth()->toDateString(), $dateApplied->endOfMonth()->toDateString()])
                    ->where('leave_type_id', $leaveType->id)
                    ->where('duration', 'half day')
                    ->whereIn('status', ['approved', 'pending'])
                    ->where('user_id', $request->user_id)
                    ->get()->count();

                /** @phpstan-ignore-next-line */
                $appliedLimit = ($currentMonthFullDay + ($currentMonthHalfDay / 2)) + (($duration == 'half day') ? 0.5 : 1);

                /** @phpstan-ignore-next-line */
                if (!is_null($leaveTaken->leavesCount) && ((($leaveTaken->leavesCount->count - ($leaveTaken->leavesCount->halfday * 0.5)) + (($duration == 'half day') ? 0.5 : 1)) > $totalAllowedLeaves)) {
                    return Reply::error(__('messages.leaveLimitError'));
                }

                if ($appliedLimit > $leaveType->monthly_limit) {
                    return Reply::error(__('messages.monthlyLeaveLimitError'));
                }


            }
            else {

                $sDate = Carbon::createFromFormat(company()->date_format, $request->multiStartDate);
                $eDate = Carbon::createFromFormat(company()->date_format, $request->multiEndDate);
                $multipleDates = CarbonPeriod::create($sDate, $eDate);

                foreach ($multipleDates as $multipleDate) {
                    $multiDates[] = $multipleDate->format('Y-m-d');
                }

                /** @phpstan-ignore-next-line */
                foreach ($multiDates as $dateData) {
                    $leaveTaken = LeaveType::byUser($request->user_id, $request->leave_type_id, array('approved', 'pending'), Carbon::parse($dateData)->format(company()->date_format));
                    $leaveTaken = $leaveTaken->first();

                    /** @phpstan-ignore-next-line */
                    if (!is_null($leaveTaken->leavesCount) && (($leaveTaken->leavesCount->count - ($leaveTaken->leavesCount->halfday * 0.5)) + count($multipleDates)) > $totalAllowedLeaves) {
                        return Reply::error(__('messages.leaveLimitError'));
                    }
                    elseif (count($multipleDates) > $totalAllowedLeaves) { /** @phpstan-ignore-line */
                        return Reply::error(__('messages.leaveLimitError'));
                    }

                    /** @phpstan-ignore-next-line */
                    array_push($multiDates, Carbon::parse($dateData)->format('Y-m-d'));
                }


                foreach ($multiDates as $dateData) {
                    $dateApplied = Carbon::parse($dateData);

                    /** @phpstan-ignore-next-line */
                    $currentMonthFullDay = Leave::whereBetween('leave_date', [$dateApplied->startOfMonth()->toDateString(), $dateApplied->endOfMonth()->toDateString()])
                        ->where('leave_type_id', $leaveType->id)
                        ->where('duration', '<>', 'half day')
                        ->whereIn('status', ['approved', 'pending'])
                        ->where('user_id', $request->user_id)
                        ->get()->count();

                    /** @phpstan-ignore-next-line */
                    $currentMonthHalfDay = Leave::whereBetween('leave_date', [$dateApplied->startOfMonth()->toDateString(), $dateApplied->endOfMonth()->toDateString()])
                        ->where('leave_type_id', $leaveType->id)
                        ->where('duration', 'half day')
                        ->whereIn('status', ['approved', 'pending'])
                        ->where('user_id', $request->user_id)
                        ->get()->count();

                    /** @phpstan-ignore-next-line */
                    $appliedLimit = ($currentMonthFullDay + ($currentMonthHalfDay / 2)) + count($multipleDates);

                    if ($appliedLimit > $leaveType->monthly_limit) {
                        return Reply::error(__('messages.monthlyLeaveLimitError'));
                    }
                }

            }

        }

        if ($request->duration == 'multiple') {
            session(['leaves_duration' => 'multiple']);

            $multipleDates = CarbonPeriod::create(Carbon::createFromFormat(company()->date_format, $request->multiStartDate)->toDateString(), Carbon::createFromFormat(company()->date_format, $request->multiEndDate)->toDateString());

            $leaveTaken = LeaveType::byUser($request->user_id, $request->leave_type_id, array('approved', 'pending'), $request->leave_date);
            $leaveTaken = $leaveTaken->first();

            foreach ($multipleDates as $multipleDate) {
                $multiDates[] = $multipleDate->format('Y-m-d');
            }

            $leaveApplied = Leave::select(DB::raw('DATE_FORMAT(leave_date, "%Y-%m-%d") as leave_date_new'))
                ->where('user_id', $request->user_id)
                ->where('status', '!=', 'rejected')
                /** @phpstan-ignore-next-line */
                ->whereIn('leave_date', $multiDates)
                ->pluck('leave_date_new')
                ->toArray();

            if (!empty($leaveApplied)) {
                return Reply::error(__('messages.leaveApplyError'));
            }

            /* check leave limit for the selected leave type start */

            $holidays = Holiday::select(DB::raw('DATE_FORMAT(date, "%Y-%m-%d") as holiday_date'))
                ->whereIn('date', $multiDates) /** @phpstan-ignore-line */
                ->pluck('holiday_date')->toArray();

            /** @phpstan-ignore-next-line */
            foreach ($multiDates as $date) {

                $dateInsert = Carbon::parse($date);

                if (!in_array($dateInsert, $holidays)) {
                    $leaveYear = Carbon::createFromFormat('d-m-Y', '01-'.company()->year_starts_from.'-'.$dateInsert->copy()->year)->startOfMonth();

                    if ($leaveYear->gt($dateInsert)) {
                        $leaveYear = $leaveYear->subYear();
                    }

                    $userTotalLeaves = Leave::byUserCount($request->user_id, $leaveYear->year);
                    $remainingLeave = $employeeLeaveQuota->no_of_leaves - $userTotalLeaves;

                    if(!is_null($leaveTaken->leavesCount) && ($leaveTaken->leavesCount->count + .5) == $employeeLeaveQuota->no_of_leaves) {
                        return Reply::error(__('messages.multipleRemainingLeaveError', ['leaves' => $remainingLeave]));
                    }

                    /** @phpstan-ignore-next-line */
                    if (!is_null($leaveTaken->leavesCount) && (($leaveTaken->leavesCount->count - ($leaveTaken->leavesCount->halfday * 0.5)) + count($multiDates)) > $employeeLeaveQuota->no_of_leaves) {
                        return Reply::error(__('messages.leaveLimitError'));
                    }
                    elseif (($userTotalLeaves + count($multiDates)) > $totalEmployeeLeave) { /** @phpstan-ignore-line */
                        return Reply::error(__('messages.leaveLimitError'));
                    }

                }
            }


            /* check leave limit for the selected leave type end */

            $leaveId = '';

            // Remove duplicate dates $multiDates
            $multiDates = array_unique($multiDates);

            /** @phpstan-ignore-next-line */
            foreach ($multiDates as $date) {

                $dateInsert = Carbon::parse($date)->format('Y-m-d');

                if (!in_array($dateInsert, $holidays)) {
                    $leave = new Leave();
                    $leave->user_id = $request->user_id;
                    $leave->unique_id = $uniqueId;
                    $leave->leave_type_id = $request->leave_type_id;
                    $leave->duration = $request->duration;
                    $leave->leave_date = $dateInsert;
                    $leave->reason = $request->reason;
                    $leave->status = ($request->has('status') ? $request->status : 'pending');
                    $leave->save();

                    $leaveId = $leave->id;
                    session()->forget('leaves_duration');
                }
            }

            return Reply::successWithData(__('messages.leaveApplySuccess'), ['leaveID' => $leaveId, 'redirectUrl' => $redirectUrl]);
        }

        $dateInsert = Carbon::createFromFormat($this->company->date_format, $request->leave_date)->format('Y-m-d');
        $leaveApplied = Leave::where('user_id', $request->user_id)
        ->where('status', '!=', 'rejected')
        ->whereDate('leave_date', $dateInsert)
        ->first();

        $holiday = Holiday::select(DB::raw('DATE_FORMAT(date, "%Y-%m-%d") as holiday_date'))->where('date', $dateInsert)->first();

        if (!empty($leaveApplied) && $request->duration == 'single') {
            return Reply::error(__('messages.leaveApplyError'));
        }

        if (!is_null($holiday)) {
            return Reply::error(__('messages.holidayLeaveApplyError'));
        }

        /* check leave limit for the selected leave type start */
        $leaveYear = Carbon::createFromFormat('d-m-Y', '01-'.company()->year_starts_from.'-'.Carbon::parse($dateInsert)->year)->startOfMonth();

        if ($leaveYear->gt(Carbon::parse($dateInsert))) {
            $leaveYear = $leaveYear->subYear();
        }

        $leaveTaken = LeaveType::byUser($request->user_id, $request->leave_type_id, array('approved', 'pending'), $request->leave_date);
        $leaveTaken = $leaveTaken->first();

        $userTotalLeaves = Leave::byUserCount($request->user_id, $leaveYear->year);
        $remainingLeave = $employeeLeaveQuota->no_of_leaves - $userTotalLeaves;

        if(!is_null($leaveTaken->leavesCount) && ($leaveTaken->leavesCount->count + .5) == $employeeLeaveQuota->no_of_leaves && $request->duration == 'single') {
            return Reply::error(__('messages.multipleRemainingLeaveError', ['leaves' => $remainingLeave]));
        }

        /** @phpstan-ignore-next-line */
        if($request->duration == 'single'){
            if (!is_null($leaveTaken->leavesCount) && (($leaveTaken->leavesCount->count - ($leaveTaken->leavesCount->halfday * 0.5)) + 1) > $employeeLeaveQuota->no_of_leaves) {
                return Reply::error(__('messages.leaveLimitError'));
            }
            elseif (($userTotalLeaves + 1) > $totalEmployeeLeave) { /** @phpstan-ignore-line */
                return Reply::error(__('messages.leaveLimitError'));
            }
        }

        /* check leave limit for the selected leave type end */

        $duration = match ($request->duration) {
            'first_half', 'second_half' => 'half day',
            default => $request->duration,
        };

        $leave = new Leave();
        $leave->user_id = $request->user_id;
        $leave->unique_id = $uniqueId;
        $leave->leave_type_id = $request->leave_type_id;
        $leave->duration = $duration;

        if ($duration == 'half day') {
            /* check leave limit for the selected leave type start */
            $dateInsert = Carbon::createFromFormat($this->company->date_format, $request->leave_date)->format('Y-m-d');

            $leaveApplied = Leave::where('user_id', $request->user_id)
            ->where('status', '!=', 'rejected')
            ->whereDate('leave_date', $dateInsert)->first();

            $userHalfDaysLeave = Leave::where([
                ['user_id', $request->user_id],
                ['status', '!=', 'rejected'],
                ['duration', $duration],
                ])->whereDate('leave_date', $dateInsert)->count();

            if($userHalfDaysLeave > 1){
                return Reply::error(__('messages.leaveApplyError'));
            }
            elseif(!is_null($leaveApplied) && $leaveApplied->duration != 'half day') {
                return Reply::error(__('messages.leaveApplyError'));
            }
            elseif(!is_null($leaveApplied) && $leaveApplied->half_day_type == $request->duration){
                return Reply::error(__('messages.leaveApplyError'));
            }

            if (!is_null($leaveTaken->leavesCount) && (($leaveTaken->leavesCount->count - ($leaveTaken->leavesCount->halfday * 0.5)) + 0.5) > $employeeLeaveQuota->no_of_leaves) {
                return Reply::error(__('messages.leaveLimitError'));
            }
            elseif ($userTotalLeaves + 0.5 > $totalEmployeeLeave) { /** @phpstan-ignore-line */
                return Reply::error(__('messages.leaveLimitError'));
            }

            /* check leave limit for the selected leave type end */
            $leave->half_day_type = $request->duration;
        }

        $leave->leave_date = Carbon::createFromFormat($this->company->date_format, $request->leave_date)->format('Y-m-d');
        $leave->reason = $request->reason;
        $leave->status = ($request->has('status') ? $request->status : 'pending');
        $leave->save();

        return Reply::successWithData(__('messages.leaveApplySuccess'), ['leaveID' => $leave->id, 'redirectUrl' => $redirectUrl]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $this->leave = Leave::with('approvedBy', 'user')->where(function($q) use($id){
            $q->where('id', $id);
            $q->orWhere('unique_id', $id);
        })->firstOrFail();

        $this->reportingTo = EmployeeDetails::where('reporting_to', user()->id)->first();

        $this->viewPermission = user()->permission('view_leave');
        abort_403(!($this->viewPermission == 'all'
            || ($this->viewPermission == 'added' && user()->id == $this->leave->added_by)
            || ($this->viewPermission == 'owned' && user()->id == $this->leave->user_id)
            || ($this->viewPermission == 'both' && (user()->id == $this->leave->user_id || user()->id == $this->leave->added_by)) || ($this->reportingTo)
        ));

        $this->pageTitle = $this->leave->user->name;
        $this->reportingPermission = LeaveSetting::value('manager_permission');

        if (request()->ajax()) {
            $html = view('leaves.ajax.show', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        if($this->leave->duration == 'multiple' && !is_null($this->leave->unique_id) && (request()->type != 'single' || !request()->has('type'))){
            $this->multipleLeaves = Leave::with('type', 'user')->where('unique_id', $id)->orderBy('leave_date', 'DESC')->get();
            $this->viewType = 'multiple';
            $this->pendingCountLeave = $this->multipleLeaves->where('status', 'pending')->count();

            $this->view = 'leaves.ajax.multiple-leaves';
        }
        else {
            $this->view = 'leaves.ajax.show';
        }

        return view('leaves.create', $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $this->leave = Leave::with('files')->findOrFail($id);
        $this->editPermission = user()->permission('edit_leave');
        abort_403(!(
            ($this->editPermission == 'all'
                || ($this->editPermission == 'added' && $this->leave->added_by == user()->id)
                || ($this->editPermission == 'owned' && $this->leave->user_id == user()->id)
                || ($this->editPermission == 'both' && ($this->leave->user_id == user()->id || $this->leave->added_by == user()->id))
            )
            && ($this->leave->status == 'pending')));

        $this->employees = User::allEmployees();

        $this->pageTitle = $this->leave->user->name;

        $leaveQuotas = LeaveType::select('leave_types.*', 'employee_details.notice_period_start_date', 'employee_details.probation_end_date',
        'employee_details.department_id as employee_department', 'employee_details.designation_id as employee_designation',
        'employee_details.marital_status as maritalStatus', 'users.gender as usergender', 'employee_details.joining_date')
            ->join('employee_leave_quotas', 'employee_leave_quotas.leave_type_id', 'leave_types.id')
            ->join('users', 'users.id', 'employee_leave_quotas.user_id')
            ->join('employee_details', 'employee_details.user_id', 'users.id');

        if ($this->editPermission == 'added') {
            $this->defaultAssign = user();
            $this->leaveQuotas = $leaveQuotas->where('users.id', user()->id)->get();
            $this->leaveTypeRole(user()->id);
        }
        else if (isset(request()->default_assign)) {
            $this->defaultAssign = User::findOrFail(request()->default_assign);
            $this->leaveQuotas = $leaveQuotas->where('users.id', request()->default_assign)->get();
            $this->leaveTypeRole(request()->default_assign);
        }
        else {
            $this->leaveQuotas = $leaveQuotas->where('users.id', $this->leave->user_id)->get();
            $this->leaveTypeRole($this->leave->user_id);
        }

        if (request()->ajax()) {
            $html = view('leaves.ajax.edit', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'leaves.ajax.edit';

        return view('leaves.create', $this->data);
    }

    /**
     * @param UpdateLeave $request
     * @param int $id
     * @return array|void
     * @throws \Froiden\RestAPI\Exceptions\RelatedResourceNotFoundException
     */
    public function update(UpdateLeave $request, $id)
    {
        $leave = Leave::findOrFail($id);
        $this->editPermission = user()->permission('edit_leave');

        abort_403(!($this->editPermission == 'all'
            || ($this->editPermission == 'added' && $leave->added_by == user()->id)
            || ($this->editPermission == 'owned' && $leave->user_id == user()->id)
            || ($this->editPermission == 'both' && ($leave->user_id == user()->id || $leave->added_by == user()->id))
        ));

        /* check leave limit for the selected leave type start */
        $leaveStartYear = Carbon::parse(now()->format((now(company()->timezone)->year) . '-'. company()->year_starts_from . '-01'));

        if($leaveStartYear->isFuture()){
            $leaveStartYear = $leaveStartYear->subYear();
        }

        $userFullDayLeaves = Leave::where([
            ['user_id', $request->user_id],
            ['leave_type_id', $request->leave_type_id],
            ['status', '!=', 'rejected'],
            ['status', '!=', 'pending'],
            ['duration', '!=', 'half day']
        ])->whereBetween('leave_date', [$leaveStartYear->copy()->toDateString(), $leaveStartYear->copy()->addYear()->toDateString()])
            ->count();
        $userHalfDayLeaves = Leave::where([
            ['user_id', $request->user_id],
            ['leave_type_id', $request->leave_type_id],
            ['status', '!=', 'rejected'],
            ['status', '!=', 'pending']
        ])->whereBetween('leave_date', [$leaveStartYear->copy()->toDateString(), $leaveStartYear->copy()->addYear()->toDateString()])
            ->where('duration', 'half day')->count();

        $userTotalLeaves = $userFullDayLeaves + ($userHalfDayLeaves / 2);

        $leaveQuota = EmployeeLeaveQuota::whereUserId($request->user_id)->whereLeaveTypeId($request->leave_type_id)->first();
        $userRemainingLeaves = $leaveQuota->no_of_leaves - $userTotalLeaves;


        if ((($userTotalLeaves + .5) == $leaveQuota->no_of_leaves) && $userTotalLeaves >= $leaveQuota->no_of_leaves) {
            return Reply::error(__('messages.multipleRemainingLeaveError', ['leaves' => $userRemainingLeaves]));
        }

        if ($userTotalLeaves >= $leaveQuota->no_of_leaves) {
            return Reply::error(__('messages.leaveLimitError'));
        }

        /* check leave limit for the selected leave type end */

        $leave->user_id = $request->user_id;
        $leave->leave_type_id = $request->leave_type_id;
        $leave->leave_date = Carbon::createFromFormat($this->company->date_format, $request->leave_date)->format('Y-m-d');
        $leave->reason = $request->reason;

        if ($request->has('reject_reason')) {
            $leave->reject_reason = $request->reject_reason;
        }

        if ($request->has('status')) {
            $leave->status = $request->status;
        }

        $leave->save();

        $uniqueID = $leave->unique_id;

        if($leave->duration == 'multiple' && !is_null($uniqueID)){
            $route = route('leaves.show', $leave->unique_id).'?tab=multiple-leaves';
        }
        else{
            $route = route('leaves.index');
        }

        return Reply::successWithData(__('messages.leaveAssignSuccess'), ['leaveID' => $leave->id, 'redirectUrl' => $route]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $leave = Leave::findOrFail($id);
        $uniqueID = $leave->unique_id;

        $this->deletePermission = user()->permission('delete_leave');
        $this->deleteApproveLeavePermission = user()->permission('delete_approve_leaves');

        abort_403(!($this->deletePermission == 'all'
            || ($this->deletePermission == 'added' && $leave->added_by == user()->id)
            || ($this->deletePermission == 'owned' && $leave->user_id == user()->id)
            || ($this->deletePermission == 'both' && ($leave->user_id == user()->id || $leave->added_by == user()->id) || ($this->deleteApproveLeavePermission == 'none'))
        ));

        if(!is_null(request()->uniId) && request()->duration == 'multiple')
        {
            Leave::where('unique_id', request()->uniId)->delete();
        }
        else {
            Leave::destroy($id);
        }

        $totalLeave = $leave->duration == 'multiple' && !is_null($uniqueID) ? Leave::where('unique_id', $uniqueID)->count() : 0;

        if($totalLeave == 0){
            $route = route('leaves.index');
        }
        elseif(request()->type == 'delete-single' && !is_null($uniqueID) && $leave->duration == 'multiple'){
            $route = route('leaves.show', $leave->unique_id);
        }
        else{
            $route = '';
        }

        return Reply::successWithData(__('messages.deleteSuccess'), ['redirectUrl' => $route]);
    }

    public function leaveCalendar(Request $request)
    {
        $viewPermission = user()->permission('view_leave');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        $this->pendingLeaves = Leave::where('status', 'pending')->count();
        $this->employees = User::allEmployees();
        $this->leaveTypes = LeaveType::all();
        $this->pageTitle = 'app.menu.calendar';
        $this->reportingPermission = LeaveSetting::value('manager_permission');

        if (request('start') && request('end')) {

            $leaveArray = array();

            $leavesList = Leave::join('users', 'users.id', 'leaves.user_id')
                ->join('leave_types', 'leave_types.id', 'leaves.leave_type_id')
                ->join('employee_details', 'employee_details.user_id', 'users.id')
                ->where('users.status', 'active')
                ->select('leaves.id', 'users.name', 'leaves.leave_date', 'leaves.status', 'leave_types.type_name', 'leave_types.color', 'leaves.leave_date', 'leaves.duration', 'leaves.status');

            if (!is_null($request->startDate)) {
                $startDate = Carbon::createFromFormat($this->company->date_format, $request->startDate)->toDateString();
                $leavesList->whereRaw('Date(leaves.leave_date) >= ?', [$startDate]);
            }

            if (!is_null($request->endDate)) {
                $endDate = Carbon::createFromFormat($this->company->date_format, $request->endDate)->toDateString();

                $leavesList->whereRaw('Date(leaves.leave_date) <= ?', [$endDate]);
            }

            if ($request->leaveTypeId != 'all' && $request->leaveTypeId != '') {
                $leavesList->where('leave_types.id', $request->leaveTypeId);
            }

            if ($request->status != 'all' && $request->status != '') {
                $leavesList->where('leaves.status', $request->status);
            }

            if ($request->searchText != '') {
                $leavesList->where('users.name', 'like', '%' . $request->searchText . '%');
            }

            if ($viewPermission == 'owned') {
                $leavesList->where(function ($q) {
                    $q->orWhere('leaves.user_id', '=', user()->id);

                    ($this->reportingPermission != 'cannot-approve') ? $q->orWhere('employee_details.reporting_to', user()->id) : '';
                });
            }

            if ($viewPermission == 'added') {
                $leavesList->where(function ($q) {
                    $q->orWhere('leaves.added_by', '=', user()->id);

                    ($this->reportingPermission != 'cannot-approve') ? $q->orWhere('employee_details.reporting_to', user()->id) : '';
                });
            }

            if ($viewPermission == 'both') {
                $leavesList->where(function ($q) {
                    $q->orwhere('leaves.user_id', '=', user()->id);

                    $q->orWhere('leaves.added_by', '=', user()->id);

                    ($this->reportingPermission != 'cannot-approve') ? $q->orWhere('employee_details.reporting_to', user()->id) : '';
                });
            }

            $leaves = $leavesList->get();

            foreach ($leaves as $key => $leave) {
                /** @phpstan-ignore-next-line */
                $title = $leave->name;

                $leaveArray[] = [
                    'id' => $leave->id,
                    'title' => $title,
                    'start' => $leave->leave_date->format('Y-m-d'),
                    'end' => $leave->leave_date->format('Y-m-d'),
                    /** @phpstan-ignore-next-line */
                    'color' => $leave->color
                ];
            }

            return $leaveArray;
        }

        return view('leaves.calendar.index', $this->data);
    }

    public function applyQuickAction(Request $request)
    {
        switch ($request->action_type) {
        case 'delete':
            $this->deleteRecords($request);

            return Reply::success(__('messages.deleteSuccess'));
        case 'change-leave-status':
            $this->changeBulkStatus($request);

            return Reply::success(__('messages.updateSuccess'));
        default:
            return Reply::error(__('messages.selectAction'));
        }
    }

    protected function deleteRecords($request)
    {
        abort_403(user()->permission('delete_leave') != 'all');
        $leaves = Leave::whereIn('id', explode(',', $request->row_ids))->get();

        foreach($leaves as $leave)
        {
            if(!is_null($leave->unique_id) && $leave->duration == 'multiple')
            {
                Leave::where('unique_id', $leave->unique_id)->delete();
            }
            else {
                Leave::destroy($leave->id);
            }
        }
    }

    protected function changeBulkStatus($request)
    {
        abort_403(user()->permission('edit_leave') != 'all');

        $leaves = Leave::whereIn('id', explode(',', $request->row_ids))->get();

        foreach($leaves as $leave)
        {
            if(!is_null($leave->unique_id) && $leave->duration == 'multiple')
            {
                Leave::where('unique_id', $leave->unique_id)->update(['status' => $request->status]);
            }
            else {
                Leave::where('id', $leave->id)->update(['status' => $request->status]);
            }
        }

    }

    public function leaveAction(ActionLeave $request)
    {
        $this->reportingTo = EmployeeDetails::where('reporting_to', user()->id)->first();

        abort_403(!($this->reportingTo) && user()->permission('approve_or_reject_leaves') == 'none');

        if($request->type == 'single'){
            $leave = Leave::findOrFail($request->leaveId);
            $this->leaveStore($leave, $request);
        }
        else {
            $leaves = Leave::where('unique_id', $request->leaveId)->where('status', 'pending')->get();

            foreach($leaves as $leave)
            {
                $this->leaveStore($leave, $request);
            }

        }

        return Reply::success(__('messages.updateSuccess'));
    }

    public function leaveStore($leave, $request)
    {
        $leave->status = $request->action;

        if (isset($request->approveReason)) {
            $leave->approve_reason = $request->approveReason;
        }

        if (isset($request->reason)) {
            $leave->reject_reason = $request->reason;
        }

        $leave->approved_by = user()->id;
        $leave->approved_at = now()->toDateTimeString();
        $leave->save();
    }

    public function preApprove(Request $request)
    {
        $this->reportingTo = EmployeeDetails::where('reporting_to', user()->id)->first();

        $leave = Leave::findOrFail($request->leaveId);
        $leave->manager_status_permission = $request->action;

        $leave->save();

        return Reply::success(__('messages.updateSuccess'));
    }

    public function approveLeave(Request $request)
    {
        $this->reportingTo = EmployeeDetails::where('reporting_to', user()->id)->first();

        abort_403(!($this->reportingTo) && (user()->permission('approve_or_reject_leaves') == 'none'));

        $this->leaveAction = $request->leave_action;
        $this->leaveID = $request->leave_id;
        $this->type = $request->type;

        return view('leaves.approve.index', $this->data);
    }

    public function rejectLeave(Request $request)
    {
        $this->reportingTo = EmployeeDetails::where('reporting_to', user()->id)->first();

        abort_403(!($this->reportingTo) && (user()->permission('approve_or_reject_leaves') == 'none'));

        $this->leaveAction = $request->leave_action;
        $this->leaveID = $request->leave_id;
        $this->type = $request->type;

        return view('leaves.reject.index', $this->data);
    }

    public function personalLeaves()
    {
        $this->pageTitle = __('modules.leaves.myLeaves');

        $this->employee = User::with(['employeeDetail', 'employeeDetail.designation', 'employeeDetail.department', 'leaveTypes', 'leaveTypes.leaveType', 'country', 'employee'])
            ->withoutGlobalScope(ActiveScope::class)
            ->withCount('member', 'agents', 'tasks')
            ->findOrFail(user()->id);

        $this->leaveTypes = LeaveType::byUser(user()->id);
        $this->leaveTypeRole(user()->id);
        $this->leavesTakenByUser = Leave::byUserCount(user()->id);
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

        $this->view = 'leaves.ajax.personal';

        return view('leaves.create', $this->data);
    }

    public function getDate(Request $request)
    {
        if ($request->date != null) {
            $date = Carbon::createFromFormat($this->company->date_format, $request->date)->toDateString();
            $users = Leave::where('leave_date', $date)->where('status', 'approved')->count();
        }
        else{
            $users = '';
        }

        return Reply::dataOnly(['status' => 'success', 'users' => $users]);
    }

    public function viewRelatedLeave(Request $request)
    {
        $this->editLeavePermission = user()->permission('edit_leave');
        $this->deleteLeavePermission = user()->permission('delete_leave');
        $this->approveRejectPermission = user()->permission('approve_or_reject_leaves');
        $this->deleteApproveLeavePermission = user()->permission('delete_approve_leaves');
        $this->multipleLeaves = Leave::with('type', 'user')->where('unique_id', $request->uniqueId)->orderBy('leave_date', 'DESC')->get();
        $this->pendingCountLeave = $this->multipleLeaves->where('status', 'pending')->count();

        $this->viewType = 'model';
        return view('leaves.view-multiple-related-leave', $this->data);
    }

    public function leaveTypeRole($id)
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
