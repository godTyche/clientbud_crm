<?php

namespace App\Observers;

use App\Models\Deal;
use App\Models\LeadStatus;
use App\Models\User;
use App\Models\UserLeadboardSetting;

class LeadStatusObserver
{

    public function created(LeadStatus $leadStatus)
    {
        if (!isRunningInConsoleOrSeeding() && user()) {
            $employees = User::allEmployees();

            foreach ($employees as $item) {
                UserLeadboardSetting::create([
                    'user_id' => $item->id,
                    'board_column_id' => $leadStatus->id
                ]);
            }
        }
    }

    public function deleting(LeadStatus $leadStatus)
    {
        $defaultStatus = LeadStatus::where('default', 1)->first();
        abort_403($defaultStatus->id == $leadStatus->id);

        Deal::where('status_id', $leadStatus->id)->update(['status_id' => $defaultStatus->id]);
    }

    public function creating(LeadStatus $leadStatus)
    {
        if (company()) {
            $leadStatus->company_id = company()->id;
        }
    }

}
