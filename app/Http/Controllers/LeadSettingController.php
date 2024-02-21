<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Models\LeadAgent;
use App\Models\LeadCategory;
use App\Models\LeadPipeline;
use App\Models\LeadSource;
use App\Models\PipelineStage;
use App\Models\LeadStatus;
use App\Models\User;

class LeadSettingController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'modules.deal.leadSetting';
        $this->activeSettingMenu = 'lead_settings';
        $this->middleware(function ($request, $next) {
            abort_403(!(user()->permission('manage_lead_setting') == 'all' && in_array('leads', user_modules())));
            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function index()
    {
        $this->pipelines = LeadPipeline::all();
        $this->leadSources = LeadSource::all();
        $this->leadStages = PipelineStage::all();
        $this->leadAgents = LeadAgent::with('user')->get();
        $this->leadCategories = LeadCategory::all();

        $this->employees = User::doesntHave('leadAgent')
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->select('users.id', 'users.name', 'users.email', 'users.created_at')
            ->where('roles.name', 'employee')
            ->get();

        $tab = request('tab');

        $this->view = match ($tab) {
            'pipeline' => 'lead-settings.ajax.pipeline',
            'agent' => 'lead-settings.ajax.agent',
            'category' => 'lead-settings.ajax.category',
            default => 'lead-settings.ajax.source',
        };

        $this->activeTab = $tab ?: 'source';

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle, 'activeTab' => $this->activeTab]);
        }

        return view('lead-settings.index', $this->data);

    }

}
