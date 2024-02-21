<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Models\AttendanceSetting;
use App\Models\DashboardWidget;
use App\Models\EmployeeDetails;
use App\Models\Event;
use App\Models\Holiday;
use App\Models\LeadPipeline;
use App\Models\Leave;
use App\Models\ProjectTimeLog;
use App\Models\ProjectTimeLogBreak;
use App\Models\Task;
use App\Models\TaskboardColumn;
use App\Models\Ticket;
use App\Traits\ClientDashboard;
use App\Traits\ClientPanelDashboard;
use App\Traits\CurrencyExchange;
use App\Traits\EmployeeDashboard;
use App\Traits\FinanceDashboard;
use App\Traits\HRDashboard;
use App\Traits\OverviewDashboard;
use App\Traits\ProjectDashboard;
use App\Traits\TicketDashboard;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Froiden\Envato\Traits\AppBoot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Nwidart\Modules\Facades\Module;

class DashboardController extends AccountBaseController
{

    use AppBoot, CurrencyExchange, OverviewDashboard, EmployeeDashboard, ProjectDashboard, ClientDashboard, HRDashboard, TicketDashboard, FinanceDashboard, ClientPanelDashboard;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.dashboard';
        $this->middleware(function ($request, $next) {
            $this->viewOverviewDashboard = user()->permission('view_overview_dashboard');
            $this->viewProjectDashboard = user()->permission('view_project_dashboard');
            $this->viewClientDashboard = user()->permission('view_client_dashboard');
            $this->viewHRDashboard = user()->permission('view_hr_dashboard');
            $this->viewTicketDashboard = user()->permission('view_ticket_dashboard');
            $this->viewFinanceDashboard = user()->permission('view_finance_dashboard');

            return $next($request);
        });

    }

    /**
     * @return array|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response|mixed|void
     */
    public function index()
    {
        $this->isCheckScript();

        if (in_array('employee', user_roles())) {
            return $this->employeeDashboard();
        }

        if (in_array('client', user_roles())) {
            return $this->clientPanelDashboard();
        }
    }

    public function widget(Request $request, $dashboardType)
    {
        $data = $request->all();
        unset($data['_token']);
        DashboardWidget::where('status', 1)->where('dashboard_type', $dashboardType)->update(['status' => 0]);

        foreach ($data as $key => $widget) {
            DashboardWidget::where('widget_name', $key)->where('dashboard_type', $dashboardType)->update(['status' => 1]);
        }

        return Reply::success(__('messages.updateSuccess'));
    }

    public function checklist()
    {
        if (in_array('admin', user_roles())) {
            $this->isCheckScript();

            return view('dashboard.checklist', $this->data);
        }
    }

    /**
     * @return array|\Illuminate\Http\Response
     */
    public function memberDashboard()
    {
        abort_403(!in_array('employee', user_roles()));

        return $this->employeeDashboard();
    }

    public function advancedDashboard()
    {

        if (in_array('admin', user_roles()) || $this->sidebarUserPermissions['view_overview_dashboard'] == 4
            || $this->sidebarUserPermissions['view_project_dashboard'] == 4
            || $this->sidebarUserPermissions['view_client_dashboard'] == 4
            || $this->sidebarUserPermissions['view_hr_dashboard'] == 4
            || $this->sidebarUserPermissions['view_ticket_dashboard'] == 4
            || $this->sidebarUserPermissions['view_finance_dashboard'] == 4) {

            $tab = request('tab');

            switch ($tab) {
            case 'project':
                $this->projectDashboard();
                break;
            case 'client':
                $this->clientDashboard();
                break;
            case 'hr':
                $this->hrDashboard();
                break;
            case 'ticket':
                $this->ticketDashboard();
                break;
            case 'finance':
                $this->financeDashboard();
                break;
            default:
                if (in_array('admin', user_roles()) || $this->sidebarUserPermissions['view_overview_dashboard'] == 4) {
                    $this->activeTab = $tab ?: 'overview';
                    $this->overviewDashboard();

                }
                elseif ($this->sidebarUserPermissions['view_project_dashboard'] == 4) {
                    $this->activeTab = $tab ?: 'project';
                    $this->projectDashboard();

                }
                elseif ($this->sidebarUserPermissions['view_client_dashboard'] == 4) {
                    $this->activeTab = $tab ?: 'client';
                    $this->clientDashboard();

                }
                elseif ($this->sidebarUserPermissions['view_hr_dashboard'] == 4) {
                    $this->activeTab = $tab ?: 'hr';
                    $this->hrDashboard();

                }
                elseif ($this->sidebarUserPermissions['view_finance_dashboard'] == 4) {
                    $this->activeTab = $tab ?: 'finance';
                    $this->ticketDashboard();

                }
                else if ($this->sidebarUserPermissions['view_ticket_dashboard'] == 4) {
                    $this->activeTab = $tab ?: 'finance';
                    $this->financeDashboard();
                }

                break;
            }

            if (request()->ajax()) {
                $html = view($this->view, $this->data)->render();

                return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
            }

            if (!isset($this->activeTab)) {
                $this->activeTab = $tab ?: 'overview';
            }

            return view('dashboard.admin', $this->data);
        }
    }

    public function accountUnverified()
    {
        return view('dashboard.unverified', $this->data);
    }

    public function weekTimelog()
    {
        $now = now(company()->timezone);
        $attndcSetting = AttendanceSetting::first();
        $this->timelogDate = $timelogDate = Carbon::parse(request()->date);
        $this->weekStartDate = $now->copy()->startOfWeek($attndcSetting->week_start_from);
        $this->weekEndDate = $this->weekStartDate->copy()->addDays(7);
        $this->weekPeriod = CarbonPeriod::create($this->weekStartDate, $this->weekStartDate->copy()->addDays(6)); // Get All Dates from start to end date

        $this->dateWiseTimelogs = ProjectTimeLog::dateWiseTimelogs($timelogDate->toDateString(), user()->id);
        $this->dateWiseTimelogBreak = ProjectTimeLogBreak::dateWiseTimelogBreak($timelogDate->toDateString(), user()->id);

        $this->weekWiseTimelogs = ProjectTimeLog::weekWiseTimelogs($this->weekStartDate->copy()->toDateString(), $this->weekEndDate->copy()->toDateString(), user()->id);
        $this->weekWiseTimelogBreak = ProjectTimeLogBreak::weekWiseTimelogBreak($this->weekStartDate->toDateString(), $this->weekEndDate->toDateString(), user()->id);

        $html = view('dashboard.employee.week_timelog', $this->data)->render();

        return Reply::dataOnly(['html' => $html]);
    }

    public function privateCalendar()
    {
        if (request()->filter) {
            $employee_details = EmployeeDetails::where('user_id', user()->id)->first();
            $employee_details->calendar_view = (request()->filter != false) ? request()->filter : null;
            $employee_details->save();
            session()->forget('user');
        }

        $startDate = Carbon::parse(request('start'));
        $endDate = Carbon::parse(request('end'));

        // get calendar view current logined user
        $calendar_filter_array = explode(',', user()->employeeDetails->calendar_view);

        $eventData = array();

        if (!is_null(user()->permission('view_events')) && user()->permission('view_events') != 'none') {

            if (in_array('events', $calendar_filter_array)) {
                // Events
                $model = Event::with('attendee', 'attendee.user');

                $model->where(function ($query) {
                    $query->whereHas('attendee', function ($query) {
                        $query->where('user_id', user()->id);
                    });
                    $query->orWhere('added_by', user()->id);
                });

                $model->whereBetween('start_date_time', [$startDate->toDateString(), $endDate->toDateString()]);

                $events = $model->get();


                foreach ($events as $event) {
                    $eventData[] = [
                        'id' => $event->id,
                        'title' => $event->event_name,
                        'start' => $event->start_date_time,
                        'end' => $event->end_date_time,
                        'event_type' => 'event',
                        'extendedProps' => ['bg_color' => $event->label_color, 'color' => '#fff', 'icon' => 'fa-calendar']
                    ];
                }
            }

        }

        if (!is_null(user()->permission('view_holiday')) && user()->permission('view_holiday') != 'none') {
            if (in_array('holiday', $calendar_filter_array)) {
                // holiday
                $holidays = Holiday::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])->get();

                foreach ($holidays as $holiday) {
                    $eventData[] = [
                        'id' => $holiday->id,
                        'title' => $holiday->occassion,
                        'start' => $holiday->date,
                        'end' => $holiday->date,
                        'event_type' => 'holiday',
                        'extendedProps' => ['bg_color' => '#1d82f5', 'color' => '#fff', 'icon' => 'fa-star']
                    ];
                }
            }

        }

        if (!is_null(user()->permission('view_tasks')) && user()->permission('view_tasks') != 'none') {

            if (in_array('task', $calendar_filter_array)) {
                // tasks
                $completedTaskColumn = TaskboardColumn::completeColumn();
                $tasks = Task::with('boardColumn')
                    ->where('board_column_id', '<>', $completedTaskColumn->id)
                    ->whereHas('users', function ($query) {
                        $query->where('user_id', user()->id);
                    })
                    ->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween(DB::raw('DATE(tasks.`due_date`)'), [$startDate->toDateString(), $endDate->toDateString()]);

                        $q->orWhereBetween(DB::raw('DATE(tasks.`start_date`)'), [$startDate->toDateString(), $endDate->toDateString()]);
                    })->get();

                foreach ($tasks as $task) {
                    $eventData[] = [
                        'id' => $task->id,
                        'title' => $task->heading,
                        'start' => $task->start_date,
                        'end' => $task->due_date ?: $task->start_date,
                        'event_type' => 'task',
                        'extendedProps' => ['bg_color' => $task->boardColumn->label_color, 'color' => '#fff', 'icon' => 'fa-list']
                    ];
                }
            }
        }

        if (!is_null(user()->permission('view_tickets')) && user()->permission('view_tickets') != 'none') {

            if (in_array('tickets', $calendar_filter_array)) {
                // tickets
                $tickets = Ticket::where('user_id', user()->id)
                    ->whereBetween(DB::raw('DATE(tickets.`updated_at`)'), [$startDate->toDateTimeString(), $endDate->endOfDay()->toDateTimeString()])->get();

                foreach ($tickets as $key => $ticket) {
                    $eventData[] = [
                        'id' => $ticket->ticket_number,
                        'title' => $ticket->subject,
                        'start' => $ticket->updated_at,
                        'end' => $ticket->updated_at,
                        'event_type' => 'ticket',
                        'extendedProps' => ['bg_color' => '#1d82f5', 'color' => '#fff', 'icon' => 'fa-ticket-alt']
                    ];
                }
            }

        }

        if (!is_null(user()->permission('view_leave')) && user()->permission('view_leave') != 'none') {

            if (in_array('leaves', $calendar_filter_array)) {
                // approved leaves of all emoloyees with employee name
                $leaves = Leave::join('leave_types', 'leave_types.id', 'leaves.leave_type_id')
                    ->where('leaves.status', 'approved')
                    ->select('leaves.id', 'leaves.leave_date', 'leaves.status', 'leave_types.type_name', 'leave_types.color', 'leaves.leave_date', 'leaves.duration', 'leaves.status', 'leaves.user_id')
                    ->with('user')
                    ->whereBetween(DB::raw('DATE(leaves.`leave_date`)'), [$startDate->toDateString(), $endDate->toDateString()])
                    ->get();

                foreach ($leaves as $leave) {
                    $duration = ($leave->duration == 'half day') ? '( ' . __('app.halfday') . ' )' : '';

                    $eventData[] = [
                        'id' => $leave->id,
                        'title' => $duration . ' ' . $leave->user->name,
                        'start' => $leave->leave_date->toDateString(),
                        'end' => $leave->leave_date->toDateString(),
                        'event_type' => 'leave',
                        /** @phpstan-ignore-next-line */
                        'extendedProps' => ['name' => 'Leave : ' . $leave->user->name, 'bg_color' => $leave->color, 'color' => '#fff', 'icon' => 'fa-plane-departure']
                    ];
                }
            }
        }

        return $eventData;
    }

    public function getLeadStage($pipelineId)
    {
        $this->startDate = (request('startDate') != '') ? Carbon::createFromFormat($this->company->date_format, request('startDate')) : now($this->company->timezone)->startOfMonth();
        $this->endDate = (request('endDate') != '') ? Carbon::createFromFormat($this->company->date_format, request('endDate')) : now($this->company->timezone);
        $startDate = $this->startDate->toDateString();
        $endDate = $this->endDate->toDateString();

        $this->leadPipelines = LeadPipeline::all();

        $this->leadStatusChart = $this->leadStatusChart($startDate, $endDate, $pipelineId);

        $html = view('dashboard.ajax.lead-by-pipeline', $this->data)->render();
        return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
    }

}
