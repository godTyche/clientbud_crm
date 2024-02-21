<?php

namespace App\Http\Controllers;

use App\DataTables\LeadReportDataTable;
use App\Models\Company;
use App\Models\Deal;
use App\Models\LeadAgent;
use App\Models\User;
use Illuminate\Http\Request;

class LeadReportController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.dealReport';
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(LeadReportDataTable $dataTable)
    {
        if (!request()->ajax()) {
            $this->fromDate = now($this->company->timezone)->startOfMonth();
            $this->toDate = now($this->company->timezone);

            $this->agents = LeadAgent::with('user')
                ->join('users', 'users.id', 'lead_agents.user_id')->get();
        }

        return $dataTable->render('reports.lead.index', $this->data);
    }

}
