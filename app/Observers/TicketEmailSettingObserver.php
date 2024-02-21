<?php

namespace App\Observers;

use App\Events\TicketEvent;
use App\Events\TicketRequesterEvent;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\TicketEmailSetting;
use App\Models\UniversalSearch;

class TicketEmailSettingObserver
{

    public function creating(TicketEmailSetting $model)
    {
        if (company()) {
            $model->company_id = company()->id;
        }
    }

}
