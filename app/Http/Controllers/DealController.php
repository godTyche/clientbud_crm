<?php

namespace App\Http\Controllers;

use App\DataTables\DealNotesDataTable;
use App\DataTables\LeadFollowupDataTable;
use App\DataTables\LeadGDPRDataTable;
use App\DataTables\DealsDataTable;
use App\DataTables\ProposalDataTable;
use App\Enums\Salutation;
use App\Helper\Reply;
use App\Http\Requests\Admin\Employee\ImportProcessRequest;
use App\Http\Requests\Admin\Employee\ImportRequest;
use App\Http\Requests\CommonRequest;
use App\Http\Requests\FollowUp\StoreRequest as FollowUpStoreRequest;
use App\Http\Requests\Deal\StoreRequest;
use App\Http\Requests\Deal\UpdateRequest;
use App\Imports\DealImport;
use App\Jobs\ImportDealJob;
use App\Models\GdprSetting;
use App\Models\Deal;
use App\Models\LeadAgent;
use App\Models\LeadCategory;
use App\Models\LeadCustomForm;
use App\Models\DealFollowUp;
use App\Models\Lead;
use App\Models\LeadPipeline;
use App\Models\LeadProduct;
use App\Models\LeadSource;
use App\Models\PipelineStage;
use App\Models\LeadStatus;
use App\Models\Product;
use App\Models\PurposeConsent;
use App\Models\PurposeConsentLead;
use App\Models\User;
use App\Traits\ImportExcel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DealController extends AccountBaseController
{

    use ImportExcel;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.deal';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('leads', $this->user->modules));

            return $next($request);
        });
    }

    public function index(DealsDataTable $dataTable)
    {
        $this->viewLeadPermission = $viewPermission = user()->permission('view_deals');

        abort_403(!in_array($viewPermission, ['all', 'added', 'both', 'owned']));

        if (!request()->ajax()) {
            $this->totalDeals = Deal::all();
            $this->pipelines = LeadPipeline::all();

            $defaultPipeline = $this->pipelines->filter(function ($value, $key) {
                return $value->default == 1;
            })->first();

            $this->stages = PipelineStage::where('lead_pipeline_id', $defaultPipeline->id)->get();
            $this->categories = LeadCategory::all();
            $this->sources = LeadSource::all();

            $this->totalClientConverted = $this->totalDeals->filter(function ($value, $key) {
                return $value->client_id != null;
            });

            $this->totalLeads = $this->totalDeals->count();
            $this->totalClientConverted = $this->totalClientConverted->count();

            $this->pendingLeadFollowUps = DealFollowUp::where(DB::raw('DATE(next_follow_up_date)'), '<=', now()->format('Y-m-d'))
                ->join('deals', 'deals.id', 'lead_follow_up.deal_id')
                ->where('deals.next_follow_up', 'yes')
                ->groupBy('lead_follow_up.deal_id')
                ->get();

            $this->pendingLeadFollowUps = $this->pendingLeadFollowUps->count();

            $this->viewLeadAgentPermission = user()->permission('view_lead_agents');

            $this->leadAgents = LeadAgent::with('user')->whereHas('user', function ($q) {
                $q->where('status', 'active');
            });

            $this->leadAgents = $this->leadAgents->where(function ($q) {
                if ($this->viewLeadAgentPermission == 'all') {
                    $this->leadAgents = $this->leadAgents;
                }
                elseif ($this->viewLeadAgentPermission == 'added') {
                    $this->leadAgents = $this->leadAgents->where('added_by', user()->id);
                }
                elseif ($this->viewLeadAgentPermission == 'owned') {
                    $this->leadAgents = $this->leadAgents->where('user_id', user()->id);
                }
                elseif ($this->viewLeadAgentPermission == 'both') {
                    $this->leadAgents = $this->leadAgents->where('added_by', user()->id)->orWhere('user_id', user()->id);
                }
                else {
                    // This is $this->viewLeadAgentPermission == 'none'
                    $this->leadAgents = [];
                }
            })->get();

        }

        return $dataTable->render('leads.index', $this->data);

    }

    public function show($id)
    {
        $this->deal = Deal::with(['leadAgent', 'leadAgent.user', 'products', 'leadStage', 'contact'])->findOrFail($id)->withCustomFields();

        $leadAgentId = ($this->deal->leadAgent != null) ? $this->deal->leadAgent->user->id : 0;

        $this->viewPermission = user()->permission('view_deals');

        abort_403(!(
            $this->viewPermission == 'all'
            || ($this->viewPermission == 'added' && $this->deal->added_by == user()->id)
            || ($this->viewPermission == 'owned' && $this->deal->leadAgent->user->id == user()->id)
            || ($this->viewPermission == 'both' && ($this->deal->added_by == user()->id || $leadAgentId == user()->id))
        ));

        $this->pageTitle = $this->deal->name;

        $this->productNames = $this->deal->products->pluck('name')->toArray();

        $this->leadFormFields = LeadCustomForm::with('customField')->where('status', 'active')->where('custom_fields_id', '!=', 'null')->get();

        $this->leadId = $id;

        if ($this->deal->getCustomFieldGroupsWithFields()) {
            $this->fields = $this->deal->getCustomFieldGroupsWithFields()->fields;
        }

        $this->deleteLeadPermission = user()->permission('delete_deals');
        $this->view = 'leads.ajax.profile';

        $tab = request('tab');

        switch ($tab) {
        case 'files':
            $this->view = 'leads.ajax.files';
            break;
        case 'follow-up':
            return $this->leadFollowup();
        case 'proposals':
            return $this->proposals();
        case 'notes':
            return $this->notes();
        case 'gdpr':

            $this->consents = PurposeConsent::with(['lead' => function ($query) use ($id) {
                $query->where('lead_id', $id)
                    ->orderBy('created_at', 'desc');
            }])->get();

            $this->gdpr = GdprSetting::first();

            return $this->gdpr();
        default:
            $this->view = 'leads.ajax.profile';
            break;
        }

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->activeTab = $tab ?: 'profile';

        return view('leads.show', $this->data);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->addPermission = user()->permission('add_deals');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $defaultStatus = LeadStatus::where('default', '1')->first();
        $this->columnId = ((request('column_id') != '') ? request('column_id') : $defaultStatus->id);
        $this->leadAgents = LeadAgent::with('user')->whereHas('user', function ($q) {
            $q->where('status', 'active');
        })->get();

        $this->stage = (request()->has('column_id') && !is_null(request()->column_id)) ? PipelineStage::find(request()->column_id) : null;
        $this->contactID = (request()->has('contact_id') && !is_null(request()->contact_id)) ? request()->contact_id : null;

        $this->leadAgentArray = $this->leadAgents->pluck('user_id')->toArray();

        if ((in_array(user()->id, $this->leadAgentArray))) {
            $this->myAgentId = $this->leadAgents->filter(function ($value, $key) {
                return $value->user_id == user()->id;
            })->first()->id;
        }

        $deal = new Deal();

        if ($deal->getCustomFieldGroupsWithFields()) {
            $this->fields = $deal->getCustomFieldGroupsWithFields()->fields;
        }

        $this->leadContacts = Lead::allLeads();
        $this->products = Product::all();
        $this->sources = LeadSource::all();
        $this->stages = PipelineStage::all();
        $this->categories = LeadCategory::all();
        $this->leadPipelines = LeadPipeline::orderBy('default', 'DESC')->get();
        $this->leadStages = PipelineStage::all();
        $this->countries = countries();

        $this->pageTitle = __('modules.deal.createTitle');
        $this->salutations = Salutation::cases();

        if (request()->ajax()) {
            $html = view('leads.ajax.create', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'leads.ajax.create';

        return view('leads.create', $this->data);

    }

    /**
     * @param StoreRequest $request
     * @return array|void
     * @throws \Froiden\RestAPI\Exceptions\RelatedResourceNotFoundException
     */
    public function store(StoreRequest $request)
    {
        $this->addPermission = user()->permission('add_deals');

        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $deal = new Deal();
        $deal->name = $request->name;
        $deal->lead_id = $request->lead_contact;
        $deal->next_follow_up = 'yes';
        $deal->agent_id = $request->agent_id;
        $deal->lead_pipeline_id = $request->pipeline;
        $deal->pipeline_stage_id = $request->stage_id;
        $deal->close_date = Carbon::createFromFormat($this->company->date_format, $request->close_date)->format('Y-m-d');
        $deal->value = ($request->value) ?: 0;
        $deal->currency_id = $this->company->currency_id;
        $deal->save();

        if (!is_null($request->product_id)) {

            $products = $request->product_id;

            foreach ($products as $product) {
                $leadProduct = new LeadProduct();
                $leadProduct->deal_id = $deal->id;
                $leadProduct->product_id = $product;
                $leadProduct->save();
            }
        }

        // To add custom fields data
        if ($request->custom_fields_data) {
            $deal->updateCustomFieldData($request->custom_fields_data);
        }

        // Log search
        $this->logSearchEntry($deal->id, $deal->name, 'deals.show', 'deal');

        $redirectUrl = urldecode($request->redirect_url);

        if ($request->add_more == 'true') {
            $html = $this->create();

            return Reply::successWithData(__('messages.recordSaved'), ['html' => $html, 'add_more' => true]);
        }

        if ($redirectUrl == '') {
            $redirectUrl = route('deals.index');
        }

        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => $redirectUrl]);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $this->deal = Deal::with('currency', 'leadAgent', 'leadAgent.user', 'products', 'leadStage')->findOrFail($id)->withCustomFields();

        $this->productIds = $this->deal->products->pluck('id')->toArray();

        $this->editPermission = user()->permission('edit_deals');

        abort_403(!($this->editPermission == 'all'
            || ($this->editPermission == 'added' && $this->deal->added_by == user()->id)
            || ($this->editPermission == 'owned' && !is_null($this->deal->agent_id) && user()->id == $this->deal->leadAgent->user->id)
            || ($this->editPermission == 'both' && ((!is_null($this->deal->agent_id) && user()->id == $this->deal->leadAgent->user->id)
                    || user()->id == $this->deal->added_by)
            )));

        $this->leadAgents = LeadAgent::with('user')->whereHas('user', function ($q) {
            $q->where('status', 'active');
        })->get();

        if ($this->deal->getCustomFieldGroupsWithFields()) {
            $this->fields = $this->deal->getCustomFieldGroupsWithFields()->fields;
        }

        $this->leadContacts = Lead::all();
        $this->products = Product::all();
        $this->leadPipelines = LeadPipeline::all();

        $this->stages = PipelineStage::where('lead_pipeline_id', $this->deal->lead_pipeline_id)->get();

        $this->pageTitle = __('modules.deal.updateDeal');
        $this->salutations = Salutation::cases();

        if (request()->ajax()) {
            $html = view('leads.ajax.edit', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'leads.ajax.edit';

        return view('leads.create', $this->data);

    }

    /**
     * @param UpdateRequest $request
     * @param int $id
     * @return array|void
     * @throws \Froiden\RestAPI\Exceptions\RelatedResourceNotFoundException
     */
    public function update(UpdateRequest $request, $id)
    {
        $deal = Deal::with('leadAgent', 'leadAgent.user')->findOrFail($id);
        $this->editPermission = user()->permission('edit_deals');

        abort_403(!($this->editPermission == 'all'
            || ($this->editPermission == 'added' && $deal->added_by == user()->id)
            || ($this->editPermission == 'owned' && !is_null($deal->agent_id) && user()->id == $deal->leadAgent->user->id)
            || ($this->editPermission == 'both' && ((!is_null($deal->agent_id) && user()->id == $deal->leadAgent->user->id)
                    || user()->id == $deal->added_by)
            )));

        if ($request->has('agent_id')) {
            $deal->agent_id = $request->agent_id;
        }

        $deal->name = $request->name;
        $deal->next_follow_up = $request->next_follow_up;
        $deal->lead_pipeline_id = $request->pipeline;
        $deal->pipeline_stage_id = $request->stage_id;
        $deal->close_date = Carbon::createFromFormat($this->company->date_format, $request->close_date)->format('Y-m-d');
        $deal->value = ($request->value) ?: 0;
        $deal->currency_id = $this->company->currency_id;
        $deal->save();

        $deal->products()->sync($request->product_id);

        // To add custom fields data
        if ($request->custom_fields_data) {
            $deal->updateCustomFieldData($request->custom_fields_data);
        }

        return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => route('deals.index')]);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $deal = Deal::with('leadAgent', 'leadAgent.user')->findOrFail($id);
        $this->deletePermission = user()->permission('delete_deals');

        abort_403(!($this->deletePermission == 'all'
            || ($this->deletePermission == 'added' && $deal->added_by == user()->id)
            || ($this->deletePermission == 'owned' && !is_null($deal->agent_id) && user()->id == $deal->leadAgent->user->id)
            || ($this->deletePermission == 'both' && ((!is_null($deal->agent_id) && user()->id == $deal->leadAgent->user->id)
                    || user()->id == $deal->added_by)
            )));

        Deal::destroy($id);

        return Reply::success(__('messages.deleteSuccess'));

    }

    /**
     * @param CommonRequest $request
     * @return array
     */
    public function changeStatus(CommonRequest $request)
    {
        $deal = Deal::findOrFail($request->leadID);
        $this->editPermission = user()->permission('edit_deals');
        $this->changeLeadStatusPermission = user()->permission('change_deal_stages');

        abort_403(!(($this->editPermission == 'all' || ($this->editPermission == 'added' && $deal->added_by == user()->id)) || $this->changeLeadStatusPermission == 'all'));

        $deal->status_id = $request->statusID;
        $deal->save();

        return Reply::success(__('messages.recordSaved'));
    }

    public function applyQuickAction(Request $request)
    {
        switch ($request->action_type) {
        case 'delete':
            $this->deleteRecords($request);

            return Reply::success(__('messages.deleteSuccess'));
        case 'change-status':
            $this->changeBulkStatus($request);

            return Reply::success(__('messages.updateSuccess'));
        default:
            return Reply::error(__('messages.selectAction'));
        }
    }

    protected function deleteRecords($request)
    {
        abort_403(user()->permission('delete_deals') != 'all');

        Deal::whereIn('id', explode(',', $request->row_ids))->delete();
    }

    protected function changeBulkStatus($request)
    {
        abort_403(!(user()->permission('edit_deals') == 'all' || user()->permission('change_deal_stages') == 'all'));

        Deal::whereIn('id', explode(',', $request->row_ids))->update(['pipeline_stage_id' => $request->status]);
    }

    protected function changeAgentStatus($request)
    {
        abort_403(user()->permission('edit_deals') != 'all');

        $leads = Deal::with('leadAgent')->whereIn('id', explode(',', $request->row_ids))->get();

        foreach ($leads as $key => $deal) {
            $deal->agent_id = $request->agent_id;
            $deal->save();
        }
    }

    /**
     *
     * @param int $leadID
     * @return void
     */
    public function followUpCreate($dealID)
    {
        $this->addPermission = user()->permission('add_lead_follow_up');

        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $this->dealID = $dealID;
        $this->deal = Deal::findOrFail($dealID);

        return view('leads.followup.create', $this->data);

    }

    public function leadFollowup()
    {
        $tab = request('tab');
        $this->activeTab = $tab ?: 'overview';
        $this->view = 'leads.ajax.follow-up';
        $dataTable = new LeadFollowupDataTable();

        return $dataTable->render('leads.show', $this->data);
    }

    /**
     * @param FollowUpStoreRequest $request
     * @return array|void
     * @throws \Froiden\RestAPI\Exceptions\RelatedResourceNotFoundException
     */
    public function followUpStore(FollowUpStoreRequest $request)
    {
        $this->deal = Deal::findOrFail($request->deal_id);

        $this->addPermission = user()->permission('add_lead_follow_up');

        abort_403(!in_array($this->addPermission, ['all', 'added']));

        if ($this->deal->next_follow_up != 'yes') {
            return Reply::error(__('messages.leadFollowUpRestricted'));
        }

        $followUp = new DealFollowUp();
        $followUp->deal_id = $request->deal_id;
        $followUp->next_follow_up_date = Carbon::createFromFormat($this->company->date_format . ' ' . $this->company->time_format, $request->next_follow_up_date . ' ' . $request->start_time)->format('Y-m-d H:i:s');
        $followUp->remark = $request->remark;
        $followUp->send_reminder = $request->send_reminder;
        $followUp->remind_time = $request->remind_time;
        $followUp->remind_type = $request->remind_type;
        $followUp->status = 'pending';

        $followUp->save();

        return Reply::success(__('messages.recordSaved'));

    }

    public function editFollow($id)
    {
        $this->follow = DealFollowUp::findOrFail($id);
        $this->editPermission = user()->permission('edit_lead_follow_up');
        abort_403(!($this->editPermission == 'all' || ($this->editPermission == 'added' && $this->follow->added_by == user()->id)));

        return view('leads.followup.edit', $this->data);
    }

    public function updateFollow(FollowUpStoreRequest $request)
    {
        $this->deal = Deal::findOrFail($request->deal_id);

        $followUp = DealFollowUp::findOrFail($request->id);
        $this->editPermission = user()->permission('edit_lead_follow_up');

        abort_403(!($this->editPermission == 'all'
            || ($this->editPermission == 'added' && $followUp->added_by == user()->id)
        ));

        if ($this->deal->next_follow_up != 'yes') {
            return Reply::error(__('messages.leadFollowUpRestricted'));
        }

        $followUp->deal_id = $request->deal_id;

        $followUp->next_follow_up_date = Carbon::createFromFormat($this->company->date_format . ' ' . $this->company->time_format, $request->next_follow_up_date . ' ' . $request->start_time)->format('Y-m-d H:i:s');

        $followUp->remark = $request->remark;
        $followUp->send_reminder = $request->send_reminder;
        $followUp->status = $request->status;
        $followUp->remind_time = $request->remind_time;
        $followUp->remind_type = $request->remind_type;

        $followUp->save();

        return Reply::success(__('messages.updateSuccess'));

    }

    public function deleteFollow($id)
    {
        $followUp = DealFollowUp::findOrFail($id);
        $this->deletePermission = user()->permission('delete_lead_follow_up');
        abort_403(!($this->deletePermission == 'all' || ($this->deletePermission == 'added' && $followUp->added_by == user()->id)));

        DealFollowUp::destroy($id);

        return Reply::success(__('messages.deleteSuccess'));
    }

    public function proposals()
    {
        $viewPermission = user()->permission('view_lead_proposals');

        abort_403(!in_array($viewPermission, ['all', 'added']));

        $tab = request('tab');
        $this->activeTab = $tab ?: 'overview';
        $this->view = 'leads.ajax.proposal';
        $dataTable = new ProposalDataTable();

        return $dataTable->render('leads.show', $this->data);
    }

    public function gdpr()
    {
        $dataTable = new LeadGDPRDataTable();
        $tab = request('tab');
        $this->activeTab = $tab ?: 'gdpr';
        $this->view = 'leads.ajax.gdpr';

        return $dataTable->render('leads.show', $this->data);
    }

    public function consent(Request $request)
    {
        $leadId = $request->leadId;
        $this->consentId = $request->consentId;
        $this->leadId = $leadId;

        $this->consent = PurposeConsent::with(['lead' => function ($query) use ($request) {
            $query->where('lead_id', $request->leadId)
                ->orderBy('created_at', 'desc');
        }])
            ->where('id', $request->consentId)
            ->first();

        return view('leads.gdpr.consent-form', $this->data);
    }

    public function saveLeadConsent(Request $request, $id)
    {
        $deal = Deal::findOrFail($id);
        $consent = PurposeConsent::findOrFail($request->consent_id);

        if ($request->consent_description && $request->consent_description != '') {
            $consent->description = trim_editor($request->consent_description);
            $consent->save();
        }

        // Saving Consent Data
        $newConsentLead = new PurposeConsentLead();
        $newConsentLead->deal_id = $deal->id;
        $newConsentLead->purpose_consent_id = $consent->id;
        $newConsentLead->status = trim($request->status);
        $newConsentLead->ip = $request->ip();
        $newConsentLead->updated_by_id = $this->user->id;
        $newConsentLead->additional_description = $request->additional_description;
        $newConsentLead->save();

        return $request->status == 'agree' ? Reply::success(__('messages.consentOptIn')) : Reply::success(__('messages.consentOptOut'));
    }

    public function importLead()
    {
        $this->pageTitle = __('app.importExcel') . ' ' . __('app.menu.deal');

        $this->addPermission = user()->permission('add_deals');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        if (request()->ajax()) {
            $html = view('deals.ajax.import', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'deals.ajax.import';

        return view('leads.create', $this->data);
    }

    public function importStore(ImportRequest $request)
    {
        $this->importFileProcess($request, DealImport::class);

        $view = view('deals.ajax.import_progress', $this->data)->render();

        return Reply::successWithData(__('messages.importUploadSuccess'), ['view' => $view]);
    }

    public function importProcess(ImportProcessRequest $request)
    {
        $batch = $this->importJobProcess($request, DealImport::class, ImportDealJob::class);

        return Reply::successWithData(__('messages.importProcessStart'), ['batch' => $batch]);
    }

    public function notes()
    {
        $dataTable = new DealNotesDataTable();
        $viewPermission = user()->permission('view_deal_note');

        abort_403(!($viewPermission == 'all' || $viewPermission == 'added' || $viewPermission == 'both'));

        $tab = request('tab');
        $this->activeTab = $tab ?: 'profile';

        $this->view = 'leads.ajax.notes';

        return $dataTable->render('leads.show', $this->data);
    }

    public function changeFollowUpStatus(Request $request)
    {
        $id = $request->id;
        $status = $request->status;
        $leadFollowUp = DealFollowUp::find($id);

        if (!is_null($leadFollowUp)) {
            $leadFollowUp->status = $status;
            $leadFollowUp->save();
        }

        return Reply::success(__('messages.leadStatusChangeSuccess'));

    }

    // Get Satges
    public function getStages($id)
    {
        $stages = PipelineStage::where('lead_pipeline_id', $id)->get();

        return Reply::dataOnly(['status' => 'success', 'data' => $stages]);
    }

    // Get Deals
    public function getDeals($id)
    {
        $deals = Deal::allLeads($id);

        return Reply::dataOnly(['status' => 'success', 'data' => $deals]);
    }

    /**
     * @param CommonRequest $request
     * @return array
     */
    public function changeStage(CommonRequest $request)
    {
        $deal = Deal::findOrFail($request->leadID);
        $this->editPermission = user()->permission('edit_deals');
        $this->changeLeadStatusPermission = user()->permission('change_deal_stages');

        abort_403(!(($this->editPermission == 'all' || ($this->editPermission == 'added' && $deal->added_by == user()->id)) || $this->changeLeadStatusPermission == 'all'));

        $deal->pipeline_stage_id = $request->statusID;
        $deal->save();

        return Reply::success(__('messages.recordSaved'));
    }

}
