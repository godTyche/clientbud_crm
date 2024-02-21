<?php

namespace App\DataTables;

use App\DataTables\BaseDataTable;
use App\Models\ProjectTimeLogBreak;
use App\Models\Task;
use App\Models\TaskboardColumn;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;

class ArchiveTasksDataTable extends BaseDataTable
{

    private $editTaskPermission;
    private $deleteTaskPermission;
    private $viewTaskPermission;
    private $changeStatusPermission;
    private $viewUnassignedTasksPermission;

    public function __construct()
    {
        parent::__construct();

        $this->editTaskPermission = user()->permission('edit_tasks');
        $this->deleteTaskPermission = user()->permission('delete_tasks');
        $this->viewTaskPermission = user()->permission('view_tasks');
        $this->changeStatusPermission = user()->permission('change_status');
        $this->viewUnassignedTasksPermission = user()->permission('view_unassigned_tasks');
    }

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $taskBoardColumns = TaskboardColumn::orderBy('priority', 'asc')->get();

        return datatables()
            ->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="select-table-row" id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" onclick="dataTableRowCheck(' . $row->id . ')">';
            })
            ->addColumn('action', function ($row) {
                $taskUsers = $row->users->pluck('id')->toArray();

                $action = '<div class="task_view">

                    <div class="dropdown">
                        <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                            id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-options-vertical icons"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '" tabindex="0">';

                $action .= '<a href="' . route('tasks.show', [$row->id]) . '" class="dropdown-item openRightModal"><i class="fa fa-eye mr-2"></i>' . __('app.view') . '</a>';

                if ($this->editTaskPermission == 'all'
                    || ($this->editTaskPermission == 'owned' && in_array(user()->id, $taskUsers))
                    || ($this->editTaskPermission == 'added' && $row->added_by == user()->id)
                    || ($row->project_admin == user()->id)
                    || ($this->editTaskPermission == 'both' && (in_array(user()->id, $taskUsers) || $row->added_by == user()->id))
                ) {
                    $action .= '<a class="dropdown-item openRightModal" href="' . route('tasks.edit', [$row->id]) . '">
                                <i class="fa fa-edit mr-2"></i>
                                ' . trans('app.edit') . '
                            </a>';
                }

                if ($this->deleteTaskPermission == 'all'
                    || ($this->deleteTaskPermission == 'owned' && in_array(user()->id, $taskUsers))
                    || ($this->deleteTaskPermission == 'added' && $row->added_by == user()->id)
                    || ($row->project_admin == user()->id)
                    || ($this->deleteTaskPermission == 'both' && (in_array(user()->id, $taskUsers) || $row->added_by == user()->id))
                ) {
                    $action .= '<a class="dropdown-item delete-table-row" href="javascript:;" data-user-id="' . $row->id . '">
                                <i class="fa fa-trash mr-2"></i>
                                ' . trans('app.delete') . '
                            </a>';
                }

                if ($this->editTaskPermission == 'all'
                    || ($this->editTaskPermission == 'owned' && in_array(user()->id, $taskUsers))
                    || ($this->editTaskPermission == 'added' && $row->added_by == user()->id)
                    || ($this->editTaskPermission == 'both' && (in_array(user()->id, $taskUsers) || $row->added_by == user()->id))
                ) {
                    $action .= '<a class="dropdown-item openRightModal" href="' . route('tasks.create') . '?duplicate_task=' . $row->id . '">
                                <i class="fa fa-clone"></i>
                                ' . trans('app.duplicate') . '
                            </a>';
                }

                $action .= '</div>
                    </div>
                </div>';

                return $action;
            })
            ->editColumn('due_date', function ($row) {
                if (is_null($row->due_date)) {
                    return '--';
                }

                if ($row->due_date->endOfDay()->isPast()) {
                    return '<span class="text-danger">' . $row->due_date->translatedFormat($this->company->date_format) . '</span>';
                }
                elseif ($row->due_date->isToday()) {
                    return '<span class="text-success">' . __('app.today') . '</span>';
                }

                return '<span >' . $row->due_date->translatedFormat($this->company->date_format) . '</span>';
            })
            ->editColumn('users', function ($row) {
                if (count($row->users) == 0) {
                    return '--';
                }

                $key = '';
                $members = '<div class="position-relative">';

                foreach ($row->users as $key => $member) {
                    if ($key < 4) {
                        $img = '<img data-toggle="tooltip" data-original-title="' . $member->name . '" src="' . $member->image_url . '">';
                        $position = $key > 0 ? 'position-absolute' : '';

                        $members .= '<div class="taskEmployeeImg rounded-circle ' . $position . '" style="left:  ' . ($key * 13) . 'px"><a href="' . route('employees.show', $member->id) . '">' . $img . '</a></div> ';
                    }
                }

                if (count($row->users) > 4 && $key) {
                    $members .= '<div class="taskEmployeeImg more-user-count text-center rounded-circle border bg-amt-grey position-absolute" style="left:  ' . (($key - 1) * 13) . 'px"><a href="' . route('tasks.show', [$row->id]) . '" class="text-dark f-10">+' . (count($row->users) - 4) . '</a></div> ';
                }

                $members .= '</div>';

                return $members;
            })
            ->addColumn('name', function ($row) {
                $members = [];

                foreach ($row->users as $member) {
                    $members[] = $member->name;
                }

                return implode(',', $members);
            })
            ->addColumn('timer', function ($row) {
                if ($row->boardColumn->slug != 'completed' && !is_null($row->is_task_user)) {
                    if (is_null($row->userActiveTimer)) {
                        return '<a href="javascript:;" class="text-primary btn border f-15 start-timer" data-task-id="' . $row->id . '" data-toggle="tooltip" data-original-title="' . __('modules.timeLogs.startTimer') . '"><i class="bi bi-play-circle-fill"></i></a>';

                    }
                    else {

                        if (is_null($row->userActiveTimer->activeBreak)) {
                            $timerButtons = '<div class="btn-group" role="group">';
                            $timerButtons .= '<a href="javascript:;" class="text-secondary btn border f-15 pause-timer" data-time-id="' . $row->userActiveTimer->id . '" data-toggle="tooltip" data-original-title="' . __('modules.timeLogs.pauseTimer') . '"><i class="bi bi-pause-circle-fill"></i></a>';

                            $timerButtons .= '<a href="javascript:;" class="text-secondary btn border f-15 stop-timer" data-time-id="' . $row->userActiveTimer->id . '" data-toggle="tooltip" data-original-title="' . __('modules.timeLogs.stopTimer') . '"><i class="bi bi-stop-circle-fill"></i></a>';
                            $timerButtons .= '</div>';

                            return $timerButtons;

                        }
                        else {
                            return '<a href="javascript:;" class="text-secondary btn border f-15 resume-timer" data-time-id="' . $row->userActiveTimer->activeBreak->id . '" data-toggle="tooltip" data-original-title="' . __('modules.timeLogs.resumeTimer') . '"><i class="bi bi-play-circle-fill"></i></a>';
                        }

                    }
                }
            })
            ->editColumn('clientName', function ($row) {
                return ($row->clientName) ? $row->clientName : '-';
            })
            ->addColumn('task', function ($row) {
                return $row->heading;
            })
            ->addColumn('timeLogged', function ($row) {

                $timeLog = '--';

                if ($row->timeLogged) {
                    $totalMinutes = $row->timeLogged->sum('total_minutes');

                    $breakMinutes = ProjectTimeLogBreak::taskBreakMinutes($row->id);
                    $totalMinutes = $totalMinutes - $breakMinutes;
                    $timeLog = CarbonInterval::formatHuman($totalMinutes); /** @phpstan-ignore-line */
                }

                return $timeLog;
            })
            ->editColumn('heading', function ($row) {
                $labels = $private = $pin = $timer = '';

                if ($row->is_private) {
                    $private = '<span class="badge badge-secondary mr-1"><i class="fa fa-lock"></i> ' . __('app.private') . '</span>';
                }

                if (($row->pinned_task)) {
                    $pin = '<span class="badge badge-secondary mr-1"><i class="fa fa-thumbtack"></i> ' . __('app.pinned') . '</span>';
                }

                if ($row->active_timer_all_count > 1) {
                    $timer .= '<span class="badge badge-primary mr-1" ><i class="fa fa-clock"></i> ' . $row->active_timer_all_count . ' ' . __('modules.projects.activeTimers') . '</span>';
                }

                if ($row->activeTimer && $row->active_timer_all_count == 1) {
                    $timer .= '<span class="badge badge-primary mr-1" data-toggle="tooltip" data-original-title="' . __('modules.projects.activeTimers') . '" ><i class="fa fa-clock"></i> ' . $row->activeTimer->timer . '</span>';
                }

                foreach ($row->labels as $label) {
                    $labels .= '<span class="badge badge-secondary mr-1" style="background-color: ' . $label->label_color . '">' . $label->label_name . '</span>';
                }

                return '<div class="media align-items-center">
                        <div class="media-body">
                    <h5 class="mb-0 f-13 text-darkest-grey"><a href="' . route('tasks.show', [$row->id]) . '" class="openRightModal">' . $row->heading . '</a></h5>
                    <p class="mb-0">' . $private . ' ' . $pin . ' ' . $timer . ' ' . $labels . '</p>
                    </div>
                  </div>';
            })
            ->editColumn('board_column', function ($row) use ($taskBoardColumns) {
                $taskUsers = $row->users->pluck('id')->toArray();

                if (
                    $this->changeStatusPermission == 'all'
                    || ($this->changeStatusPermission == 'added' && $row->added_by == user()->id)
                    || ($this->changeStatusPermission == 'owned' && in_array(user()->id, $taskUsers))
                    || ($this->changeStatusPermission == 'both' && (in_array(user()->id, $taskUsers) || $row->added_by == user()->id))
                    || ($row->project_admin == user()->id)
                ) {
                    $status = '<select class="form-control select-picker change-status" data-task-id="' . $row->id . '">';

                    foreach ($taskBoardColumns as $item) {
                        $status .= '<option ';

                        if ($item->id == $row->board_column_id) {
                            $status .= 'selected';
                        }

                        $status .= '  data-content="<i class=\'fa fa-circle mr-2\' style=\'color: ' . $item->label_color . '\'></i> ' . $item->column_name . '" value="' . $item->slug . '">' . $item->column_name . '</option>';
                    }

                    $status .= '</select>';

                    return $status;

                }
                else {
                    return '<i class="fa fa-circle mr-1 text-yellow"
                    style="color: ' . $row->boardColumn->label_color . '"></i>' . $row->boardColumn->column_name;
                }
            })
            ->addColumn('status', function ($row) {
                return $row->boardColumn->column_name;
            })
            ->editColumn('project_name', function ($row) {
                if (is_null($row->project_id)) {
                    return '-';
                }

                return '<a href="' . route('projects.show', $row->project_id) . '" class="text-darkest-grey">' . $row->project_name . '</a>';
            })
            ->setRowId(function ($row) {
                return 'row-' . $row->id;
            })
            ->rawColumns(['board_column', 'action', 'project_name', 'clientName', 'due_date', 'users', 'heading', 'check', 'timeLogged', 'timer'])
            ->removeColumn('project_id')
            ->removeColumn('image')
            ->removeColumn('created_image')
            ->removeColumn('label_color');
    }

    /**
     * @param Task $model
     * @return mixed
     */
    public function query(Task $model)
    {
        $request = $this->request();
        $startDate = null;
        $endDate = null;

        if ($request->startDate !== null && $request->startDate != 'null' && $request->startDate != '') {
            $startDate = Carbon::createFromFormat($this->company->date_format, $request->startDate)->toDateString();
        }

        if ($request->endDate !== null && $request->endDate != 'null' && $request->endDate != '') {
            $endDate = Carbon::createFromFormat($this->company->date_format, $request->endDate)->toDateString();
        }

        $projectId = $request->projectId;
        $taskBoardColumn = TaskboardColumn::completeColumn();

        $model = $model->leftJoin('projects', 'projects.id', '=', 'tasks.project_id')
            ->leftJoin('users as client', 'client.id', '=', 'projects.client_id')
            ->join('taskboard_columns', 'taskboard_columns.id', '=', 'tasks.board_column_id');

        if (
            ($this->viewUnassignedTasksPermission == 'all'
                && !in_array('client', user_roles())
                && ($request->assignedTo == 'unassigned' || $request->assignedTo == 'all'))
            || ($request->has('project_admin') && $request->project_admin == 1)
        ) {
            $model->leftJoin('task_users', 'task_users.task_id', '=', 'tasks.id')
                ->leftJoin('users as member', 'task_users.user_id', '=', 'member.id');

        }
        else {
            $model->join('task_users', 'task_users.task_id', '=', 'tasks.id')
                ->join('users as member', 'task_users.user_id', '=', 'member.id');
        }

        $model->leftJoin('users as creator_user', 'creator_user.id', '=', 'tasks.created_by')
            ->leftJoin('task_labels', 'task_labels.task_id', '=', 'tasks.id')
            ->selectRaw('tasks.id, tasks.added_by, projects.project_name, projects.project_admin, tasks.heading, client.name as clientName, creator_user.name as created_by, creator_user.image as created_image, tasks.board_column_id,
             tasks.due_date, taskboard_columns.column_name as board_column, taskboard_columns.label_color,
              tasks.project_id, tasks.is_private ,( select count("id") from pinned where pinned.task_id = tasks.id and pinned.user_id = ' . user()->id . ') as pinned_task')
            ->with('users', 'activeTimerAll', 'boardColumn', 'activeTimer', 'timeLogged', 'timeLogged.breaks', 'userActiveTimer', 'userActiveTimer.activeBreak', 'labels')
            ->withCount('activeTimerAll')
            ->groupBy('tasks.id');


        if ($request->pinned == 'pinned') {
            $model->join('pinned', 'pinned.task_id', 'tasks.id');
            $model->where('pinned.user_id', user()->id);
        }

        if (!in_array('admin', user_roles())) {
            if ($request->pinned == 'private') {
                $model->where(
                    function ($q2) {
                        $q2->where('tasks.is_private', 1);
                        $q2->where(
                            function ($q4) {
                                $q4->where('task_users.user_id', user()->id);
                                $q4->orWhere('tasks.added_by', user()->id);
                            }
                        );
                    }
                );

            }
            else {
                $model->where(
                    function ($q) {
                        $q->where('tasks.is_private', 0);
                        $q->orWhere(
                            function ($q2) {
                                $q2->where('tasks.is_private', 1);
                                $q2->where(
                                    function ($q5) {
                                        $q5->where('task_users.user_id', user()->id);
                                        $q5->orWhere('tasks.added_by', user()->id);
                                    }
                                );
                            }
                        );
                    }
                );
            }
        }

        if ($request->assignedTo == 'unassigned' && $this->viewUnassignedTasksPermission == 'all' && !in_array('client', user_roles())) {
            $model->whereDoesntHave('users');
        }

        if ($startDate !== null && $endDate !== null) {
            $model->where(function ($q) use ($startDate, $endDate) {
                if (request()->date_filter_on == 'due_date') {
                    $q->whereBetween(DB::raw('DATE(tasks.`due_date`)'), [$startDate, $endDate]);

                }
                elseif (request()->date_filter_on == 'start_date') {
                    $q->whereBetween(DB::raw('DATE(tasks.`start_date`)'), [$startDate, $endDate]);

                }
                elseif (request()->date_filter_on == 'completed_on') {
                    $q->whereBetween(DB::raw('DATE(tasks.`completed_on`)'), [$startDate, $endDate]);
                }

            });
        }

        if ($request->overdue == 'yes' && $request->status != 'all') {
            $model->where(DB::raw('DATE(tasks.`due_date`)'), '<', now(company()->timezone)->toDateString());
        }

        if ($projectId != 0 && $projectId != null && $projectId != 'all') {
            $model->where('tasks.project_id', '=', $projectId);
        }

        if ($request->clientID != '' && $request->clientID != null && $request->clientID != 'all') {
            $model->where('projects.client_id', '=', $request->clientID);
        }

        if ($request->assignedTo != '' && $request->assignedTo != null && $request->assignedTo != 'all' && $request->assignedTo != 'unassigned') {
            $model->where('task_users.user_id', '=', $request->assignedTo);
        }

        if (($request->has('project_admin') && $request->project_admin != 1) || !$request->has('project_admin')) {
            if ($this->viewTaskPermission == 'owned') {
                $model->where(function ($q) use ($request) {
                    $q->where('task_users.user_id', '=', user()->id);

                    if (in_array('client', user_roles())) {
                        $q->orWhere('projects.client_id', '=', user()->id);
                    }

                    if ($this->viewUnassignedTasksPermission == 'all' && !in_array('client', user_roles()) && $request->assignedTo == 'all') {
                        $q->orWhereDoesntHave('users');
                    }
                });

                if ($projectId != 0 && $projectId != null && $projectId != 'all') {
                    $model->where('projects.project_admin', '<>', user()->id);
                }

            }

            if ($this->viewTaskPermission == 'added') {
                $model->where('tasks.added_by', '=', user()->id);
            }

            if ($this->viewTaskPermission == 'both') {
                $model->where(function ($q) use ($request) {
                    $q->where('task_users.user_id', '=', user()->id);

                    $q->orWhere('tasks.added_by', '=', user()->id);

                    if (in_array('client', user_roles())) {
                        $q->orWhere('projects.client_id', '=', user()->id);
                    }

                    if ($this->viewUnassignedTasksPermission == 'all' && !in_array('client', user_roles()) && ($request->assignedTo == 'unassigned' || $request->assignedTo == 'all')) {
                        $q->orWhereDoesntHave('users');
                    }

                });

            }
        }

        if ($request->assignedBY != '' && $request->assignedBY != null && $request->assignedBY != 'all') {
            $model->where('creator_user.id', '=', $request->assignedBY);
        }

        if ($request->status != '' && $request->status != null && $request->status != 'all') {
            if ($request->status == 'not finished' || $request->status == 'pending_task') {
                $model->where('tasks.board_column_id', '<>', $taskBoardColumn->id);
            }
            else {
                $model->where('tasks.board_column_id', '=', $request->status);
            }
        }

        if ($request->label != '' && $request->label != null && $request->label != 'all') {
            $model->where('task_labels.label_id', '=', $request->label);
        }

        if ($request->category_id != '' && $request->category_id != null && $request->category_id != 'all') {
            $model->where('tasks.task_category_id', '=', $request->category_id);
        }

        if ($request->billable != '' && $request->billable != null && $request->billable != 'all') {
            $model->where('tasks.billable', '=', $request->billable);
        }

        if ($request->milestone_id != '' && $request->milestone_id != null && $request->milestone_id != 'all') {
            $model->where('tasks.milestone_id', $request->milestone_id);
        }

        if ($request->searchText != '') {
            $model->where(function ($query) {
                $query->where('tasks.heading', 'like', '%' . request('searchText') . '%')
                    ->orWhere('member.name', 'like', '%' . request('searchText') . '%')
                    ->orWhere('tasks.id', 'like', '%' . request('searchText') . '%')
                    ->orWhere('projects.project_name', 'like', '%' . request('searchText') . '%');
            });
        }

        if ($request->trashedData == 'true') {
            $model->whereNotNull('projects.deleted_at');
        }
        else {
            $model->whereNull('projects.deleted_at');
        }

        if ($request->type == 'public') {
            $model->where('tasks.is_private', 0);
        }

        $model->withTrashed();

        return $model;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {

        $dataTable = $this->setBuilder('allTasks-table')
            ->parameters([
                'initComplete' => 'function () {
                   window.LaravelDataTables["allTasks-table"].buttons().container()
                    .appendTo("#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $("#allTasks-table .select-picker").selectpicker();
                    $(".bs-tooltip-top").removeClass("show");
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
            'check' => [
                'title' => '<input type="checkbox" name="select_all_table" id="select-all-table" onclick="selectAllTable(this)">',
                'exportable' => false,
                'orderable' => false,
                'searchable' => false
            ],
            __('app.id') => ['data' => 'id', 'name' => 'id', 'title' => __('app.id')],
            __('app.timer') . ' ' => ['data' => 'timer', 'name' => 'timer', 'exportable' => false, 'searchable' => false, 'sortable' => false, 'title' => '', 'class' => 'text-right'],
            __('app.task') => ['data' => 'heading', 'name' => 'heading', 'exportable' => false, 'title' => __('app.task')],
            __('app.menu.tasks') . ' ' => ['data' => 'task', 'name' => 'heading', 'visible' => false, 'title' => __('app.menu.tasks')],
            __('app.project') => ['data' => 'project_name', 'name' => 'projects.project_name', 'title' => __('app.project')],
            __('modules.tasks.assigned') => ['data' => 'name', 'name' => 'name', 'visible' => false, 'title' => __('modules.tasks.assigned')],
            __('app.dueDate') => ['data' => 'due_date', 'name' => 'due_date', 'title' => __('app.dueDate')],
            __('modules.employees.hoursLogged') => ['data' => 'timeLogged', 'name' => 'timeLogged', 'title' => __('modules.employees.hoursLogged')],
            __('modules.tasks.assignTo') => ['data' => 'users', 'name' => 'member.name', 'exportable' => false, 'title' => __('modules.tasks.assignTo')],
            __('app.columnStatus') => ['data' => 'board_column', 'name' => 'board_column', 'exportable' => false, 'searchable' => false, 'title' => __('app.columnStatus')],
            __('app.task') . ' ' . __('app.status') => ['data' => 'status', 'name' => 'board_column_id', 'visible' => false, 'title' => __('app.task')],
            Column::computed('action', __('app.action'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-right pr-20')
        ];
    }

}
