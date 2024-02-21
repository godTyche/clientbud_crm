<?php

namespace App\DataTables;

use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Button;

class ExpenseReportDataTable extends BaseDataTable
{

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="select-table-row" id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" onclick="dataTableRowCheck(' . $row->id . ')">';
            })
            ->editColumn('price', function ($row) {
                return $row->total_amount;
            })
            ->addColumn('default_currency_price', function ($row) {
                return currency_format($row->default_currency_price, company()->currency_id);
            })
            ->editColumn('item_name', function ($row) {
                if (is_null($row->expenses_recurring_id)) {
                    return '<a href="' . route('expenses.show', $row->id) . '" class="openRightModal text-darkest-grey">' . $row->item_name . '</a>';
                }

                return '<a href="' . route('expenses.show', $row->id) . '" class="openRightModal text-darkest-grey">' . $row->item_name . '</a>
                <p class="mb-0"><span class="badge badge-primary"> ' . __('app.recurring') . ' </span></p>';
            })
            ->addColumn('export_item_name', function ($row) {
                return $row->item_name;
            })
            ->addColumn('employee_name', function ($row) {
                return $row->user->name;
            })
            ->addColumn('bank_account', function ($row) {
                return !is_null($row->bank_name) ? $row->bank_name : '--';
            })
            ->editColumn('user_id', function ($row) {
                return view('components.employee', [
                    'user' => $row->user
                ]);
            })
            ->addColumn('export_bill', function($row){
                return !is_null($row->bill) ? $row->bill_url : '';
            })
            ->addColumn('bill', function($row){
                return !is_null($row->bill) ? $row->bill : '--';
            })
            ->addColumn('status', function ($row) {
                return '<i class="fa fa-circle mr-1 text-dark-green f-10"></i>' . __('app.' . $row->status);
            })
            ->editColumn(
                'purchase_date',
                function ($row) {
                    if (!is_null($row->purchase_date)) {

                        return $row->purchase_date->translatedFormat($this->company->date_format);
                    }
                }
            )
            ->editColumn(
                'purchase_from',
                function ($row) {
                    return !is_null($row->purchase_from) ? $row->purchase_from : '--';
                }
            )
            ->smart(false)
            ->setRowId(function ($row) {
                return 'row-' . $row->id;
            })
            ->addIndexColumn()
            ->rawColumns(['action', 'status', 'user_id', 'item_name', 'check'])
            ->removeColumn('currency_id')
            ->removeColumn('name')
            ->removeColumn('currency_symbol')
            ->removeColumn('updated_at')
            ->removeColumn('created_at');
    }

    /**
     * Get query source of dataTable.
     *
     * @param Expense $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Expense $model)
    {
        $request = $this->request();

        if ($request->startDate !== null && $request->startDate != 'null' && $request->startDate != '') {
            $startDate = Carbon::createFromFormat($this->company->date_format, $request->startDate)->toDateString();
            $model = $model->where(DB::raw('DATE(`purchase_date`)'), '>=', $startDate);
        }

        if ($request->endDate !== null && $request->endDate != 'null' && $request->endDate != '') {
            $endDate = Carbon::createFromFormat($this->company->date_format, $request->endDate)->toDateString();
            $model = $model->where(DB::raw('DATE(`purchase_date`)'), '<=', $endDate);
        }

        if ($request->categoryID != 'all' && !is_null($request->categoryID)) {
            $model = $model->where('category_id', '=', $request->categoryID);
        }

        if ($request->projectID != 'all' && !is_null($request->projectID)) {
            $model = $model->where('project_id', '=', $request->projectID);
        }

        if ($request->employeeID != 'all' && !is_null($request->employeeID)) {
            $employeeID = $request->employeeID;
            $model = $model->where(function ($query) use ($employeeID) {
                $query->where('user_id', $employeeID);
            });
        }

        $model = $model->where('status', 'approved');

        return $model;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        $dataTable = $this->setBuilder('expense-report-table')
            ->parameters([
                'initComplete' => 'function () {
                   window.LaravelDataTables["expense-report-table"].buttons().container()
                    .appendTo("#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $("#expense-report-table .select-picker").selectpicker();
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
            __('modules.expenses.itemName') => ['data' => 'item_name', 'name' => 'item_name', 'exportable' => false, 'title' => __('modules.expenses.itemName')],
            __('app.menu.itemName') => ['data' => 'export_item_name', 'name' => 'export_item_name', 'visible' => false, 'title' => __('modules.expenses.itemName')],
            __('app.price') => ['data' => 'price', 'name' => 'price', 'title' => __('app.price')],
            __('app.price') . company()->currency->currency_code => ['data' => 'default_currency_price', 'name' => 'default_currency_price',  'orderable' => false, 'title' => __('app.price') . ' ( ' . company()->currency->currency_code . ' )'],
            __('app.menu.employees') => ['data' => 'user_id', 'name' => 'user_id', 'exportable' => false, 'title' => __('app.menu.employees')],
            __('app.employee') => ['data' => 'employee_name', 'name' => 'user_id', 'visible' => false, 'title' => __('app.employee')],
            __('modules.expenses.purchaseFrom') => ['data' => 'purchase_from', 'name' => 'purchase_from', 'title' => __('modules.expenses.purchaseFrom')],
            __('app.bankaccount') => ['data' => 'bank_account', 'name' => 'bank_account', 'title' => __('app.bankaccount')],
            __('modules.expenses.purchaseDate') => ['data' => 'purchase_date', 'name' => 'purchase_date', 'title' => __('modules.expenses.purchaseDate')],
            __('modules.expenses.expenseBill') => ['data' => 'export_bill', 'name' => 'export_bill', 'visible' => false, 'title' => __('modules.expenses.expenseBill')],
            __('app.bill') => ['data' => 'bill', 'name' => 'bill', 'exportable' => false, 'title' => __('app.bill')],
            __('app.status') => ['data' => 'status', 'name' => 'status', 'exportable' => false, 'title' => __('app.status')],
            __('app.status') . ' ' . __('app.status') => ['data' => 'status', 'name' => 'status', 'visible' => false, 'title' => __('app.status')]
        ];
    }

}
