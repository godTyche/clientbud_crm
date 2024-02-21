<?php

namespace App\DataTables;

use App\DataTables\BaseDataTable;
use App\Models\ProjectTimeLog;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Button;

class TimeLogReportDataTable extends BaseDataTable
{

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    protected $timeLogFor;
    protected $isTask;
    private $editTimelogPermission;
    private $deleteTimelogPermission;

    public function __construct()
    {
        parent::__construct();
        $this->editTimelogPermission = user()->permission('edit_timelogs');
        $this->deleteTimelogPermission = user()->permission('delete_timelogs');
    }

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('employee_name', function ($row) {
                return $row->user->name;
            })
            ->addColumn('total_minutes', function ($row) {
                return $row->total_minutes;
            })
            ->editColumn('name', function ($row) {
                return view('components.employee', [
                    'user' => $row->user
                ]);
            })
            ->editColumn('start_time', function ($row) {
                return $row->start_time->timezone($this->company->timezone)->translatedFormat($this->company->date_format . ' ' . $this->company->time_format);
            })
            ->editColumn('end_time', function ($row) {
                if (!is_null($row->end_time)) {
                    return $row->end_time->timezone($this->company->timezone)->translatedFormat($this->company->date_format . ' ' . $this->company->time_format);
                }
                else {
                    return "<span class='badge badge-primary'>" . __('app.active') . '</span>';
                }
            })
            ->editColumn('total_hours', function ($row) {
                if (is_null($row->end_time)) {

                    $totalMinutes = now()->diffInMinutes($row->start_time) - $row->breaks->sum('total_minutes');

                    $timeLog = CarbonInterval::formatHuman($totalMinutes - $row->breaks->sum('total_minutes')); /** @phpstan-ignore-line */
                    $timeLog .= ' <i data-toggle="tooltip" data-original-title="' . __('app.active') . '" class="fa fa-hourglass-start" ></i>';
                }
                else {
                    $totalMinutes = $row->total_minutes - $row->breaks->sum('total_minutes');
                    $timeLog = CarbonInterval::formatHuman($totalMinutes - $row->breaks->sum('total_minutes')); /** @phpstan-ignore-line */

                    if ($row->approved) {
                        $timeLog .= ' <i data-toggle="tooltip" data-original-title="' . __('app.approved') . '" class="fa fa-check-circle text-primary"></i>';
                    }
                }

                return $timeLog;
            })
            ->editColumn('earnings', function ($row) {
                if (is_null($row->hourly_rate)) {
                    return '--';
                }

                return currency_format($row->earnings, company()->currency_id);
            })
            ->editColumn('project', function ($row) {
                $project = '';

                if (!is_null($row->project_id)) {
                    $project .= '<a href="' . route('projects.show', [$row->project_id]) . '" class="text-darkest-grey ">' . $row->project->project_name . '</a>';
                }

                return $project;
            })
            ->editColumn('task', function ($row) {

                $task = '';

                if (!is_null($row->task_id)) {
                    $task .= '<a href="' . route('tasks.show', [$row->task_id]) . '" class="text-darkest-grey openRightModal">' . $row->task->heading . '</a>';
                }

                return $task;
            })
            ->editColumn('task_project', function ($row) {
                $name = '';

                if (!is_null($row->project_id) && !is_null($row->task_id)) {
                    $name .= '<h5 class="f-13 text-darkest-grey"><a href="' . route('tasks.show', [$row->task_id]) . '" class="openRightModal">' . $row->task->heading . '</a></h5><div class="text-muted">' . $row->project->project_name . '</div>';
                }
                else if (!is_null($row->project_id)) {
                    $name .= '<a href="' . route('projects.show', [$row->project_id]) . '" class="text-darkest-grey ">' . $row->project->project_name . '</a>';
                }
                else if (!is_null($row->task_id)) {
                    $name .= '<a href="' . route('tasks.show', [$row->task_id]) . '" class="text-darkest-grey openRightModal">' . $row->task->heading . '</a>';
                }

                return $name;
            })
            ->addIndexColumn()
            ->setRowId(function ($row) {
                return 'row-' . $row->id;
            })
            ->editColumn('short_code', function ($row) {

                if (is_null($row->task_short_code)) {
                    return ' -- ';
                }

                return '<a href="' . route('tasks.show', [$row->task_id]) . '" class="text-darkest-grey openRightModal">' . $row->task_short_code . '</a>';
            })
            ->rawColumns(['end_time', 'action', 'project', 'task', 'task_project', 'name', 'total_hours', 'total_minutes', 'check', 'short_code'])
            ->removeColumn('project_id')
            ->removeColumn('task_id');
    }

    /**
     * @param ProjectTimeLog $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(ProjectTimeLog $model)
    {
        $request = $this->request();

        $projectId = $request->projectId;
        $employee = $request->employee;
        $client = $request->client;
        $taskId = $request->taskId;
        $approved = $request->approved;
        $invoice = $request->invoice;


        $model = $model->with('user', 'project', 'task', 'breaks', 'activeBreak');

        $model = $model->join('users', 'users.id', '=', 'project_time_logs.user_id')
            ->join('employee_details', 'users.id', '=', 'employee_details.user_id')
            ->leftJoin('designations', 'employee_details.designation_id', '=', 'designations.id')
            ->leftJoin('tasks', 'tasks.id', '=', 'project_time_logs.task_id')
            ->leftJoin('projects', 'projects.id', '=', 'project_time_logs.project_id');

        $model = $model->select('project_time_logs.id', 'project_time_logs.start_time', 'project_time_logs.end_time', 'project_time_logs.total_hours', 'project_time_logs.total_minutes', 'project_time_logs.memo', 'project_time_logs.user_id', 'project_time_logs.project_id', 'project_time_logs.task_id', 'users.name', 'users.image', 'project_time_logs.hourly_rate', 'project_time_logs.earnings', 'project_time_logs.approved', 'tasks.heading', 'projects.project_name', 'projects.client_id', 'designations.name as designation_name', 'tasks.task_short_code');


        if ($request->startDate !== null && $request->startDate != 'null' && $request->startDate != '') {
            $startDate = Carbon::createFromFormat($this->company->date_format, $request->startDate)->toDateString();

            if (!is_null($startDate)) {
                $model = $model->where(DB::raw('DATE(project_time_logs.`start_time`)'), '>=', $startDate);
            }
        }

        if ($request->endDate !== null && $request->endDate != 'null' && $request->endDate != '') {
            $endDate = Carbon::createFromFormat($this->company->date_format, $request->endDate)->toDateString();

            if (!is_null($endDate)) {
                $model = $model->where(function ($query) use ($endDate) {
                    $query->where(DB::raw('DATE(project_time_logs.`end_time`)'), '<=', $endDate);
                });
            }
        }

        if (!is_null($employee) && $employee !== 'all') {
            $model->where('project_time_logs.user_id', $employee);
        }

        if (!is_null($client) && $client !== 'all') {
            $model->where('projects.client_id', $client);
        }

        if (!is_null($projectId) && $projectId !== 'all') {
            $model->where('project_time_logs.project_id', '=', $projectId);
        }

        if (!is_null($taskId) && $taskId !== 'all') {
            $model->where('project_time_logs.task_id', '=', $taskId);
        }

        if (!is_null($approved) && $approved !== 'all') {
            if ($approved == 2) {
                $model->whereNull('project_time_logs.end_time');
            }
            else {
                $model->where('project_time_logs.approved', '=', $approved);
            }
        }

        if (!is_null($invoice) && $invoice !== 'all') {
            if ($invoice == 0) {
                $model->where('project_time_logs.invoice_id', '=', null);
            }
            else if ($invoice == 1) {
                $model->where('project_time_logs.invoice_id', '!=', null);
            }
        }

        if ($request->searchText != '') {
            $model->where(function ($query) {
                $query->where('tasks.heading', 'like', '%' . request('searchText') . '%')
                    ->orWhere('project_time_logs.memo', 'like', '%' . request('searchText') . '%')
                    ->orWhere('projects.project_name', 'like', '%' . request('searchText') . '%')
                    ->orWhere('tasks.task_short_code', 'like', '%' . request('searchText') . '%');
            });
        }

        $model->orderBy('project_time_logs.id', 'desc');

        return $model;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        $dataTable = $this->setBuilder('timelogs-table', 5)
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["timelogs-table"].buttons().container()
                     .appendTo( "#table-actions")
                 }',
                'fnDrawCallback' => 'function( oSettings ) {
                   //
                   $(".select-picker").selectpicker();
                 }',
            ]);

        if (canDataTableExport()) {
            $dataTable->buttons(Button::make(['extend' => 'excel', 'text' => '<i class="fa fa-file-export"></i> ' . trans('app.exportExcel')]));
        }

        return $dataTable;
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            '#' => ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'visible' => false, 'title' => '#'],
            __('app.id') => ['data' => 'id', 'name' => 'id', 'visible' => false, 'title' => __('app.id')],
            __('modules.taskCode') => ['data' => 'short_code', 'name' => 'task_short_code', 'title' => __('modules.taskCode')],
            __('app.project') => ['data' => 'project', 'visible' => false, 'title' => __('app.project')],
            __('app.task') => ['data' => 'task', 'visible' => false, 'title' => __('app.task')],
            __('app.tasks') => ['data' => 'task_project', 'name' => 'heading', 'width' => '200', 'exportable' => false, 'title' => __('app.tasks')],
            __('app.employee') => ['data' => 'name', 'name' => 'users.name', 'exportable' => false, 'title' => __('app.employee')],
            __('app.name') => ['data' => 'employee_name', 'name' => 'name', 'visible' => false, 'title' => __('app.name')],
            __('modules.timeLogs.startTime') => ['data' => 'start_time', 'name' => 'start_time', 'title' => __('modules.timeLogs.startTime')],
            __('modules.timeLogs.endTime') => ['data' => 'end_time', 'name' => 'end_time', 'title' => __('modules.timeLogs.endTime')],
            __('modules.timeLogs.totalHours') => ['data' => 'total_hours', 'name' => 'total_hours', 'title' => __('modules.timeLogs.totalHours')],
            __('modules.timeLogs.totalMinutes') => ['data' => 'total_minutes', 'visible' => false, 'title' => __('modules.timeLogs.totalMinutes')],
            __('app.earnings') => ['data' => 'earnings', 'name' => 'earnings', 'title' => __('app.earnings')]
        ];
    }

}
