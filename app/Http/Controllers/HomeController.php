<?php

namespace App\Http\Controllers;

use App\Models\GlobalSetting;
use App\Scopes\CompanyScope;
use Carbon\Carbon;
use Stripe\Stripe;
use App\Models\Deal;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Helper\Files;
use App\Helper\Reply;
use App\Models\Order;
use App\Models\Ticket;
use GuzzleHttp\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\TaskFile;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\TicketType;
use App\Models\CreditNotes;
use App\Models\LeadProduct;
use App\Models\TicketReply;
use App\Scopes\ActiveScope;
use App\Models\InvoiceItems;
use App\Models\ProposalItem;
use App\Models\ProposalSign;
use Illuminate\Http\Request;
use App\Models\ClientDetails;
use App\Models\LeadCustomForm;
use App\Models\TaskboardColumn;
use App\Models\TicketCustomForm;
use Froiden\RestAPI\ApiResponse;
use Illuminate\Support\Facades\DB;
use App\Models\ProjectTimeLogBreak;
use Illuminate\Support\Facades\App;
use Nwidart\Modules\Facades\Module;
use App\Traits\UniversalSearchTrait;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use App\Models\PaymentGatewayCredentials;
use App\Http\Requests\Lead\StorePublicLead;
use App\Http\Requests\ProposalAcceptRequest;
use App\Http\Requests\Stripe\StoreStripeDetail;
use App\Http\Requests\Tickets\StoreCustomTicket;
use App\Models\Lead;
use App\Models\LeadPipeline;
use App\Models\PipelineStage;
use App\Models\TicketGroup;

class HomeController extends Controller
{

