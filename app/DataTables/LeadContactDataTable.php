<?php

namespace App\DataTables;

use Carbon\Carbon;
use App\Models\LeadStatus;
use App\Models\CustomField;
use App\Models\CustomFieldGroup;
use App\Models\Lead;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\DB;

class LeadContactDataTable extends BaseDataTable
{

    private $editLeadPermission;
    private $viewLeadFollowUpPermission;
    private $deleteLeadPermission;
    private $addFollowUpPermission;
    private $changeLeadStatusPermission;
    private $viewLeadPermission;

    /**
     * @var LeadStatus[]|\Illuminate\Database\Eloquent\Collection
     */
    private $status;

    public function __construct()
    {
        parent::__construct();
        $this->editLeadPermission = user()->permission('edit_lead');
        $this->deleteLeadPermission = user()->permission('delete_lead');
        $this->viewLeadPermission = user()->permission('view_lead');
        $this->addFollowUpPermission = user()->permission('add_lead_follow_up');
        $this->changeLeadStatusPermission = user()->permission('change_deal_stages');
        $this->viewLeadFollowUpPermission = user()->permission('view_lead_follow_up');
        $this->status = LeadStatus::get();
    }

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $currentDate = Carbon::now(company()->timezone)->translatedFormat('Y-m-d');
        $status = $this->status;

        $datatables = datatables()->eloquent($query);
        $datatables->addIndexColumn();
        $datatables->addColumn('check', function ($row) {
            return '<input type="checkbox" class="select-table-row" id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" onclick="dataTableRowCheck(' . $row->id . ')">';
        });
        $datatables->addColumn('action', function ($row) {
            $action = '<div class="task_view">

                    <div class="dropdown">
                        <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                            id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-options-vertical icons"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '" tabindex="0">';

            $action .= '<a href="' . route('lead-contact.show', [$row->id]) . '" class="dropdown-item"><i class="fa fa-eye mr-2"></i>' . __('app.view') . '</a>';

            if (
                $this->editLeadPermission == 'all'
                || ($this->editLeadPermission == 'added' && user()->id == $row->added_by) || user()->id == $row->added_by)

            {
                $action .= '<a class="dropdown-item" href="' . route('lead-contact.edit', [$row->id]) . '">
                                <i class="fa fa-edit mr-2"></i>
                                ' . trans('app.edit') . '
                            </a>';
            }

            if ($row->client_id == null || $row->client_id == '') {
                $action .= '<a class="dropdown-item" href="' . route('clients.create') . '?lead=' . $row->id . '">
                                <i class="fa fa-user mr-2"></i>
                                ' . trans('modules.lead.changeToClient') . '
                            </a>';
            }

            if (
                $this->deleteLeadPermission == 'all'
                || ($this->deleteLeadPermission == 'added' && user()->id == $row->added_by)
                || ($this->deleteLeadPermission == 'owned' && !is_null($row->agent_id) && user()->id == $row->leadAgent->user->id)
                || ($this->deleteLeadPermission == 'both' && ((!is_null($row->agent_id) && user()->id == $row->leadAgent->user->id)
                        || user()->id == $row->added_by))
            ) {
                $action .= '<a class="dropdown-item delete-table-row" href="javascript:;" data-id="' . $row->id . '">
                        <i class="fa fa-trash mr-2"></i>
                        ' . trans('app.delete') . '
                    </a>';
            }

            $action .= '</div>
                    </div>
                </div>';

            return $action;
        });

        $datatables->addColumn('export_email', function ($row) {
            return $row->client_email;
        });

        $datatables->addColumn('lead_value', function ($row) {
            return currency_format($row->value, $row->currency_id);
        });

        $datatables->addColumn('name', function ($row) {
            return $row->client_name;
        });

        $datatables->addColumn('email', function ($row) {
            return $row->client_email;
        });

        $datatables->addColumn('category_name', function ($row) {

            if (!is_null($row->category_id)) {
                return $row->category->category_name;
            }
        });

        $datatables->editColumn('client_name', function ($row) {
            if ($row->client_id != null && $row->client_id != '') {
                $label = '<label class="badge badge-secondary">' . __('app.client') . '</label>';
            }
            else {
                $label = '';
            }

            $client_name = ($row->salutation ? $row->salutation->label() . ' ' : '') . $row->client_name;

            return '
                        <div class="media-bod1y">
                    <h5 class="mb-0 f-13 "><a href="' . route('lead-contact.show', [$row->id]) . '">' . $client_name . '</a></h5>
                    <p class="mb-0">' . $label . '</p>
                    <p class="mb-0 f-12 text-dark-grey">
                    '.$row->company_name.'
                </p>
                    </div>
                  ';
        });

        $datatables->editColumn('created_at', function ($row) {
            return $row->created_at->translatedFormat($this->company->date_format);
        });

        $datatables->smart(false);
        $datatables->setRowId(function ($row) {
            return 'row-' . $row->id;
        });
        $datatables->removeColumn('client_id');
        $datatables->removeColumn('source');

        $customFieldColumns = CustomField::customFieldData($datatables, Lead::CUSTOM_FIELD_MODEL);

        $datatables->rawColumns(array_merge(['action', 'client_name', 'check'], $customFieldColumns));

