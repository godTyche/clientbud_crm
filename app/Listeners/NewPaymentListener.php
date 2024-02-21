<?php

namespace App\Listeners;

use App\Models\User;
use App\Events\NewPaymentEvent;
use App\Notifications\NewPayment;
use Illuminate\Support\Facades\Notification;

class NewPaymentListener
{

    /**
     * Handle the event.
     *
     * @param NewPaymentEvent $event
     * @return void
     */

    public function handle(NewPaymentEvent $event)
    {
        Notification::send($event->notifyUsers, new NewPayment($event->payment));
    }

}
