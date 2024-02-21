<?php

namespace App\Listeners;

use App\Events\PaymentReminderEvent;
use App\Notifications\PaymentReminder;
use Illuminate\Support\Facades\Notification;

class PaymentReminderListener
{

    /**
     * Handle the event.
     *
     * @param PaymentReminderEvent $event
     * @return void
     */

    public function handle(PaymentReminderEvent $event)
    {
        Notification::send($event->notifyUser, new PaymentReminder($event->invoice));
    }

}
