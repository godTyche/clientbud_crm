<?php

namespace App\DataTables;

use Carbon\Carbon;
use App\Models\Ticket;
use App\Models\CustomField;
use App\Models\CustomFieldGroup;
use App\DataTables\BaseDataTable;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\DB;

class   TicketDataTable extends BaseDataTable
{

    private $deleteTicketPermission;
    private $viewTicketPermission;
    private $editTicketPermission;

    public function __construct()
    {
        parent::__construct();
        $this->deleteTicketPermission = user()->permission('delete_tickets');

        $this->viewTicketPermission = user()->permission('view_tickets');
        $this->editTicketPermission = user()->permission('edit_tickets');
    }

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $datatables = datatables()->eloquent($query);
        $datatables->addColumn('check', function ($row) {
            return '<input type="checkbox" class="select-table-row" id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" onclick="dataTableRowCheck(' . $row->id . ')">';
        });
        $datatables->addIndexColumn();
        $datatables->addColumn('action', function ($row) {
            $action = '<div class="task_view">';

            $action .= '<div class="dropdown">
                    <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                        id="dropdownMenuLink-' . $row->ticket_number . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="icon-options-vertical icons"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->ticket_number . '" tabindex="0">';

            if (
                $this->viewTicketPermission == 'all'
                || ($this->viewTicketPermission == 'added' && user()->id == $row->added_by)
                || ($this->viewTicketPermission == 'owned' && (user()->id == $row->user_id || $row->agent_id == user()->id))
                || ($this->viewTicketPermission == 'both' && (user()->id == $row->user_id || $row->agent_id == user()->id || $row->added_by == user()->id))
            ) {
                $action .= '<a href="' . route('tickets.show', [$row->ticket_number]) . '" class="dropdown-item"><i class="fa fa-eye mr-2"></i>' . __('app.view') . '</a>';
            }

            if (
                $this->deleteTicketPermission == 'all'
                || ($this->deleteTicketPermission == 'added' && user()->id == $row->added_by)
                || ($this->deleteTicketPermission == 'owned' && (user()->id == $row->agent_id || user()->id == $row->user_id))
                || ($this->deleteTicketPermission == 'both' && (user()->id == $row->agent_id || user()->id == $row->added_by || user()->id == $row->user_id))
            ) {
                $action .= '<a class="dropdown-item delete-table-row" href="javascript:;" data-ticket-id="' . $row->id . '">
                            <i class="fa fa-trash mr-2"></i>
                            ' . trans('app.delete') . '
                        </a>';
            }

            $action .= '</div>
                        </div>
                    </div>';

            return $action;
        });
        $datatables->addColumn('others', function ($row) {
            $others = '';

            if (!is_null($row->agent)) {
                $others .= '<div class="mb-2">' . __('modules.tickets.agent') . ': ' . (is_null($row->agent_id) ? '-' : $row->agent->name) . '</div> ';
            }

            $others .= '<div>' . __('modules.tasks.priority') . ': ' . __('app.' . $row->priority) . '</div> ';

            return $others;
        });