        return $datatables;
    }

    /**
     * @param Lead $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Lead $model)
    {
        $leadContact = $model->with(['category'])
            ->select(
                'leads.id',
                'leads.added_by',
                'leads.client_id',
                'leads.salutation',
                'leads.category_id',
                'leads.client_name',
                'leads.client_email',
                'leads.company_name',
                'leads.created_at',
                'leads.updated_at',
                'lead_sources.type as source',
            )
            ->leftJoin('lead_sources', 'lead_sources.id', 'leads.source_id');

        if ($this->request()->type != 'all' && $this->request()->type != '') {

            if ($this->request()->type == 'lead') {
                $leadContact = $leadContact->whereNull('client_id');
            }
            else {
                $leadContact = $leadContact->whereNotNull('client_id');
            }
        }

        if ($this->request()->startDate !== null && $this->request()->startDate != 'null' && $this->request()->startDate != '' && request()->date_filter_on == 'created_at') {
            $startDate = Carbon::createFromFormat($this->company->date_format, $this->request()->startDate)->toDateString();

            $leadContact = $leadContact->having(DB::raw('DATE(leads.`created_at`)'), '>=', $startDate);
        }

        if ($this->request()->endDate !== null && $this->request()->endDate != 'null' && $this->request()->endDate != '' && request()->date_filter_on == 'created_at') {
            $endDate = Carbon::createFromFormat($this->company->date_format, $this->request()->endDate)->toDateString();
            $leadContact = $leadContact->having(DB::raw('DATE(leads.`created_at`)'), '<=', $endDate);
        }


        if ($this->request()->startDate !== null && $this->request()->startDate != 'null' && $this->request()->startDate != '' && request()->date_filter_on == 'updated_at') {
            $startDate = Carbon::createFromFormat($this->company->date_format, $this->request()->startDate)->toDateString();

            $leadContact = $leadContact->having(DB::raw('DATE(leads.`updated_at`)'), '>=', $startDate);
        }

        if ($this->request()->endDate !== null && $this->request()->endDate != 'null' && $this->request()->endDate != '' && request()->date_filter_on == 'updated_at') {
            $endDate = Carbon::createFromFormat($this->company->date_format, $this->request()->endDate)->toDateString();
            $leadContact = $leadContact->having(DB::raw('DATE(leads.`updated_at`)'), '<=', $endDate);
        }

        if ($this->viewLeadPermission == 'both') {
            $leadContact = $leadContact->where(function ($query) {
                $query->orWhere('leads.added_by', user()->id);
            });
        }

        if ($this->request()->category_id != 'all' && $this->request()->category_id != '') {
            $leadContact = $leadContact->where('category_id', $this->request()->category_id);
        }

        if ($this->request()->source_id != 'all' && $this->request()->source_id != '') {
            $leadContact = $leadContact->where('source_id', $this->request()->source_id);
        }

        if ($this->request()->searchText != '') {
            $leadContact = $leadContact->where(function ($query) {
                $query->where('leads.client_name', 'like', '%' . request('searchText') . '%')
                    ->orWhere('leads.client_email', 'like', '%' . request('searchText') . '%')
                    ->orwhere('leads.mobile', 'like', '%' . request('searchText') . '%');
            });
        }

        return $leadContact->groupBy('leads.id');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        $dataTable = $this->setBuilder('lead-contact-table', 2)
            ->parameters([
                'initComplete' => 'function () {
                   window.LaravelDataTables["lead-contact-table"].buttons().container()
                    .appendTo("#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $("body").tooltip({
                        selector: \'[data-toggle="tooltip"]\'
                    });
                    $(".statusChange").selectpicker();
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
                'searchable' => false
            ],
            '#' => ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'visible' => false, 'title' => '#'],
            __('app.id') => ['data' => 'id', 'name' => 'id', 'title' => __('app.id'), 'visible' => showId()],
            __('app.name') => ['data' => 'name', 'name' => 'name', 'exportable' => true, 'visible' => false,'title' => __('app.name')],
            __('modules.leadContact.contactName') => ['data' => 'client_name', 'name' => 'leads.client_name', 'exportable' => false, 'title' => __('modules.leadContact.contactName')],
            __('modules.lead.companyName') => ['data' => 'company_name', 'name' => 'company_name', 'exportable' => true, 'title' => __('modules.lead.companyName')],

            __('app.email') . ' ' . __('modules.lead.email') => ['data' => 'export_email', 'name' => 'email', 'title' => __('app.lead') . ' ' . __('modules.lead.email'), 'exportable' => true, 'visible' => false],
            __('modules.lead.email') => ['data' => 'email', 'name' => 'leads.client_email', 'title' => __('modules.lead.email')],
            __('modules.lead.leadCategory') => ['data' => 'category_name', 'name' => 'category_name', 'exportable' => true, 'visible' => false, 'title' => __('modules.lead.leadCategory')],
            __('app.createdOn') => ['data' => 'created_at', 'name' => 'leads.created_at', 'title' => __('app.createdOn')],
        ];

        $action = [
            Column::computed('action', __('app.action'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-right pr-20')
        ];


        return array_merge($data, CustomFieldGroup::customFieldsDataMerge(new Lead()), $action);

    }

}

