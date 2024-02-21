<?php

namespace App\Observers;

use App\Events\LeadEvent;
use App\Models\Lead;
use App\Models\UniversalSearch;

class LeadObserver
{

    public function saving(Lead $lead)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $userID = (!is_null(user())) ? user()->id : null;
            $lead->last_updated_by = $userID;
        }
    }

    public function creating(Lead $leadContact)
    {
        $leadContact->hash = md5(microtime());

        if (!isRunningInConsoleOrSeeding()) {
            $userID = (!is_null(user())) ? user()->id : null;
            $leadContact->added_by = $userID;
        }

        if (company()) {
            $leadContact->company_id = company()->id;
        }
    }

    public function created(Lead $leadContact)
    {
        if (!isRunningInConsoleOrSeeding()) {
              event(new LeadEvent($leadContact, 'NewLeadCreated'));
        }
    }

    public function deleting(Lead $leadContact)
    {
        $notifyData = ['App\Notifications\LeadAgentAssigned', 'App\Notifications\NewDealCreated'];
        \App\Models\Notification::deleteNotification($notifyData, $leadContact->id);
    }

    public function deleted(Lead $leadContact)
    {
        UniversalSearch::where('searchable_id', $leadContact->id)->where('module_type', 'lead')->delete();
    }

}