        $datatables->addColumn('status', function ($row) {
            if (
                $this->editTicketPermission == 'all'
                || ($this->editTicketPermission == 'added' && user()->id == $row->added_by)
                || ($this->editTicketPermission == 'owned' && (user()->id == $row->user_id || $row->agent_id == user()->id))
                || ($this->editTicketPermission == 'both' && (user()->id == $row->user_id || $row->agent_id == user()->id || $row->added_by == user()->id))
            ) {
                $status = '<select class="form-control select-picker change-status" data-ticket-id="' . $row->id . '">';
                $status .= '<option ';

                if ($row->status == 'open') {
                    $status .= 'selected';
                }

                $status .= '  data-content="<i class=\'fa fa-circle mr-2 text-red\'></i> ' . __('app.open') . '" value="open">' . __('app.open') . '</option>';
                $status .= '<option ';

                if ($row->status == 'pending') {
                    $status .= 'selected';
                }

                $status .= '  data-content="<i class=\'fa fa-circle mr-2 text-yellow\'></i> ' . __('app.pending') . '" value="pending">' . __('app.pending') . '</option>';
                $status .= '<option ';

                if ($row->status == 'resolved') {
                    $status .= 'selected';
                }

                $status .= '  data-content="<i class=\'fa fa-circle mr-2 text-dark-green\'></i> ' . __('app.resolved') . '" value="resolved">' . __('app.resolved') . '</option>';
                $status .= '<option ';

                if ($row->status == 'closed') {
                    $status .= 'selected';
                }

                $status .= '  data-content="<i class=\'fa fa-circle mr-2 text-blue\'></i> ' . __('app.closed') . '" value="closed">' . __('app.closed') . '</option>';

                $status .= '</select>';

                return $status;
            }

            $statuses = [
                'open' => ['red', __('app.open')],
                'pending' => ['warning', __('app.pending')],
                'resolved' => ['dark-green', __('app.resolved')],
                'closed' => ['blue', __('app.closed')],
            ];

            $status = $statuses[$row->status] ?? $statuses['closed'];

            return '<i class="fa fa-circle mr-2 text-' . $status[0] . '"></i>' . $status[1];

            /* status end */
        });
        $datatables->editColumn('ticket_status', function ($row) {
            return $row->status;
        });
        $datatables->editColumn('subject', function ($row) {
            return '<a href="' . route('tickets.show', $row->ticket_number) . '" class="text-darkest-grey" >' . $row->subject . '</a>' . $row->badge();
        });
        $datatables->addColumn('name', function ($row) {
            return $row->requester ? $row->requester->name : $row->ticket_number;
        });
        $datatables->editColumn('user_id', function ($row) {
            if (is_null($row->requester)) {
                return '';
            }

            if ($row->requester->hasRole('employee')) {
                return view('components.employee', [
                    'user' => $row->requester
                ]);
            }

            return view('components.client', [
                'user' => $row->requester
            ]);

        });
        $datatables->editColumn('updated_at', function ($row) {
            return $row->created_at->timezone($this->company->timezone)->translatedFormat($this->company->date_format . ' ' . $this->company->time_format);
        });

        $datatables->setRowId(function ($row) {
            return 'row-' . $row->id;
        });

        $datatables->orderColumn('user_id', 'name $1');
        $datatables->orderColumn('status', 'id $1');

        $datatables->removeColumn('agent_id');
        $datatables->removeColumn('channel_id');
        $datatables->removeColumn('type_id');
        $datatables->removeColumn('deleted_at');

        // Custom Fields For export
        $customFieldColumns = CustomField::customFieldData($datatables, Ticket::CUSTOM_FIELD_MODEL);

        $datatables->rawColumns(array_merge(['others', 'action', 'subject', 'check', 'user_id', 'status'], $customFieldColumns));

