<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Models\Deal;
use App\Models\Task;
use App\Models\User;
use App\Helper\Reply;
use App\Models\Event;
use App\Models\Leave;
use App\Models\Notice;
use App\Models\Ticket;
use App\Models\Holiday;
use App\Models\Project;
use Carbon\CarbonPeriod;
use App\Models\LeadAgent;
use App\Models\Attendance;
use App\Models\Appreciation;
use Illuminate\Http\Request;
use App\Models\CompanyAddress;
use App\Models\ProjectTimeLog;
use App\Models\DashboardWidget;
use App\Models\EmployeeDetails;
use App\Models\TaskboardColumn;
use App\Models\AttendanceSetting;
use App\Models\TicketAgentGroups;
use Illuminate\Support\Facades\DB;
use App\Models\ProjectTimeLogBreak;
use App\Models\EmployeeShiftSchedule;
use App\Http\Requests\ClockIn\ClockInRequest;
use App\Models\Company;
use App\Models\EmployeeShift;

/**
 *
 */
trait EmployeeDashboard
{

    /**
     * @return array|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function employeeDashboard()
    {

        $completedTaskColumn = TaskboardColumn::completeColumn();
        $showClockIn = AttendanceSetting::first();

        $this->attendanceSettings = $this->attendanceShift($showClockIn);

        $startTimestamp = now()->format('Y-m-d') . ' ' . $this->attendanceSettings->office_start_time;

        $endTimestamp = now()->format('Y-m-d') . ' ' . $this->attendanceSettings->office_end_time;
        $officeStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $startTimestamp, $this->company->timezone);
        $officeEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $endTimestamp, $this->company->timezone);

        $officeStartTime = $officeStartTime->setTimezone('UTC');
        $officeEndTime = $officeEndTime->setTimezone('UTC');

        if ($officeStartTime->gt($officeEndTime)) {
            $officeEndTime->addDay();
        }

        $this->cannotLogin = false;

        $date = Carbon::now()->format('Y-m-d');

        $attendance = Attendance::where('user_id', $this->user->id)
            ->where(DB::raw('DATE(`clock_in_time`)'), $date)
            ->get();

        foreach ($attendance as $item) {
            if (now()->between($item->clock_in_time, $item->clock_out_time)) {
                $this->cannotLogin = true;
            }
        }

        if ($showClockIn->employee_clock_in_out == 'yes') {
            if (is_null($this->attendanceSettings->early_clock_in) && !now()->between($officeStartTime, $officeEndTime) && $showClockIn->show_clock_in_button == 'no') {
                $this->cannotLogin = true;
            }
            else{
                $earlyClockIn = Carbon::now(company()->timezone)->addMinutes($this->attendanceSettings->early_clock_in);
                $earlyClockIn = $earlyClockIn->setTimezone('UTC');

                if($earlyClockIn->gte($officeStartTime) || $showClockIn->show_clock_in_button == 'yes'){
                    $this->cannotLogin = false;
                }
                else {
                    $this->cannotLogin = true;
                }

            }

            if ($this->cannotLogin == true && now()->betweenIncluded($officeStartTime->copy()->subDay(), $officeEndTime->copy()->subDay())) {
                $this->cannotLogin = false;
            }
        }
        else {
            $this->cannotLogin = true;
        }

        if ($this->attendanceSettings->shift_name == 'Day Off') {
            $this->cannotLogin = true;
        }

        $currentDate = Carbon::now();

        $this->checkJoiningDate = true;

        if (is_null(user()->employeeDetail->joining_date) || user()->employeeDetail->joining_date->gt($currentDate)) {
            $this->checkJoiningDate = false;
        }

        $this->viewEventPermission = user()->permission('view_events');
        $this->viewHolidayPermission = user()->permission('view_holiday');
        $this->viewTaskPermission = user()->permission('view_tasks');
        $this->viewTicketsPermission = user()->permission('view_tickets');
        $this->viewLeavePermission = user()->permission('view_leave');
        $this->viewNoticePermission = user()->permission('view_notice');
        $this->editTimelogPermission = user()->permission('edit_timelogs');

        // Getting Attendance setting data

        if (request('start') && request('end') && !is_null($this->viewEventPermission) && $this->viewEventPermission != 'none') {
            $eventData = array();

            $events = Event::with('attendee', 'attendee.user');

            if ($this->viewEventPermission == 'added') {
                $events->where('events.added_by', $this->user->id);
            }
            elseif ($this->viewEventPermission == 'owned' || $this->viewEventPermission == 'both') {
                $events->where('events.added_by', $this->user->id)
                    ->orWhere(function ($q) {
                        $q->whereHas('attendee.user', function ($query) {
                            $query->where('user_id', $this->user->id);
                        });
                    });
            }

            $events = $events->get();

            foreach ($events as $key => $event) {
                $eventData[] = [
                    'id' => $event->id,
                    'title' => $event->event_name,
                    'start' => $event->start_date_time,
                    'end' => $event->end_date_time,
                    'extendedProps' => ['bg_color' => $event->label_color, 'color' => '#fff'],
                ];
            }

            return $eventData;
        }

        $this->totalProjects = Project::select('projects.id')
            ->where('completion_percent', '<>', 100)

            ->join('project_members', 'project_members.project_id', '=', 'projects.id');
        $this->totalProjects = $this->totalProjects->where('project_members.user_id', '=', $this->user->id);

        $this->totalProjects = $this->totalProjects->groupBy('projects.id');
        $this->totalProjects = count($this->totalProjects->get());

        $this->counts = User::select(
            DB::raw('(select IFNULL(sum(project_time_logs.total_minutes),0) from `project_time_logs` where user_id = ' . $this->user->id . ') as totalHoursLogged '),
            DB::raw('(select count(tasks.id) from `tasks` inner join task_users on task_users.task_id=tasks.id where tasks.board_column_id=' . $completedTaskColumn->id . ' and task_users.user_id = ' . $this->user->id . ') as totalCompletedTasks')
        )
            ->first();

        if (!is_null($this->viewNoticePermission) && $this->viewNoticePermission != 'none') {
            if ($this->viewNoticePermission == 'added') {
                $this->notices = Notice::latest()->where('added_by', $this->user->id)
                    ->select('id', 'heading', 'created_at')
                    ->limit(10)
                    ->get();
            }
            elseif ($this->viewNoticePermission == 'owned') {
                $this->notices = Notice::latest()
                    ->select('id', 'heading', 'created_at')
                    ->where(['to' => 'employee', 'department_id' => null])
                    ->orWhere(['department_id' => $this->user->employeeDetails->department_id])
                    ->limit(10)
                    ->get();
            }
            elseif ($this->viewNoticePermission == 'both') {
                $this->notices = Notice::latest()
                    ->select('id', 'heading', 'created_at')
                    ->where('added_by', $this->user->id)
                    ->orWhere(function ($q) {
                        $q->where(['to' => 'employee', 'department_id' => null])
                            ->orWhere(['department_id' => $this->user->employeeDetails->department_id]);
                    })
                    ->limit(10)
                    ->get();
            }
            elseif ($this->viewNoticePermission == 'all') {
                $this->notices = Notice::latest()
                    ->select('id', 'heading', 'created_at')
                    ->limit(10)
                    ->get();
            }
        }

        $this->tickets = Ticket::where(function ($query) {
            $query->where('status', '=', 'open')
                ->orWhere('status', '=', 'pending');
        })
            ->where(function ($query) {
                $query->where('user_id', user()->id)
                    ->orWhere('agent_id', user()->id);
            })
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        $checkTicketAgent = TicketAgentGroups::select('id')->where('agent_id', user()->id)->first();

        if (!is_null($checkTicketAgent)) {
            $this->totalOpenTickets = Ticket::with('agent')->whereHas('agent', function ($q) {
                $q->where('id', user()->id);
            })->where('status', 'open')->count();
        }

        $tasks = $this->pendingTasks = Task::with('activeProject', 'boardColumn', 'labels')
            ->join('task_users', 'task_users.task_id', '=', 'tasks.id')
            ->where('task_users.user_id', $this->user->id)
            ->where('tasks.board_column_id', '<>', $completedTaskColumn->id)
            ->select('tasks.*')
            ->groupBy('tasks.id')
            ->orderBy('tasks.id', 'desc')
            ->get();

        $this->inProcessTasks = $tasks->count();

        $this->dueTasks = $tasks->filter(function ($item) {
            return !is_null($item->due_date) && $item->due_date->endOfDay()->isPast();
        })->count();

        $projects = Project::with('members')
            ->where('completion_percent', '<>', '100')
            ->leftJoin('project_members', 'project_members.project_id', 'projects.id')
            ->leftJoin('users', 'project_members.user_id', 'users.id')
            ->selectRaw('project_members.user_id, projects.deadline as due_date, projects.id')
            ->where('project_members.user_id', $this->user->id)
            ->groupBy('projects.id')
            ->get();

        $projects = $projects->whereNotNull('due_date');

        $this->dueProjects = $projects->filter(function ($value) {
            return now(company()->timezone)->gt($value->due_date);
        })->count();

        // Getting Current Clock-in if exist
        $this->currentClockIn = Attendance::where(DB::raw('DATE(clock_in_time)'), now()->format('Y-m-d'))
            ->select('id', 'clock_in_time', 'clock_out_time')
            ->where('user_id', $this->user->id)
            ->whereNull('clock_out_time')
            ->first();

        $currentDate = now(company()->timezone)->format('Y-m-d');

        $this->checkTodayLeave = Leave::where('status', 'approved')
            ->select('id')
            ->where('leave_date', now(company()->timezone)->toDateString())
            ->where('user_id', user()->id)
            ->where('duration', '<>', 'half day')
            ->first();

        // Check Holiday by date
        $this->checkTodayHoliday = Holiday::where('date', $currentDate)->first();
        $this->myActiveTimer = ProjectTimeLog::with('task', 'user', 'project', 'breaks', 'activeBreak')
            ->where('user_id', user()->id)
            ->whereNull('end_time')
            ->first();

        $currentDay = now(company()->timezone)->format('m-d');

        $this->upcomingBirthdays = EmployeeDetails::whereHas('user', function ($query) {
            return $query->where('status', 'active');
        })
            ->with('user')
            ->select('*', 'date_of_birth', DB::raw('MONTH(date_of_birth) months'), DB::raw('DAY(date_of_birth) as day'))
            ->whereNotNull('date_of_birth')
            ->where(function ($query) use ($currentDay) {
                $query->whereRaw('DATE_FORMAT(`date_of_birth`, "%m-%d") >= "' . $currentDay . '"')->orderBy('date_of_birth');
            })
            ->limit('5')
            ->orderBy('months')
            ->orderBy('day')
            ->get()->values()->all();

        $this->leave = Leave::with('user', 'type')->where('status', 'approved')
            ->where('leave_date', today(company()->timezone)->toDateString())
            ->get();


        $this->workFromHome = Attendance::with('user')
            ->select('id', 'user_id')
            ->where('work_from_type', 'home')
            ->where(DB::raw('DATE(attendances.clock_in_time)'), now()->toDateString())
            ->groupBy('user_id')
            ->get();

        $this->leadAgent = LeadAgent::where('user_id', $this->user->id)->first();

        // Deal Data
        if (!is_null($this->leadAgent)) {

            $this->deals = Deal::select('deals.*', 'pipeline_stages.slug')->with('leadAgent', 'leadStage')->whereHas('leadAgent', function ($q) {
                $q->where('user_id', $this->user->id);
            })->join('pipeline_stages', 'pipeline_stages.id', 'deals.pipeline_stage_id')
            ->get();

            $this->convertedDeals = $this->deals->filter(function ($value) {
                return $value->slug == 'win';
            })->count();

        }

        $now = now(company()->timezone);
        $this->weekStartDate = $now->copy()->startOfWeek($showClockIn->week_start_from);
        $this->weekEndDate = $this->weekStartDate->copy()->addDays(7);
        $this->weekPeriod = CarbonPeriod::create($this->weekStartDate, $this->weekStartDate->copy()->addDays(6)); // Get All Dates from start to end date

        $this->employeeShifts = EmployeeShiftSchedule::where('user_id', user()->id)
            ->whereBetween(DB::raw('DATE(`date`)'), [$this->weekStartDate->format('Y-m-d'), $this->weekEndDate->format('Y-m-d')])
            ->select(DB::raw('DATE_FORMAT(date, "%Y-%m-%d") as dates'), 'employee_shift_schedules.*')
            ->with('shift', 'user', 'requestChange')
            ->get();
        $this->employeeShiftDates = $this->employeeShifts->pluck('dates')->toArray();

        $currentWeekDates = [];
        $weekShifts = [];

        $weekHolidays = Holiday::whereBetween(DB::raw('DATE(`date`)'), [$this->weekStartDate->format('Y-m-d'), $this->weekEndDate->format('Y-m-d')])
            ->select(DB::raw('DATE_FORMAT(`date`, "%Y-%m-%d") as hdate'), 'occassion')
            ->get();

        $holidayDates = $weekHolidays->pluck('hdate')->toArray();

        $weekLeaves = Leave::with('type')
            ->select(DB::raw('DATE_FORMAT(`leave_date`, "%Y-%m-%d") as ldate'), 'leaves.*')
            ->where('user_id', user()->id)
            ->whereBetween(DB::raw('DATE(`leave_date`)'), [$this->weekStartDate->format('Y-m-d'), $this->weekEndDate->format('Y-m-d')])
            ->where('status', 'approved')
            ->where('duration', '<>', 'half day')
            ->get();

        $leaveDates = $weekLeaves->pluck('ldate')->toArray();
        $generalShift = Company::with(['attendanceSetting', 'attendanceSetting.shift'])->first();

        // phpcs:ignore
        for ($i = $this->weekStartDate->copy(); $i < $this->weekEndDate->copy(); $i->addDay()) {
            $date = Carbon::parse($i);
            array_push($currentWeekDates, $date);

            if (in_array($date->toDateString(), $holidayDates)) {

                $leave = [];

                foreach ($weekHolidays as $holiday) {
                    if ($holiday->hdate == $date->toDateString()) {
                        $leave = '<i class="fa fa-star text-warning"></i> ' . $holiday->occassion;
                    }
                }

                array_push($weekShifts, $leave);

            }
            elseif (in_array($date->toDateString(), $leaveDates)) {

                $leave = [];

                foreach ($weekLeaves as $leav) {
                    if ($leav->ldate == $date->toDateString()) {
                        $leave = __('app.onLeave') . ': <span class="badge badge-success" style="background-color:' . $leav->type->color . '">' . $leav->type->type_name . '</span>';
                    }
                }

                array_push($weekShifts, $leave);

            }
            elseif (in_array($date->toDateString(), $this->employeeShiftDates)) {
                $shiftSchedule = [];

                foreach ($this->employeeShifts as $shift) {
                    if ($shift->dates == $date->toDateString()) {
                        $shiftSchedule = $shift;
                    }
                }

                array_push($weekShifts, $shiftSchedule);

            }
            else {
                $defaultShift = ($generalShift && $generalShift->attendanceSetting && $generalShift->attendanceSetting->shift) ? '<span class="badge badge-primary" style="background-color:' . $generalShift->attendanceSetting->shift->color . '">'.$generalShift->attendanceSetting->shift->shift_name.'</span>' : '--';
                array_push($weekShifts, $defaultShift);
            }

        }

        $this->upcomingAnniversaries = EmployeeDetails::whereHas('user', function ($query) {
            return $query->where('status', 'active');
        })
            ->with('user')
            ->select('employee_details.id', 'employee_details.user_id', 'employee_details.joining_date', DB::raw('MONTH(joining_date) months'), DB::raw('DAY(joining_date) as day'))
            ->whereNotNull('joining_date')
            ->where(function ($query) use ($currentDay) {
                $query->whereRaw('DATE_FORMAT(`joining_date`, "%m-%d") = "' . $currentDay . '"')->orderBy('joining_date');
            })
            ->orderBy('months')
            ->orderBy('day')
            ->get()->values()->all();

        $this->currentWeekDates = $currentWeekDates;
        $this->weekShifts = $weekShifts;
        $this->showClockIn = $showClockIn->show_clock_in_button;
        $this->event_filter = explode(',', user()->employeeDetails->calendar_view);
        $this->widgets = DashboardWidget::where('dashboard_type', 'private-dashboard')->get();
        $this->activeWidgets = $this->widgets->filter(function ($value, $key) {
            return $value->status == '1';
        })->pluck('widget_name')->toArray();

        $this->dateWiseTimelogs = ProjectTimeLog::dateWiseTimelogs(now()->toDateString(), user()->id);
        $this->dateWiseTimelogBreak = ProjectTimeLogBreak::dateWiseTimelogBreak(now()->toDateString(), user()->id);

        $this->weekWiseTimelogs = ProjectTimeLog::weekWiseTimelogs($this->weekStartDate->copy()->toDateString(), $this->weekEndDate->copy()->toDateString(), user()->id);
        $this->weekWiseTimelogBreak = ProjectTimeLogBreak::weekWiseTimelogBreak($this->weekStartDate->toDateString(), $this->weekEndDate->toDateString(), user()->id);
        $this->appreciations = Appreciation::with(['award', 'award.awardIcon'])
            ->with(['awardTo' => function($query) {
                return $query->without('clientDetails');
            }])
            ->orderBy('award_date', 'desc')
            ->latest()
            ->limit(5)
            ->get();


        $currentDay = now(company()->timezone)->format('Y-m-d');
        $this->employees = EmployeeDetails::whereHas('user', function ($query) {
            return $query->where('status', 'active');
        })->with(['user' => function($query) {
            return $query->without('clientDetails');
        }]);

        if(in_array('admin', user_roles())) {
            $this->noticePeriod = $this->employees->clone()
                ->whereNotNull('notice_period_end_date')
                ->where('notice_period_end_date', '>=', $currentDay)
                ->orderBy('notice_period_end_date', 'asc')
                ->get();

            $this->probations = $this->employees->clone()
                ->whereNotNull('probation_end_date')
                ->where('probation_end_date', '>=', $currentDay)
                ->orderBy('probation_end_date', 'asc')
                ->get();

            $this->internships = $this->employees->clone()
                ->whereNotNull('internship_end_date')
                ->where('internship_end_date', '>=', $currentDay)
                ->orderBy('internship_end_date', 'asc')
                ->get();

            $this->contracts = $this->employees->clone()
                ->whereNotNull('contract_end_date')
                ->where('contract_end_date', '>=', $currentDay)
                ->orderBy('contract_end_date', 'asc')
                ->get();
        }
        else {
            $this->noticePeriod = $this->employees->clone()
                ->where('user_id', user()->id)
                ->whereNotNull('notice_period_end_date')
                ->where('notice_period_end_date', '>=', $currentDay)
                ->first();

            $this->probation = $this->employees->clone()
                ->where('user_id', user()->id)
                ->whereNotNull('probation_end_date')
                ->where('probation_end_date', '>=', $currentDay)
                ->first();

            $this->internship = $this->employees->clone()
                ->where('user_id', user()->id)
                ->whereNotNull('internship_end_date')
                ->where('internship_end_date', '>=', $currentDay)
                ->first();

            $this->contract = $this->employees->clone()
                ->where('user_id', user()->id)
                ->where('contract_end_date', '>=', $currentDay)
                ->first();
        }

        return view('dashboard.employee.index', $this->data);
    }

    public function clockInModal()
    {
        $showClockIn = AttendanceSetting::first();

        $this->attendanceSettings = $this->attendanceShift($showClockIn);

        $startTimestamp = now()->format('Y-m-d') . ' ' . $this->attendanceSettings->office_start_time;
        $endTimestamp = now()->format('Y-m-d') . ' ' . $this->attendanceSettings->office_end_time;
        $officeStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $startTimestamp, $this->company->timezone);
        $officeEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $endTimestamp, $this->company->timezone);
        $officeStartTime = $officeStartTime->setTimezone('UTC');
        $officeEndTime = $officeEndTime->setTimezone('UTC');

        if ($officeStartTime->gt($officeEndTime)) {
            $officeEndTime->addDay();
        }

        $this->cannotLogin = false;

        if ($showClockIn->employee_clock_in_out == 'yes') {

            if (is_null($this->attendanceSettings->early_clock_in) && !now()->between($officeStartTime, $officeEndTime) && $showClockIn->show_clock_in_button == 'no') {
                $this->cannotLogin = true;
            }
            else {
                $earlyClockIn = Carbon::now(company()->timezone)->addMinutes($this->attendanceSettings->early_clock_in);
                $earlyClockIn = $earlyClockIn->setTimezone('UTC');

                if($earlyClockIn->gte($officeStartTime) || $showClockIn->show_clock_in_button == 'yes'){
                    $this->cannotLogin = false;
                }
                else {
                    $this->cannotLogin = true;
                }
            }

            if (isset($this->cannotLogin) && now()->betweenIncluded($officeStartTime->copy()->subDay(), $officeEndTime->copy()->subDay())) {
                $this->cannotLogin = false;
            }
        }
        else {
            $this->cannotLogin = true;
        }


        $this->shiftAssigned = $this->attendanceSettings;

        $this->attendanceSettings = attendance_setting();
        $this->location = CompanyAddress::all();

        return view('dashboard.employee.clock_in_modal', $this->data);
    }

    public function storeClockIn(ClockInRequest $request)
    {
        $now = now();

        $showClockIn = AttendanceSetting::first();

        $this->attendanceSettings = $this->attendanceShift($showClockIn);

        $startTimestamp = now()->format('Y-m-d') . ' ' . $this->attendanceSettings->office_start_time;
        $endTimestamp = now()->format('Y-m-d') . ' ' . $this->attendanceSettings->office_end_time;
        $officeStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $startTimestamp, $this->company->timezone);
        $officeEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $endTimestamp, $this->company->timezone);

        if ($showClockIn->show_clock_in_button == 'yes') {
            $officeEndTime = now();
        }

        $officeStartTime = $officeStartTime->setTimezone('UTC');
        $officeEndTime = $officeEndTime->setTimezone('UTC');

        if ($officeStartTime->gt($officeEndTime)) {
            $officeEndTime->addDay();
        }

        $this->cannotLogin = false;
        $clockInCount = Attendance::getTotalUserClockInWithTime($officeStartTime, $officeEndTime, $this->user->id);

        if ($showClockIn->employee_clock_in_out == 'yes') {
            if (is_null($this->attendanceSettings->early_clock_in) && !now()->between($officeStartTime, $officeEndTime) && $showClockIn->show_clock_in_button == 'no') {
                $this->cannotLogin = true;
            }
            else {
                $earlyClockIn = Carbon::now(company()->timezone)->addMinutes($this->attendanceSettings->early_clock_in);
                $earlyClockIn = $earlyClockIn->setTimezone('UTC');

                if($earlyClockIn->gte($officeStartTime) || $showClockIn->show_clock_in_button == 'yes'){
                    $this->cannotLogin = false;
                }
                else {
                    $this->cannotLogin = true;
                }
            }

            if ($this->cannotLogin == true && now()->betweenIncluded($officeStartTime->copy()->subDay(), $officeEndTime->copy()->subDay())) {
                $this->cannotLogin = false;
                $clockInCount = Attendance::getTotalUserClockInWithTime($officeStartTime->copy()->subDay(), $officeEndTime->copy()->subDay(), $this->user->id);
            }
        }
        else {
            $this->cannotLogin = true;
        }

        abort_403($this->cannotLogin);

        // Check user by ip
        if (attendance_setting()->ip_check == 'yes') {
            $ips = (array)json_decode(attendance_setting()->ip_address);

            if (!in_array($request->ip(), $ips)) {
                return Reply::error(__('messages.notAnAuthorisedDevice'));
            }
        }

        // Check user by location
        if (attendance_setting()->radius_check == 'yes') {
            $checkRadius = $this->isWithinRadius($request);

            if (!$checkRadius) {
                return Reply::error(__('messages.notAnValidLocation'));
            }
        }

        // Check maximum attendance in a day
        if ($clockInCount < $this->attendanceSettings->clockin_in_day) {

            // Set TimeZone And Convert into timestamp
            $currentTimestamp = $now->setTimezone('UTC');
            $currentTimestamp = $currentTimestamp->timestamp;;

            // Set TimeZone And Convert into timestamp in halfday time
            if ($this->attendanceSettings->halfday_mark_time) {
                $halfDayTimestamp = $now->format('Y-m-d') . ' ' . $this->attendanceSettings->halfday_mark_time;
                $halfDayTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $halfDayTimestamp, $this->company->timezone);
                $halfDayTimestamp = $halfDayTimestamp->setTimezone('UTC');
                $halfDayTimestamp = $halfDayTimestamp->timestamp;
            }


            $timestamp = $now->format('Y-m-d') . ' ' . $this->attendanceSettings->office_start_time;
            $officeStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $timestamp, $this->company->timezone);
            $officeStartTime = $officeStartTime->setTimezone('UTC');

            $lateTime = $officeStartTime->addMinutes($this->attendanceSettings->late_mark_duration);

            $checkTodayAttendance = Attendance::where('user_id', $this->user->id)
                ->where(DB::raw('DATE(attendances.clock_in_time)'), '=', $now->format('Y-m-d'))->first();

            $attendance = new Attendance();
            $attendance->user_id = $this->user->id;
            $attendance->clock_in_time = $now;
            $attendance->clock_in_ip = request()->ip();

            $attendance->working_from = $request->working_from;
            $attendance->location_id = $request->location;
            $attendance->work_from_type = $request->work_from_type;

            if ($now->gt($lateTime) && is_null($checkTodayAttendance)) {
                $attendance->late = 'yes';
            }

            $leave = Leave::where('leave_date', $attendance->clock_in_time->format('Y-m-d'))
                ->where('user_id', $this->user->id)->first();

            if(isset($leave) && !is_null($leave->half_day_type))
            {
                $attendance->half_day = 'yes';
            }
            else
            {
                $attendance->half_day = 'no';
            }


            // Check day's first record and half day time
            if (
                !is_null($this->attendanceSettings->halfday_mark_time)
                && is_null($checkTodayAttendance)
                && isset($halfDayTimestamp)
                && ($currentTimestamp > $halfDayTimestamp)
                && ($showClockIn->show_clock_in_button == 'no')
            ) {
                $attendance->half_day = 'yes';
            }

            $currentLatitude = $request->currentLatitude;
            $currentLongitude = $request->currentLongitude;

            if ($currentLatitude != '' && $currentLongitude != '') {
                $attendance->latitude = $currentLatitude;
                $attendance->longitude = $currentLongitude;
            }

            $attendance->employee_shift_id = $this->attendanceSettings->id;

            $attendance->shift_start_time = $attendance->clock_in_time->toDateString() . ' ' . $this->attendanceSettings->office_start_time;

            if (Carbon::parse($this->attendanceSettings->office_start_time)->gt(Carbon::parse($this->attendanceSettings->office_end_time))) {
                $attendance->shift_end_time = $attendance->clock_in_time->addDay()->toDateString() . ' ' . $this->attendanceSettings->office_end_time;

            }
            else {
                $attendance->shift_end_time = $attendance->clock_in_time->toDateString() . ' ' . $this->attendanceSettings->office_end_time;
            }

            $attendance->save();

            return Reply::successWithData(__('messages.attendanceSaveSuccess'), ['time' => $now->format('h:i A'), 'ip' => $attendance->clock_in_ip, 'working_from' => $attendance->working_from]);
        }

        return Reply::error(__('messages.maxClockin'));
    }

    public function updateClockIn(Request $request)
    {
        $now = now();
        $attendance = Attendance::findOrFail($request->id);
        $this->attendanceSettings = attendance_setting();

        if ($this->attendanceSettings->ip_check == 'yes') {
            $ips = (array)json_decode($this->attendanceSettings->ip_address);

            if (!in_array($request->ip(), $ips)) {
                return Reply::error(__('messages.notAnAuthorisedDevice'));
            }
        }

        $attendance->clock_out_time = $now;
        $attendance->clock_out_ip = request()->ip();
        $attendance->save();

        return Reply::success(__('messages.attendanceSaveSuccess'));
    }

    /**
     * Calculate distance between two geo coordinates using Haversine formula and then compare
     * it with $radius.
     *
     * If distance is less than the radius means two points are close enough hence return true.
     * Else return false.
     *
     * @param Request $request
     *
     * @return boolean
     */
    private function isWithinRadius($request)
    {
        $radius = attendance_setting()->radius;
        $currentLatitude = $request->currentLatitude;
        $currentLongitude = $request->currentLongitude;
        $location = CompanyAddress::find($request->location);

        $latFrom = deg2rad($location->latitude);
        $latTo = deg2rad($currentLatitude);

        $lonFrom = deg2rad($location->longitude);
        $lonTo = deg2rad($currentLongitude);

        $theta = $lonFrom - $lonTo;

        $dist = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($theta);
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $distance = $dist * 60 * 1.1515 * 1609.344;

        return $distance <= $radius;
    }

