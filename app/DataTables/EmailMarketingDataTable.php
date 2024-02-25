<?php

namespace App\DataTables;

use App\Models\EmailMarketing;
use App\Models\CustomField;
use App\Models\CustomFieldGroup;
use App\DataTables\BaseDataTable;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\DB;

class EmailMarketingDataTable extends BaseDataTable
{

    public function __construct()
    {
        parent::__construct();
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

        $datatables->addColumn('action', function ($row) {

            if (in_array('client', user_roles())) {
                return '';
            }

            $action = '<div class="task_view">

                    <div class="dropdown">
                        <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                            id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-options-vertical icons"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '" tabindex="0">';

            $action .= '<a href="' . route('email-marketing.show', [$row->id]) . '" class="dropdown-item openRightModal" data-product-id="' . $row->id . '"><i class="fa fa-eye mr-2"></i>' . __('app.view') . '</a>';
            $action .= '<a href="' . route('email-marketing.compose', [$row->id]) . '" class="dropdown-item openRightModal" data-product-id="' . $row->id . '"><i class="fa fa-envelope mr-2"></i>' . __('modules.emailMarketing.sendEmail') . '</a>';

            // if ($this->editProductPermission == 'all' || ($this->editProductPermission == 'added' && user()->id == $row->added_by)) {
                $action .= '<a class="dropdown-item openRightModal" href="' . route('email-marketing.edit', [$row->id]) . '">
                                <i class="fa fa-edit mr-2"></i>
                                ' . trans('app.edit') . '
                            </a>';
            // }

            // if ($this->deleteProductPermission == 'all' || ($this->deleteProductPermission == 'added' && user()->id == $row->added_by)) {
                $action .= '<a class="dropdown-item delete-table-row" href="javascript:;" data-product-id="' . $row->id . '">
                                <i class="fa fa-trash mr-2"></i>
                                ' . trans('app.delete') . '
                            </a>';
            // }

            $action .= '</div>
                    </div>
                </div>';

            return $action;
        });
        
        $datatables->addIndexColumn();
        $datatables->smart(false);
        $datatables->setRowId(function ($row) {
            return 'row-' . $row->id;
        });

        $datatables->rawColumns(array_merge(['action', 'price', 'allow_purchase', 'check', 'name', 'default_image']));

        return $datatables;
    }

    /**
     * @param EmailMarketing $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(EmailMarketing $model)
    {
        $request = $this->request();

        $model = $model->select('id', 'title', 'content', 'addedBy');


        return $model;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        $dataTable = $this->setBuilder('email-marketing-table', 2)
            ->parameters([
                'initComplete' => 'function () {
                   window.LaravelDataTables["email-marketing-table"].buttons().container()
                    .appendTo( "#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $("body").tooltip({
                        selector: \'[data-toggle="tooltip"]\'
                    })
                }',
            ]);

        if (canDataTableExport()) {
            // $dataTable->buttons(Button::make(['extend' => 'excel', 'text' => '<i class="fa fa-file-export"></i> ' . trans('app.exportExcel')]));
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
                // 'visible' => !in_array('client', user_roles())
            ],
            '#' => ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'visible' => false, 'title' => '#'],
            __('app.id') => ['data' => 'id', 'name' => 'id', 'title' => __('app.id'), 'visible' => true],
            __('app.title') => ['data' => 'title', 'name' => 'title', 'title' => __('app.title') ],
            __('app.addedBy') => ['data' => 'addedBy', 'name' => 'addedBy', 'title' => __('app.addedBy') ],
        ];

        $action = [
            Column::computed('action', __('app.action'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-right pr-20')
        ];

        return array_merge($data, $action);
    }
}