        return $datatables;
    }

    /**
     * @param Ticket $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Ticket $model)
    {
        $request = $this->request();

        $model = $model->with('requester', 'agent')
            ->select('tickets.*')
            ->join('users', 'users.id', '=', 'tickets.user_id');

        if (!is_null($request->startDate) && $request->startDate != '') {
            $startDate = Carbon::createFromFormat($this->company->date_format, $request->startDate)->toDateString();
            $model->where(DB::raw('DATE(tickets.updated_at)'), '>=', $startDate);
        }

        if (!is_null($request->endDate) && $request->endDate != '') {
            $endDate = Carbon::createFromFormat($this->company->date_format, $request->endDate)->toDateString();
            $model->where(DB::raw('DATE(tickets.updated_at)'), '<=', $endDate);
        }

        if (!is_null($request->agentId) && $request->agentId != 'all' && $request->ticketFilterStatus != 'unassigned') {
            $model->where('tickets.agent_id', '=', $request->agentId);
        }

        if (!is_null($request->groupId) && $request->groupId != 'all') {
            $model->where('tickets.group_id', '=', $request->groupId);
        }

        if (!is_null($request->client_id) && $request->client_id != 'all') {
            $model->where('tickets.user_id', $request->client_id);
        }

        if (!is_null($request->employee_id) && $request->employee_id != 'all') {
            $model->where('tickets.user_id', $request->employee_id);
        }

        if (!is_null($request->ticketStatus) && $request->ticketStatus != 'all' && $request->ticketFilterStatus == '') {
            $request->ticketStatus == 'unassigned' ? $model->whereNull('agent_id') : $model->where('tickets.status', '=', $request->ticketStatus);
        }

        if ($request->ticketFilterStatus != '') {
            ($request->ticketFilterStatus == 'open' || $request->ticketFilterStatus == 'unassigned') ? $model->where(function ($query) {
                $query->where('tickets.status', '=', 'open')
                    ->orWhere('tickets.status', '=', 'pending');
            }) : $model->where(function ($query) {
                $query->where('tickets.status', '=', 'resolved')
                    ->orWhere('tickets.status', '=', 'closed');
            });

            if ($request->ticketFilterStatus == 'unassigned') {
                $model->whereNull('agent_id');
            }
        }

        if (!is_null($request->priority) && $request->priority != 'all') {
            $model->where('tickets.priority', '=', $request->priority);
        }

        if (!is_null($request->channelId) && $request->channelId != 'all') {

            $model->where('tickets.channel_id', '=', $request->channelId);
        }

        if (!is_null($request->typeId) && $request->typeId != 'all') {
            $model->where('tickets.type_id', '=', $request->typeId);
        }

        if (!is_null($request->tagId) && $request->tagId != 'all') {
            $model->join('ticket_tags', 'ticket_tags.ticket_id', 'tickets.id');
            $model->where('ticket_tags.tag_id', '=', $request->tagId);
        }

        if (!is_null($request->projectID) && $request->projectID != 'all') {
            $model->whereHas('project', function ($q) use ($request) {
                $q->where('project_id', $request->projectID);
            });
        }

        if ($this->viewTicketPermission == 'owned') {
            $model->where(function ($query) {
                $query->where('tickets.user_id', '=', user()->id)
                    ->orWhere('tickets.agent_id', '=', user()->id);
            });
        }

        if ($this->viewTicketPermission == 'both') {
            $model->where(function ($query) {
                $query->where('tickets.user_id', '=', user()->id)
                    ->orWhere('tickets.added_by', '=', user()->id)
                    ->orWhere('tickets.agent_id', '=', user()->id);
            });
        }

        if ($this->viewTicketPermission == 'added') {
            $model->where('tickets.added_by', '=', user()->id);
        }

        if ($request->searchText != '') {
            $model->where(function ($query) {
                $query->where('tickets.subject', 'like', '%' . request('searchText') . '%')
                    ->orWhere('tickets.ticket_number', 'like', '%' . request('searchText') . '%')
                    ->orWhere('tickets.status', 'like', '%' . request('searchText') . '%')
                    ->orWhere('users.name', 'like', '%' . request('searchText') . '%')
                    ->orWhere('tickets.priority', 'like', '%' . request('searchText') . '%');
            });
        }

        return $model;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        $dataTable = $this->setBuilder('ticket-table', 5)
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["ticket-table"].buttons().container()
                    .appendTo("#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $("#ticket-table .select-picker").selectpicker();

                    $("body").tooltip({
                        selector: \'[data-toggle="tooltip"]\'
                    })
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

        $data = [
            'check' => [
                'title' => '<input type="checkbox" name="select_all_table" id="select-all-table" onclick="selectAllTable(this)">',
                'exportable' => false,
                'orderable' => false,
                'searchable' => false,
                'visible' => !in_array('client', user_roles())
            ],
            __('modules.tickets.ticket') . ' #' => ['data' => 'ticket_number', 'name' => 'ticket_number', 'title' => __('modules.tickets.ticket') . ' #'],
            __('modules.tickets.ticketSubject') => ['data' => 'subject', 'name' => 'subject', 'title' => __('modules.tickets.ticketSubject'), 'width' => '20%'],
            __('app.name') => ['data' => 'name', 'name' => 'user_id', 'visible' => false, 'title' => __('app.name')],
            __('modules.tickets.requesterName') => ['data' => 'user_id', 'name' => 'user_id', 'visible' => !in_array('client', user_roles()), 'exportable' => false, 'title' => __('modules.tickets.requesterName'), 'width' => '20%'],
            __('modules.tickets.requestedOn') => ['data' => 'updated_at', 'name' => 'updated_at', 'title' => __('modules.tickets.requestedOn')],
            __('app.others') => ['data' => 'others', 'name' => 'others', 'sortable' => false, 'title' => __('app.others')],
            __('app.status') => ['data' => 'status', 'name' => 'status', 'exportable' => false, 'title' => __('app.status')],
            __('modules.ticketStatus') => ['data' => 'ticket_status', 'name' => 'ticket_status', 'visible' => false, 'title' => __('modules.ticketStatus')]
        ];

        $action = [
            Column::computed('action', __('app.action'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-right pr-20')
        ];

        return array_merge($data, CustomFieldGroup::customFieldsDataMerge(new Ticket()), $action);

    }

}