    public function attendanceShift($defaultAttendanceSettings)
    {
        $checkPreviousDayShift = EmployeeShiftSchedule::with('shift')->where('user_id', user()->id)
            ->where('date', now(company()->timezone)->subDay()->toDateString())
            ->first();

        $checkTodayShift = EmployeeShiftSchedule::with('shift')->where('user_id', user()->id)
            ->where('date', now(company()->timezone)->toDateString())
            ->first();

        $backDayFromDefault = Carbon::parse(now(company()->timezone)->subDay()->format('Y-m-d') . ' ' . $defaultAttendanceSettings->office_start_time);

        $backDayToDefault = Carbon::parse(now(company()->timezone)->subDay()->format('Y-m-d') . ' ' . $defaultAttendanceSettings->office_end_time);

        if ($backDayFromDefault->gt($backDayToDefault)) {
            $backDayToDefault->addDay();
        }

        $nowTime = Carbon::createFromFormat('Y-m-d H:i:s', now(company()->timezone)->toDateTimeString(), 'UTC');

        if ($checkPreviousDayShift && $nowTime->betweenIncluded($checkPreviousDayShift->shift_start_time, $checkPreviousDayShift->shift_end_time)) {
            $attendanceSettings = $checkPreviousDayShift;

        }
        else if ($nowTime->betweenIncluded($backDayFromDefault, $backDayToDefault)) {
            $attendanceSettings = $defaultAttendanceSettings;

        }
        else if ($checkTodayShift &&
            ($nowTime->betweenIncluded($checkTodayShift->shift_start_time, $checkTodayShift->shift_end_time)
            || $nowTime->gt($checkTodayShift->shift_end_time)
            || (!$nowTime->betweenIncluded($checkTodayShift->shift_start_time, $checkTodayShift->shift_end_time) && $defaultAttendanceSettings->show_clock_in_button == 'no'))
        ) {
            $attendanceSettings = $checkTodayShift;
        }
        else if ($checkTodayShift && !is_null($checkTodayShift->shift->early_clock_in))
        {
            $attendanceSettings = $checkTodayShift;
        }
        else {
            $attendanceSettings = $defaultAttendanceSettings;
        }

        return $attendanceSettings->shift;

    }

}