    use UniversalSearchTrait;

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        return view('home');
    }

    public function login()
    {
        return redirect(route('login'));
    }

    public function invoice($hash)
    {
        $this->pageTitle = 'app.menu.invoices';
        $this->pageIcon = 'icon-money';

        if (request()->hasValidSignature()) {
            abort(401);
        }

        $this->invoice = Invoice::with('currency', 'project', 'project.client', 'items.invoiceItemImage', 'items', 'items.unit')->where('hash', $hash)->firstOrFail();
        $this->paidAmount = $this->invoice->getPaidAmount();

        $this->discount = 0;

        if ($this->invoice->discount > 0) {
            if ($this->invoice->discount_type == 'percent') {
                $this->discount = (($this->invoice->discount / 100) * $this->invoice->sub_total);
            }
            else {
                $this->discount = $this->invoice->discount;
            }
        }

        $taxList = [];

        $items = InvoiceItems::whereNotNull('taxes')
            ->where('invoice_id', $this->invoice->id)
            ->get();

        foreach ($items as $item) {

            foreach (json_decode($item->taxes) as $tax) {
                $this->tax = InvoiceItems::taxbyid($tax)->first();

                if ($this->tax) {
                    if (!isset($taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'])) {

                        if ($this->invoice->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = ($item->amount - ($item->amount / $this->invoice->sub_total) * $this->discount) * ($this->tax->rate_percent / 100);

                        }
                        else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $item->amount * ($this->tax->rate_percent / 100);
                        }

                    }
                    else {
                        if ($this->invoice->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + (($item->amount - ($item->amount / $this->invoice->sub_total) * $this->discount) * ($this->tax->rate_percent / 100));

                        }
                        else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + ($item->amount * ($this->tax->rate_percent / 100));
                        }
                    }
                }
            }
        }

        $this->taxes = $taxList;
        $this->company = $this->invoice->company;
        $this->credentials = $this->company->paymentGatewayCredentials;
        $this->methods = $this->company->offlinePaymentMethod;
        $this->invoiceSetting = $this->company->invoiceSetting;

        return view('invoice', [
            'companyName' => $this->company->company_name,
            'pageTitle' => $this->pageTitle,
            'pageIcon' => $this->pageIcon,
            'company' => $this->company,
            'invoice' => $this->invoice,
            'paidAmount' => $this->paidAmount,
            'discount' => $this->discount,
            'credentials' => $this->credentials,
            'taxes' => $this->taxes,
            'methods' => $this->methods,
            'invoiceSetting' => $this->invoiceSetting,
        ]);
    }

    public function stripeModal(Request $request)
    {
        $this->invoiceID = $request->invoice_id;
        $this->countries = countries();

        return view('public-payment.stripe.index', $this->data);
    }

    public function paystackModal(Request $request)
    {
        $this->id = $request->id;
        $this->type = $request->type;

        $data = match ($request->type) {
            'invoice' => Invoice::findOrFail($request->id),
            'order' => Order::findOrFail($request->id),
            default => Invoice::findOrFail($request->id),
        };

        $this->company = $data->company;

        return view('public-payment.paystack.index', $this->data);
    }

    public function flutterwaveModal(Request $request)
    {
        $this->id = $request->id;
        $this->type = $request->type;

        return view('public-payment.flutterwave.index', $this->data);
    }

    public function mollieModal(Request $request)
    {
        $this->id = $request->id;
        $this->type = $request->type;

        $data = match ($request->type) {
            'invoice' => Invoice::findOrFail($request->id),
            'order' => Order::findOrFail($request->id),
            default => Invoice::findOrFail($request->id),
        };

        $this->company = $data->company;

        return view('public-payment.mollie.index', $this->data);
    }

    public function authorizeModal(Request $request)
    {
        $this->id = $request->id;
        $this->type = $request->type;

        return view('public-payment.authorize.index', $this->data);
    }

    public function saveStripeDetail(StoreStripeDetail $request)
    {
        $id = $request->invoice_id;
        $this->invoice = Invoice::with(['client', 'project', 'project.client'])->findOrFail($id);
        $this->company = $this->invoice->company;

        if ($this->invoice && $this->invoice->amountDue() == 0) {
            Reply::error(__('messages.invoiceAlreadyPaid'));
        }

        $this->credentials = PaymentGatewayCredentials::where('company_id', $this->company->id)->first();

        $client = null;

        if (!is_null($this->invoice->client_id)) {
            $client = $this->invoice->client;
        }
        else if (!is_null($this->invoice->project_id) && !is_null($this->invoice->project->client_id)) {
            $client = $this->invoice->project->client;
        }

        if (($this->credentials->test_stripe_secret || $this->credentials->live_stripe_secret) && !is_null($client)) {

            Stripe::setApiKey($this->credentials->stripe_mode == 'test' ? $this->credentials->test_stripe_secret : $this->credentials->live_stripe_secret);

            $totalAmount = $this->invoice->amountDue();

            $customer = \Stripe\Customer::create([
                'email' => $client->email,
                'name' => $request->clientName,
                'address' => [
                    'line1' => $request->clientName,
                    'city' => $request->city,
                    'state' => $request->state,
                    'country' => $request->country,
                ],
            ]);

            $intent = \Stripe\PaymentIntent::create([
                'amount' => $totalAmount * 100,
                'currency' => $this->invoice->currency->currency_code,
                'customer' => $customer->id,
                'setup_future_usage' => 'off_session',
                'payment_method_types' => ['card'],
                'description' => $this->invoice->invoice_number . ' Payment',
                'metadata' => ['integration_check' => 'accept_a_payment', 'invoice_id' => $id]
            ]);

            $this->intent = $intent;
        }

        $customerDetail = [
            'email' => $client->email,
            'name' => $request->clientName,
            'line1' => $request->clientName,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
        ];

        $this->customerDetail = $customerDetail;

        $view = view('public-payment.stripe.stripe-payment', $this->data)->render();

        return Reply::dataOnly(['view' => $view, 'intent' => $this->intent]);
    }

    public function downloadInvoice($id)
    {
        $this->invoice = Invoice::whereRaw('md5(id) = ?', $id)->firstOrFail();
        $this->company = $this->invoice->company;
        $this->invoiceSetting = $this->company->invoiceSetting;
        App::setLocale($this->invoiceSetting->locale);
        // Download file uploaded
        if ($this->invoice->file != null) {
            return response()->download(storage_path('app/public/invoice-files') . '/' . $this->invoice->file);
        }

        $pdfOption = $this->domPdfObjectForDownload($this->invoice->id);
        $pdf = $pdfOption['pdf'];
        $filename = $pdfOption['fileName'];

        return $pdf->download($filename . '.pdf');
    }

    public function domPdfObjectForDownload($id)
    {
        $this->invoice = Invoice::with('items')->findOrFail($id);
        $this->company = $this->invoice->company;
        $this->invoiceSetting = $this->company->invoiceSetting;
        App::setLocale($this->invoiceSetting->locale);
        Carbon::setLocale($this->invoiceSetting->locale);
        $this->paidAmount = $this->invoice->getPaidAmount();
        $this->creditNote = 0;

        if ($this->invoice->credit_note) {
            $this->creditNote = CreditNotes::where('invoice_id', $id)
                ->select('cn_number')
                ->first();
        }

        if ($this->invoice->discount > 0) {
            if ($this->invoice->discount_type == 'percent') {
                $this->discount = (($this->invoice->discount / 100) * $this->invoice->sub_total);
            }
            else {
                $this->discount = $this->invoice->discount;
            }
        }
        else {
            $this->discount = 0;
        }

        $taxList = array();

        $items = InvoiceItems::whereNotNull('taxes')
            ->where('invoice_id', $this->invoice->id)
            ->get();

        foreach ($items as $item) {

            foreach (json_decode($item->taxes) as $tax) {
                $this->tax = InvoiceItems::taxbyid($tax)->first();

                if ($this->tax) {
                    if (!isset($taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'])) {

                        if ($this->invoice->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = ($item->amount - ($item->amount / $this->invoice->sub_total) * $this->discount) * ($this->tax->rate_percent / 100);

                        }
                        else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $item->amount * ($this->tax->rate_percent / 100);
                        }

                    }
                    else {
                        if ($this->invoice->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + (($item->amount - ($item->amount / $this->invoice->sub_total) * $this->discount) * ($this->tax->rate_percent / 100));

                        }
                        else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + ($item->amount * ($this->tax->rate_percent / 100));
                        }
                    }
                }
            }
        }

        $this->taxes = $taxList;

        $this->company = $this->invoice->company;

        $this->payments = Payment::with(['offlineMethod'])->where('invoice_id', $this->invoice->id)->where('status', 'complete')->orderBy('paid_on', 'desc')->get();

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('invoices.pdf.' . $this->invoiceSetting->template, $this->data);
        $filename = $this->invoice->invoice_number;

        return [
            'pdf' => $pdf,
            'fileName' => $filename
        ];
    }

    public function app()
    {
        $setting = Company::select('id', 'company_name')->first();

        return ['data' => $setting];
    }

    public function gantt($hash)
    {
        $this->project = Project::with('members', 'members.user')->where('hash', $hash)->firstOrFail();
        $this->company = $this->project->company;
        $this->pageTitle = $this->project->project_name;

        return view('gantt', [
            'company' => $this->company,
            'pageTitle' => $this->pageTitle,
            'project' => $this->project
        ]);
    }

    public function ganttData($ganttProjectId)
    {
        $assignedTo = request('assignedTo');
        $projectTask = request('projectTask');

        if ($assignedTo != 'all') {
            $tasks = Task::projectTasks($ganttProjectId, $assignedTo, 1);
        }
        else {
            $tasks = Task::projectTasks($ganttProjectId, null, 1);
        }

        if ($projectTask) {
            $tasks = $tasks->whereIn('id', explode(',', $projectTask));
        }

        $data = array();

        foreach ($tasks as $key => $task) {

            $data[] = [
                'id' => 'task-' . $task->id,
                'name' => $task->heading,
                'start' => ((!is_null($task->start_date)) ? $task->start_date->format('Y-m-d') : ((!is_null($task->due_date)) ? $task->due_date->format('Y-m-d') : null)),
                'end' => ((!is_null($task->due_date)) ? $task->due_date->format('Y-m-d') : $task->start_date->format('Y-m-d')),
                'progress' => 0,
                'bg_color' => $task->boardColumn->label_color,
                'taskid' => $task->hash,
                'draggable' => true
            ];

            if (!is_null($task->dependent_task_id)) {
                $data[$key]['dependencies'] = 'task-' . $task->dependent_task_id;
            }
        }

        return response()->json($data);
    }

    public function taskDetail($hash)
    {
        $this->task = Task::with('company:id,timezone,favicon,light_logo,date_format,time_format,company_name', 'boardColumn', 'project', 'users', 'label', 'approvedTimeLogs', 'approvedTimeLogs.user', 'comments', 'comments.user')
            ->withCount('subtasks', 'files', 'comments', 'activeTimerAll')
            ->where('hash', $hash)
            ->firstOrFail()
            ->withCustomFields();

        $this->pageTitle = __('app.task') . ' # ' . $this->task->task_short_code;

        if ($this->task->getCustomFieldGroupsWithFields()) {
            $this->fields = $this->task->getCustomFieldGroupsWithFields()->fields;
        }

        $this->employees = User::join('employee_details', 'users.id', '=', 'employee_details.user_id')
            ->leftJoin('project_time_logs', 'project_time_logs.user_id', '=', 'users.id')
            ->leftJoin('designations', 'employee_details.designation_id', '=', 'designations.id');


        $this->employees = $this->employees->select(
            'users.name',
            'users.image',
            'users.id',
            'designations.name as designation_name'
        );

        $this->employees = $this->employees->where('project_time_logs.task_id', '=', $this->task->id);

        $this->employees = $this->employees->groupBy('project_time_logs.user_id')
            ->orderBy('users.name')
            ->get();

        $this->breakMinutes = ProjectTimeLogBreak::taskBreakMinutes($this->task->id);

        $tab = request('view');

        $this->tab = match ($tab) {
            'sub_task' => 'front.tasks.ajax.sub_tasks',
            'history' => 'front.tasks.ajax.history',
            'comments' => 'front.tasks.ajax.comments',
            'time_logs' => 'front.tasks.ajax.timelogs',
            'notes' => 'front.tasks.ajax.notes',
            default => 'front.tasks.ajax.files',
        };

        $this->company = $this->task->company;

        if (request()->ajax()) {
            if (request('json') == true) {
                $html = view($this->tab, $this->data)->render();

                return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
            }

            $html = view('front.tasks.ajax.show', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'front.tasks.ajax.show';

        return view('front.tasks.show', $this->data);

    }

    public function taskFiles($id)
    {
        $this->taskFiles = TaskFile::where('task_id', $id)->get();

        return view('task-files', ['taskFiles' => $this->taskFiles]);
    }

    public function taskboard(Request $request, $hash)
    {
        $project = Project::where('hash', $hash)->firstOrFail();
        $this->company = $project->company;
        $this->pageTitle = $project->project_name . ' ' . __('modules.tasks.taskBoard');

        if (request()->ajax()) {

            $this->boardEdit = false;
            $this->boardDelete = false;

            $boardColumns = TaskboardColumn::withCount(['tasks as tasks_count' => function ($q) use ($project) {
                $q->leftJoin('projects', 'projects.id', '=', 'tasks.project_id')
                    ->leftJoin('users as client', 'client.id', '=', 'projects.client_id')
                    ->leftJoin('task_users', 'task_users.task_id', '=', 'tasks.id')
                    ->leftJoin('users', 'task_users.user_id', '=', 'users.id')
                    ->leftJoin('task_labels', 'task_labels.task_id', '=', 'tasks.id')
                    ->leftJoin('users as creator_user', 'creator_user.id', '=', 'tasks.created_by');

                $q->whereNull('projects.deleted_at');
                $q->where('tasks.is_private', 0);
                $q->where('tasks.project_id', '=', $project->id);
                $q->select(DB::raw('count(distinct tasks.id)'));

            }])
                ->with(['tasks' => function ($q) use ($project) {
                    $q->withCount(['subtasks', 'completedSubtasks', 'comments'])
                        ->leftJoin('projects', 'projects.id', '=', 'tasks.project_id')
                        ->leftJoin('users as client', 'client.id', '=', 'projects.client_id')
                        ->leftJoin('task_users', 'task_users.task_id', '=', 'tasks.id')
                        ->leftJoin('users', 'task_users.user_id', '=', 'users.id')
                        ->leftJoin('task_labels', 'task_labels.task_id', '=', 'tasks.id')
                        ->leftJoin('users as creator_user', 'creator_user.id', '=', 'tasks.created_by')
                        ->groupBy('tasks.id');

                    $q->whereNull('projects.deleted_at');
                    $q->where('tasks.is_private', 0);
                    $q->where('tasks.project_id', '=', $project->id);

                }])
                ->where('taskboard_columns.company_id', $this->company->id)
                ->orderBy('priority', 'asc')
                ->get();
            $result = array();

            foreach ($boardColumns as $key => $boardColumn) {
                $result['boardColumns'][] = $boardColumn;

                $tasks = Task::with(['users', 'project', 'labels'])
                    ->withCount(['subtasks', 'completedSubtasks', 'comments'])
                    ->leftJoin('projects', 'projects.id', '=', 'tasks.project_id')
                    ->leftJoin('users as client', 'client.id', '=', 'projects.client_id')
                    ->leftJoin('task_users', 'task_users.task_id', '=', 'tasks.id')
                    ->leftJoin('users', 'task_users.user_id', '=', 'users.id')
                    ->leftJoin('task_labels', 'task_labels.task_id', '=', 'tasks.id')
                    ->leftJoin('users as creator_user', 'creator_user.id', '=', 'tasks.created_by')
                    ->select('tasks.*')
                    ->where('tasks.board_column_id', $boardColumn->id)
                    ->where('tasks.is_private', 0)
                    ->orderBy('column_priority', 'asc')
                    ->groupBy('tasks.id');

                $tasks->whereNull('projects.deleted_at');
                $tasks->where('tasks.project_id', '=', $project->id);

                $tasks->skip(0)->take($this->company->taskboard_length ?? 10);
                $tasks = $tasks->get();

                $result['boardColumns'][$key]['tasks'] = $tasks;
            }

            $this->result = $result;

            $view = view('taskboard_data', [
                'result' => $this->result,
                'boardEdit' => $this->boardEdit
            ])->render();

            return Reply::dataOnly(['view' => $view]);
        }

        return view('taskboard', [
            'pageTitle' => $this->pageTitle,
            'company' => $this->company,
            'project' => $project
        ]);
    }

    public function taskboardLoadMore(Request $request, $hash)
    {
        $skip = $request->currentTotalTasks;
        $totalTasks = $request->totalTasks;
        $project = Project::where('hash', $hash)->firstOrFail();
        $this->company = $project->company;

        $tasks = Task::with('users', 'project', 'labels')
            ->withCount(['subtasks', 'completedSubtasks', 'comments'])
            ->leftJoin('projects', 'projects.id', '=', 'tasks.project_id')
            ->leftJoin('users as client', 'client.id', '=', 'projects.client_id')
            ->leftJoin('task_users', 'task_users.task_id', '=', 'tasks.id')
            ->leftJoin('users', 'task_users.user_id', '=', 'users.id')
            ->leftJoin('task_labels', 'task_labels.task_id', '=', 'tasks.id')
            ->leftJoin('users as creator_user', 'creator_user.id', '=', 'tasks.created_by')
            ->select('tasks.*')
            ->where('tasks.board_column_id', $request->columnId)
            ->orderBy('column_priority', 'asc')
            ->groupBy('tasks.id');

        $tasks->whereNull('projects.deleted_at');
        $tasks->where('tasks.project_id', '=', $project->id);

        $tasks->skip($skip)->take($this->company->taskboard_length ?? 10);
        $tasks = $tasks->get();
        $this->tasks = $tasks;

        if ($totalTasks <= ($skip + $this->company->taskboard_length)) {
            $loadStatus = 'hide';
        }
        else {
            $loadStatus = 'show';
        }

        $view = view('taskboard_load_more', $this->data)->render();

        return Reply::dataOnly(['view' => $view, 'load_more' => $loadStatus]);
    }

    /**
     * custom lead form
     *
     * @return \Illuminate\Http\Response
     */
    public function leadForm($id)
    {
        $this->withLogo = \request()->get('with_logo');
        $this->styled = \request()->get('styled');

        $this->pageTitle = 'modules.lead.leadForm';
        $this->company = Company::where('hash', $id)->firstOrFail();
        $this->globalSetting = global_setting();
        $this->countries = countries();
        $this->sources = LeadSource::where('company_id', $this->company->id)->get();
        $this->products = Product::where('company_id', $this->company->id)->get();

        $this->leadFormFields = LeadCustomForm::with('customField')
            ->where('status', 'active')
            ->where('company_id', $this->company->id)
            ->orderBy('field_order')->get();

        return view('lead-form', $this->data);
    }

    /**
     * save lead
     *
     * @return \Illuminate\Http\Response
     */
    // public function leadStore(StorePublicLead $request)
    public function leadStore(StorePublicLead $request)
    {
        $company = Company::findOrFail($request->company_id);

        if (global_setting()->google_recaptcha_status == 'active') {
            // Checking is google recaptcha is valid
            $gRecaptchaResponseInput = global_setting()->google_recaptcha_v3_status == 'active' ? 'g_recaptcha' : 'g-recaptcha-response';
            $gRecaptchaResponse = $request->{$gRecaptchaResponseInput};
            $validateRecaptcha = GlobalSetting::validateGoogleRecaptcha($gRecaptchaResponse);

            if (!$validateRecaptcha) {
                return Reply::error(__('auth.recaptchaFailed'));
            }
        }

        $leadPipeline = LeadPipeline::where('default', '1')->where('company_id', $company->id)->first();
        $leadStage = PipelineStage::where('default', '1')->where('lead_pipeline_id', $leadPipeline->id)->where('company_id', $company->id)->first();

        $leadContact = null;

        if(request()->has('email') && !is_null($request->email))
        {
            $leadContact = Lead::where('client_email', $request->email)->first();
        }

        if(is_null($leadContact)){
            $leadContact = new Lead();
        }

        $leadContact->company_id    = $company->id;
        $leadContact->company_name  = (request()->has('company_name') ? $request->company_name : '');
        $leadContact->website       = (request()->has('website') ? $request->website : '');
        $leadContact->address       = (request()->has('address') ? $request->address : '');
        $leadContact->client_name          = (request()->has('name') ? $request->name : '');
        $leadContact->client_email         = (request()->has('email') ? $request->email : '');
        $leadContact->mobile        = (request()->has('mobile') ? $request->mobile : '');
        $leadContact->city          = (request()->has('city') ? $request->city : '');
        $leadContact->state         = (request()->has('state') ? $request->state : '');
        $leadContact->country       = (request()->has('country') ? $request->country : '');
        $leadContact->postal_code   = (request()->has('postal_code') ? $request->postal_code : '');
        $leadContact->save();

        $lead = new Deal();
        $lead->company_id           = $company->id;
        $lead->lead_id      = $leadContact->id;
        $lead->name                 = (request()->has('name') ? $request->name : '');
        $lead->lead_pipeline_id     = $leadPipeline->id;
        $lead->pipeline_stage_id    = $leadStage->id;
        $lead->note                 = (request()->has('message') ? $request->message : null);
        $lead->value                = 0;
        $lead->currency_id          = $company->currency_id;
        $lead->save();

        if (!is_null($request->product)) {

            $products = $request->product;

            foreach($products as $product)
            {
                $leadProduct = new LeadProduct();
                $leadProduct->deal_id = $lead->id;
                $leadProduct->product_id = $product;
                $leadProduct->save();
            }
        }

        // To add custom fields data
        if ($request->custom_fields_data) {
            $lead->updateCustomFieldData($request->custom_fields_data);
        }

        return Reply::success(__('messages.recordSaved'));
    }

    /**
     * custom lead form
     *
     * @return \Illuminate\Http\Response
     */
    public function ticketForm($id)
    {
        $this->pageTitle = 'modules.ticketForm';
        $this->withLogo = \request()->get('with_logo');
        $this->styled = \request()->get('styled');

        $this->company = Company::where('hash', $id)->firstOrFail();

        $this->groups = TicketGroup::where('company_id', $this->company->id)->get();
        $this->ticketFormFields = TicketCustomForm::with('customField')
            ->where('company_id', $this->company->id)
            ->where('status', 'active')
            ->orderBy('field_order', 'asc')
            ->get();

        $this->types = TicketType::where('company_id', $this->company->id)->get();
        App::setLocale($this->company->locale);
        Carbon::setLocale($this->company->locale);
        setlocale(LC_TIME, $this->company->locale . '_' . mb_strtoupper($this->company->locale));

        return view('ticket-form', $this->data);
    }

    /**
     * save lead
     *
     * @return array
     */
    public function ticketStore(StoreCustomTicket $request)
    {
        $company = Company::findOrFail($request->company_id);

        if (global_setting()->google_recaptcha_status == 'active') {

            // Checking is google recaptcha is valid
            $gRecaptchaResponseInput = global_setting()->google_recaptcha_v3_status == 'active' ? 'g_recaptcha' : 'g-recaptcha-response';
            $gRecaptchaResponse = $request->{$gRecaptchaResponseInput};
            $validateRecaptcha = GlobalSetting::validateGoogleRecaptcha($gRecaptchaResponse);

            if (!$validateRecaptcha) {
                return Reply::error(__('auth.recaptchaFailed'));
            }
        }

        /* $rules['g-recaptcha-response'] = 'required'; */
        $existing_user = User::withoutGlobalScope(ActiveScope::class)->select('id', 'email')->where('email', $request->email)->first();
        $newUser = $existing_user;

        if (!$existing_user) {
            $password = str_random(8);
            // create new user
            $client = new User();
            $client->company_id = $request->company_id;
            $client->name = $request->name;
            $client->email = $request->email;
            $client->password = Hash::make($password);
            $client->save();

            // attach role
            $role = Role::withoutGlobalScope(CompanyScope::class)
                ->where('name', 'client')
                ->where('company_id', $company->id)
                ->select('id')
                ->first();

            $role ? $client->attachRole($role->id) : null;

            $clientDetail = new ClientDetails();
            $clientDetail->company_id = $client->company_id;
            $clientDetail->user_id = $client->id;
            $clientDetail->save();

            // Log search
            $this->logSearchEntry($client->id, $client->name, 'clients.edit', 'client');
            $this->logSearchEntry($client->id, $client->email, 'clients.edit', 'client');

            $newUser = $client;
        }

        // Create New Ticket
        $ticket = new Ticket();
        $ticket->company_id = $company->id;
        $ticket->subject = (request()->has('ticket_subject') ? $request->ticket_subject : '');
        $ticket->status = 'open';
        $ticket->user_id = $newUser->id;
        $ticket->type_id = (request()->has('type') ? $request->type : null);
        $ticket->priority = (request()->has('priority') ? $request->priority : 'medium');
        $ticket->group_id = (request()->has('assign_group') ? $request->assign_group : null);
        $ticket->save();

        // Save first message
        $reply = new TicketReply();
        $reply->message = (request()->has('ticket_description') ? $request->ticket_description : '');
        $reply->ticket_id = $ticket->id;
        $reply->user_id = $newUser->id; // Current logged in user
        $reply->save();

        // To add custom fields data
        if ($request->custom_fields_data) {
            $ticket->updateCustomFieldData($request->custom_fields_data);
        }

        return Reply::success(__('messages.ticketCreateSuccess'));
    }

    public function installedModule()
    {
        $message = '';
        $plugins = Module::allEnabled(); /* @phpstan-ignore-line */

        $applicationVersion = trim(
            preg_replace(
                '/\s\s+/',
                ' ',
                !file_exists(File::get(public_path() . '/version.txt')) ? File::get(public_path() . '/version.txt') : '0'
            )
        );
        $enableModules = [];
        $enableModules['application'] = 'worksuite';
        $enableModules['version'] = $applicationVersion;
        $enableModules['worksuite'] = $applicationVersion;

        foreach ($plugins as $plugin) {
            $enableModules[$plugin->getName()] = trim(
                preg_replace(
                    '/\s\s+/',
                    ' ',
                    !file_exists(File::get($plugin->getPath() . '/version.txt')) ? File::get($plugin->getPath() . '/version.txt') : '0'
                )
            );
        }

        if (((int)str_replace('.', '', $enableModules['RestAPI'])) < 110) {
            $message .= 'Please update Rest API module greater then 1.1.0 version';
        }

        if (((int)str_replace('.', '', $enableModules['worksuite'])) < 400) {
            $message .= 'Please update' . config('app.name') . ' greater then 4.0.0 version';
        }

        $enableModules['message'] = $message;

        return ApiResponse::make('Plugin data fetched successfully', $enableModules);
    }

    public function proposal($hash)
    {
        $this->pageTitle = __('app.menu.proposal');
        $this->pageIcon = 'icon-people';

        $this->proposal = Proposal::with(['items', 'unit'])->where('hash', $hash)->firstOrFail();
        $this->company = $this->proposal->company;

        if ($this->proposal->discount > 0) {
            if ($this->proposal->discount_type == 'percent') {
                $this->discount = (($this->proposal->discount / 100) * $this->proposal->sub_total);
            }
            else {
                $this->discount = $this->proposal->discount;
            }
        }
        else {
            $this->discount = 0;
        }

        $this->taxes = ProposalItem::where('type', 'tax')
            ->where('proposal_id', $this->proposal->id)
            ->get();

        $items = ProposalItem::whereNotNull('taxes')
            ->where('proposal_id', $this->proposal->id)
            ->get();

        $taxList = array();

        foreach ($items as $item) {

            foreach (json_decode($item->taxes) as $tax) {
                $this->tax = ProposalItem::taxbyid($tax)->first();

                if ($this->tax) {
                    if (!isset($taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'])) {

                        if ($this->proposal->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = ($item->amount - ($item->amount / $this->proposal->sub_total) * $this->discount) * ($this->tax->rate_percent / 100);

                        }
                        else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $item->amount * ($this->tax->rate_percent / 100);
                        }

                    }
                    else {
                        if ($this->proposal->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + (($item->amount - ($item->amount / $this->proposal->sub_total) * $this->discount) * ($this->tax->rate_percent / 100));

                        }
                        else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + ($item->amount * ($this->tax->rate_percent / 100));
                        }
                    }
                }
            }
        }

        $this->taxes = $taxList;

        $lastViewed = now();
        $ipAddress = request()->ip();
        $this->proposal->last_viewed = $lastViewed;
        $this->proposal->ip_address = $ipAddress;
        $this->proposal->save();

        return view('proposal', [
            'proposal' => $this->proposal,
            'pageTitle' => $this->pageTitle,
            'pageIcon' => $this->pageIcon,
            'taxes' => $this->taxes,
            'discount' => $this->discount,
            'company' => $this->company,
            'invoiceSetting' => $this->company->invoiceSetting,
        ]);
    }

    public function proposalActionStore(ProposalAcceptRequest $request, $id)
    {
        $this->proposal = Proposal::with('signature')->findOrFail($id);

        if ($this->proposal && $this->proposal->signature) {
            return Reply::error(__('messages.alreadySigned'));
        }

        if ($request->type == 'accept') {
            $sign = new ProposalSign();
            $sign->full_name = $request->full_name;
            $sign->proposal_id = $this->proposal->id;
            $sign->email = $request->email;
            $imageName = null;

            if ($request->signature_type == 'signature' && $request->isSignatureNull == 'false') {
                $image = $request->signature;  // your base64 encoded
                $image = str_replace('data:image/png;base64,', '', $image);
                $image = str_replace(' ', '+', $image);
                $imageName = str_random(32) . '.' . 'jpg';
                Files::createDirectoryIfNotExist('proposal/sign');

                File::put(public_path() . '/' . Files::UPLOAD_FOLDER . '/proposal/sign/' . $imageName, base64_decode($image));
                Files::uploadLocalFile($imageName, 'proposal/sign', $this->proposal->company_id);
            }
            else {
                if ($request->hasFile('image')) {
                    $imageName = Files::uploadLocalOrS3($request->image, 'proposal/sign', 300);
                }
            }

            $sign->signature = $imageName;
            $sign->save();

            $this->proposal->status = 'accepted';
        }
        else {
            $this->proposal->client_comment = $request->comment;
            $this->proposal->status = 'declined';
        }

        $this->proposal->save();

        return Reply::success(__('messages.updateSuccess'));
    }

    public function domPdfObjectProposalDownload($id)
    {

        $this->proposal = Proposal::where('hash', $id)->firstOrFail();
        $this->company = $this->proposal->company;

        if ($this->proposal->discount > 0) {
            if ($this->proposal->discount_type == 'percent') {
                $this->discount = (($this->proposal->discount / 100) * $this->proposal->sub_total);
            }
            else {
                $this->discount = $this->proposal->discount;
            }
        }
        else {
            $this->discount = 0;
        }

        $this->taxes = ProposalItem::where('type', 'tax')
            ->where('proposal_id', $this->proposal->id)
            ->get();

        $items = ProposalItem::whereNotNull('taxes')
            ->where('proposal_id', $this->proposal->id)
            ->get();

        $taxList = array();

        foreach ($items as $item) {

            foreach (json_decode($item->taxes) as $tax) {
                $this->tax = ProposalItem::taxbyid($tax)->first();

                if ($this->tax) {
                    if (!isset($taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'])) {

                        if ($this->proposal->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = ($item->amount - ($item->amount / $this->proposal->sub_total) * $this->discount) * ($this->tax->rate_percent / 100);

                        }
                        else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $item->amount * ($this->tax->rate_percent / 100);
                        }

                    }
                    else {
                        if ($this->proposal->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + (($item->amount - ($item->amount / $this->proposal->sub_total) * $this->discount) * ($this->tax->rate_percent / 100));

                        }
                        else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + ($item->amount * ($this->tax->rate_percent / 100));
                        }
                    }
                }
            }
        }

        $this->taxes = $taxList;
        $this->invoiceSetting = $this->company->invoiceSetting;

        App::setLocale($this->invoiceSetting->locale);
        Carbon::setLocale($this->invoiceSetting->locale);

        $pdf = app('dompdf.wrapper');

        $pdf->setOption('enable_php', true);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        $pdf->loadView('proposals.pdf.' . $this->invoiceSetting->template, $this->data);
        $dom_pdf = $pdf->getDomPDF();
        $canvas = $dom_pdf->getCanvas();
        $canvas->page_text(530, 820, 'Page {PAGE_NUM} of {PAGE_COUNT}', null, 10);
        $filename = 'proposal-' . $this->proposal->id;

        return [
            'pdf' => $pdf,
            'fileName' => $filename
        ];
    }

    /**
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadProposal($id)
    {

        $this->proposal = Proposal::where('hash', $id)->firstOrFail();
        $this->company = $this->proposal->company;
        App::setLocale(isset($this->company->locale) ? $this->company->locale : 'en');

        $pdfOption = $this->domPdfObjectProposalDownload($id);
        $pdf = $pdfOption['pdf'];
        $filename = $pdfOption['fileName'];

        return $pdf->download($filename . '.pdf');
    }

    public function invoicePaymentfailed($invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);

        if (request()->gateway == 'Razorpay') {
            $errorMessage = ['code' => request()->errorMessage['code'], 'message' => request()->errorMessage['description']];
        }

        if (request()->gateway == 'Stripe') {
            $errorMessage = ['code' => request()->errorMessage['type'], 'message' => request()->errorMessage['message']];
        }

        /* make new payment entry with status=failed and other details */
        $payment = new Payment();
        $payment->company_id = $invoice->company->id;
        $payment->invoice_id = $invoice->id;
        $payment->currency_id = $invoice->currency_id;
        $payment->amount = $invoice->total;
        $payment->gateway = request()->gateway;
        $payment->paid_on = now();
        $payment->status = 'failed';
        /** @phpstan-ignore-next-line */
        $payment->payment_gateway_response = $errorMessage;
        $payment->save();

        return Reply::error(__('messages.paymentFailed'));
    }

    public function showImage()
    {
        $this->imageUrl = request()->image_url;

        return view('front.image.show_image', $this->data);
    }

    public function syncPermissions()
    {
        return Artisan::call('sync-user-permissions');
    }

    public function changeLang($locale)
    {
        session(['locale' => $locale]);

        return Reply::success(__('messages.updateSuccess'));
    }

}
