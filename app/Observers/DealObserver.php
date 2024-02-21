<?php

namespace App\Observers;

use App\Events\DealEvent;
use App\Events\LeadEvent;
use App\Models\Deal;
use App\Notifications\LeadAgentAssigned;
use App\Models\UniversalSearch;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class DealObserver
{

    public function saving(Deal $deal)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $userID = (!is_null(user())) ? user()->id : null;
            $deal->last_updated_by = $userID;
        }

        $deal->next_follow_up = 'yes';
    }

    public function creating(Deal $deal)
    {
        $deal->hash = md5(microtime());

        if (!isRunningInConsoleOrSeeding()) {
            $userID = (!is_null(user())) ? user()->id : null;
            $deal->added_by = $userID;
        }

        if (company()) {
            $deal->company_id = company()->id;
        }
    }

    public function updated(Deal $deal)
    {
        if (!isRunningInConsoleOrSeeding()) {
            if ($deal->isDirty('agent_id')) {
                event(new DealEvent($deal, $deal->leadAgent, 'LeadAgentAssigned'));
            }

            if ($deal->isDirty('pipeline_stage_id') || $deal->isDirty('lead_pipeline_id')) {
                event(new DealEvent($deal, $deal->leadAgent, 'StageUpdated'));
            }
        }
    }

    public function created(Deal $deal)
    {

        if (!isRunningInConsoleOrSeeding()) {
            if (request('agent_id') != '') {
                event(new DealEvent($deal, $deal->leadAgent, 'LeadAgentAssigned'));
            }
            else {
                Notification::send(User::allAdmins($deal->company->id), new LeadAgentAssigned($deal));
            }
        }
    }

    public function deleting(Deal $deal)
    {
        $notifyData = ['App\Notifications\LeadAgentAssigned'];
        \App\Models\Notification::deleteNotification($notifyData, $deal->id);

    }

    public function deleted(Deal $deal)
    {
        UniversalSearch::where('searchable_id', $deal->id)->where('module_type', 'lead')->delete();
    }

}
