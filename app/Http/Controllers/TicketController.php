<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Helper\Reply;
use App\Models\Ticket;
use App\Models\Country;
use App\Models\TicketTag;
use App\Models\TicketType;
use App\Models\TicketGroup;
use App\Models\TicketReply;
use Illuminate\Http\Request;
use App\Models\TicketChannel;
use App\Models\TicketTagList;
use App\Models\TicketAgentGroups;
use Illuminate\Support\Facades\DB;
use App\DataTables\TicketDataTable;
use App\Models\TicketReplyTemplate;
use App\Http\Requests\Tickets\StoreTicket;
use App\Http\Requests\Tickets\UpdateTicket;
use App\Models\Project;

class TicketController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.tickets';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('tickets', $this->user->modules));

            return $next($request);
        });
    }

    public function index(TicketDataTable $dataTable)
    {
        $this->viewPermission = user()->permission('view_tickets');
        abort_403(!in_array($this->viewPermission, ['all', 'added', 'owned', 'both']));

        $managePermission = user()->permission('manage_ticket_agent');

        if (!request()->ajax()) {
            $this->channels = TicketChannel::all();
            $this->groups = $managePermission == 'none' ? null : TicketGroup::with(['enabledAgents' => function ($q) use ($managePermission) {

                if ($managePermission == 'added') {
                    $q->where('added_by', user()->id);
                }
                elseif ($managePermission == 'owned') {
                    $q->where('agent_id', user()->id);
                }
                elseif ($managePermission == 'both') {
                    $q->where('agent_id', user()->id)->orWhere('added_by', user()->id);
                }
                else {
                    $q->get();
                }

            }, 'enabledAgents.user'])->get();

            $this->types = TicketType::all();
            $this->tags = TicketTagList::all();
            $this->projects = Project::allProjects();
        }

        return $dataTable->render('tickets.index', $this->data);

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
        abort_403(user()->permission('delete_tickets') != 'all');

        Ticket::whereIn('id', explode(',', $request->row_ids))->delete();
    }

    protected function changeBulkStatus($request)
    {
        abort_403(user()->permission('edit_tickets') != 'all');

        Ticket::whereIn('id', explode(',', $request->row_ids))->update(['status' => $request->status]);
    }

    public function create()
    {
        $this->addPermission = user()->permission('add_tickets');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $this->groups = TicketGroup::with('enabledAgents', 'enabledAgents.user')->get();
        $this->types = TicketType::all();
        $this->channels = TicketChannel::all();
        $this->templates = TicketReplyTemplate::all();
        $this->employees = User::allEmployees(null, true, ($this->addPermission == 'all' ? 'all' : null));
        $this->clients = User::allClients();
        $this->countries = countries();
        $this->lastTicket = Ticket::orderBy('id', 'desc')->first();
        $this->pageTitle = __('modules.tickets.addTicket');
        $ticket = new Ticket();

        if ($ticket->getCustomFieldGroupsWithFields()) {
            $this->fields = $ticket->getCustomFieldGroupsWithFields()->fields;
        }

        if (request()->default_client) {
            $this->client = User::find(request()->default_client);
        }

        if (request()->ajax()) {
            $html = view('tickets.ajax.create', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'tickets.ajax.create';

        return view('tickets.create', $this->data);

    }

    public function store(StoreTicket $request)
    {

        $ticket = new Ticket();
        $ticket->subject = $request->subject;
        $ticket->status = 'open';
        $ticket->user_id = ($request->requester_type == 'employee') ? $request->user_id : $request->client_id;


        $ticket->type_id = $request->type_id;
        $ticket->priority = $request->priority;
        $ticket->channel_id = $request->channel_id;
        $ticket->group_id = $request->group_id;
        $ticket->project_id = $request->project_id;
        $ticket->save();

        // Save first message
        $reply = new TicketReply();
        $reply->message = trim_editor($request->description);
        $reply->ticket_id = $ticket->id;
        $reply->user_id = $this->user->id; // Current logged in user
        $reply->save();

        // To add custom fields data
        if ($request->custom_fields_data) {
            $ticket->updateCustomFieldData($request->custom_fields_data);
        }

        // Save tags
        $tags = collect(json_decode($request->tags))->pluck('value');

        foreach ($tags as $tag) {
            $tag = TicketTagList::firstOrCreate([
                'tag_name' => $tag
            ]);
            $ticket->ticketTags()->attach($tag);
        }

        // Log search
        $this->logSearchEntry($ticket->ticket_number, $ticket->subject, 'tickets.show', 'ticket');

        $redirectUrl = urldecode($request->redirect_url);

        if ($redirectUrl == '') {
            $redirectUrl = route('tickets.index');
        }

        return Reply::successWithData(__('messages.recordSaved'), ['replyID' => $reply->id, 'redirectUrl' => $redirectUrl]);
    }

    public function show($ticketNumber)
    {
        $this->viewTicketPermission = user()->permission('view_tickets');
        $this->ticket = Ticket::with('project')
            ->where('ticket_number', $ticketNumber)
            ->first();

        abort_if(!$this->ticket, 404);

        $this->ticket = $this->ticket->withCustomFields();
        $this->pageTitle = __('app.menu.ticket') . '#' . $this->ticket->ticket_number;

        abort_403(!(
            $this->viewTicketPermission == 'all'
            || ($this->viewTicketPermission == 'added' && user()->id == $this->ticket->added_by)
            || ($this->viewTicketPermission == 'owned' && (user()->id == $this->ticket->user_id || $this->ticket->agent_id == user()->id))
            || ($this->viewTicketPermission == 'both' && (user()->id == $this->ticket->user_id || $this->ticket->agent_id == user()->id || $this->ticket->added_by == user()->id))
        ));

        $this->groups = TicketGroup::with('enabledAgents', 'enabledAgents.user')->get();
        $this->types = TicketType::all();
        $this->channels = TicketChannel::all();
        $this->templates = TicketReplyTemplate::all();
        $this->ticketChart = $this->ticketChartData($this->ticket->user_id);

        if ($this->ticket->getCustomFieldGroupsWithFields()) {
            $this->fields = $this->ticket->getCustomFieldGroupsWithFields()->fields;
        }

        return view('tickets.edit', $this->data);
    }

    public function ticketChartData($id)
    {
        $labels = ['open', 'pending', 'resolved', 'closed'];
        $data['labels'] = [__('app.open'), __('app.pending'), __('app.resolved'), __('app.closed')];
        $data['colors'] = ['#D30000', '#FCBD01', '#2CB100', '#1d82f5'];
        $data['values'] = [];

        foreach ($labels as $label) {
            $data['values'][] = Ticket::where('user_id', $id)->where('status', $label)->count();
        }

        return $data;
    }

    public function update(UpdateTicket $request, $id)
    {

        $ticket = Ticket::findOrFail($id);
        $ticket->status = $request->status;
        $ticket->save();

        $message = trim_editor($request->message);

        if ($message != '') {
            $reply = new TicketReply();
            $reply->message = $request->message;
            $reply->ticket_id = $ticket->id;
            $reply->user_id = $this->user->id; // Current logged in user
            $reply->save();

            return Reply::successWithData(__('messages.ticketReplySuccess'), ['reply_id' => $reply->id]);
        }

        return Reply::dataOnly(['status' => 'success']);
    }

    public function   destroy($id)
    {
        $ticket = Ticket::findOrFail($id);

        $this->deleteTicketPermission = user()->permission('delete_tickets');
        abort_403(!(
            $this->deleteTicketPermission == 'all'
            || ($this->deleteTicketPermission == 'added' && user()->id == $ticket->added_by)
            || ($this->deleteTicketPermission == 'owned' && (user()->id == $ticket->agent_id || user()->id == $ticket->user_id))
            || ($this->deleteTicketPermission == 'both' && (user()->id == $ticket->agent_id || user()->id == $ticket->added_by || user()->id == $ticket->user_id))
        ));

        Ticket::destroy($id);

        return Reply::success(__('messages.deleteSuccess'));

    }

    public function updateOtherData(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->group_id = $request->group_id;
        $ticket->type_id = $request->type_id;
        $ticket->priority = $request->priority;
        $ticket->channel_id = $request->channel_id;
        $ticket->status = $request->status;

        $agentGroupData = TicketAgentGroups::where('company_id', company()->id)
            ->where('status', 'enabled')
            ->where('group_id', request()->group_id)
            ->pluck('agent_id')
            ->toArray();
        $ticketData = $ticket->where('company_id', company()->id)
            ->where('group_id', request()->group_id)
            ->whereIn('agent_id', $agentGroupData)
            ->whereIn('status', ['open', 'pending'])
            ->whereNotNull('agent_id')
            ->pluck('agent_id')
            ->toArray();

        $diffAgent = array_diff($agentGroupData, $ticketData);

        if (is_null(request()->agent_id)) {

            if (!empty($diffAgent)) {
                $ticket->agent_id = current($diffAgent);

            } else {
                $agentDuplicateCount = array_count_values($ticketData);

                if(!empty($agentDuplicateCount)) {
                    $minVal = min($agentDuplicateCount);
                    $agentId = array_search($minVal, $agentDuplicateCount);
                    $ticket->agent_id = $agentId;
                }

            }
        } else {
            $ticket->agent_id = request()->agent_id;
        }

        $ticket->save();

        // Save tags
        $tags = collect(json_decode($request->tags))->pluck('value');
        TicketTag::where('ticket_id', $ticket->id)->delete();

        foreach ($tags as $tag) {
            $tag = TicketTagList::firstOrCreate([
                'tag_name' => $tag
            ]);
            $ticket->ticketTags()->attach($tag);
        }

        return Reply::success(__('messages.updateSuccess'));
    }

    public function refreshCount(Request $request)
    {
        $viewPermission = user()->permission('view_tickets');

        $tickets = Ticket::with('agent');

        if (!is_null($request->startDate) && $request->startDate != '') {
            $startDate = Carbon::createFromFormat($this->company->date_format, $request->startDate)->toDateString();
            $tickets->where(DB::raw('DATE(`updated_at`)'), '>=', $startDate);
        }

        if (!is_null($request->endDate) && $request->endDate != '') {
            $endDate = Carbon::createFromFormat($this->company->date_format, $request->endDate)->toDateString();
            $tickets->where(DB::raw('DATE(`updated_at`)'), '<=', $endDate);
        }

        if (!is_null($request->agentId) && $request->agentId != 'all') {
            $tickets->where('agent_id', '=', $request->agentId);
        }

        if (!is_null($request->priority) && $request->priority != 'all') {
            $tickets->where('priority', '=', $request->priority);
        }

        if (!is_null($request->channelId) && $request->channelId != 'all') {
            $tickets->where('channel_id', '=', $request->channelId);
        }

        if (!is_null($request->typeId) && $request->typeId != 'all') {
            $tickets->where('type_id', '=', $request->typeId);
        }

        if ($viewPermission == 'added') {
            $tickets->where('added_by', '=', user()->id);
        }

        if ($viewPermission == 'owned') {
            $tickets->where(function ($query) {
                $query->where('user_id', '=', user()->id)
                    ->orWhere('agent_id', '=', user()->id);
            });
        }

        if ($viewPermission == 'both') {
            $tickets->where(function ($query) {
                $query->where('user_id', '=', user()->id)
                    ->orWhere('added_by', '=', user()->id)
                    ->orWhere('agent_id', '=', user()->id);
            });
        }

        $tickets = $tickets->get();

        $openTickets = $tickets->filter(function ($value, $key) {
            return $value->status == 'open';
        })->count();

        $pendingTickets = $tickets->filter(function ($value, $key) {
            return $value->status == 'pending';
        })->count();

        $resolvedTickets = $tickets->filter(function ($value, $key) {
            return $value->status == 'resolved';
        })->count();

        $closedTickets = $tickets->filter(function ($value, $key) {
            return $value->status == 'closed';
        })->count();

        $totalTickets = $tickets->count();

        $ticketData = [
            'totalTickets' => $totalTickets,
            'closedTickets' => $closedTickets,
            'openTickets' => $openTickets,
            'pendingTickets' => $pendingTickets,
            'resolvedTickets' => $resolvedTickets
        ];

        return Reply::dataOnly($ticketData);
    }

    public function changeStatus(Request $request)
    {
        $ticket = Ticket::find($request->ticketId);
        $this->editTicketPermission = user()->permission('edit_tickets');

        abort_403(!(
            $this->editTicketPermission == 'all'
            || ($this->editTicketPermission == 'added' && user()->id == $ticket->added_by)
            || ($this->editTicketPermission == 'owned' && (user()->id == $ticket->user_id || $ticket->agent_id == user()->id))
            || ($this->editTicketPermission == 'both' && (user()->id == $ticket->user_id || $ticket->agent_id == user()->id || $ticket->added_by == user()->id))
        ));

        $ticket->update(['status' => $request->status]);

        return Reply::successWithData(__('messages.updateSuccess'), ['status' => 'success']);
    }

    public function agentGroup($id)
    {
        $groups = TicketGroup::with('enabledAgents', 'enabledAgents.user');
        $groups = $groups->where('id', $id)->first();
        $ticketNumber = request()->ticketNumber;
        $ticket = Ticket::where('ticket_number', $ticketNumber)->first();
        $groupData = [];
        $userData = [];

        if (isset($groups) && count($groups->enabledAgents) > 0)
        {
            $data = [];

            foreach ($groups->enabledAgents as $agent)
            {
                    $selected = (!is_null($ticket) && $agent->user->id == $ticket->agent_id) ? true : false;

                    $url = route('employees.show', [$agent->user->id]);
                    $userData[] = ['id' => $agent->user->id, 'value' => $agent->user->name, 'image' => $agent->user->image_url, 'link' => $url];

                $data[] = view('components.user-option', [
                    'user' => $agent->user,
                    'agent' => false,
                    'pill' => false,
                    'selected' => $selected,
                ])->render();
            }

            $groupData = $userData;
        }
        else
        {
            $data = '<option value="">--</option>';
        }

        return Reply::dataOnly(['data' => $data , 'groupData' => $groupData]);


    }

}
