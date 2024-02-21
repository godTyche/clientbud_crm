<?php

namespace App\Listeners;

use App\Models\User;
use App\Events\EstimateAcceptedEvent;
use App\Notifications\EstimateAccepted;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class EstimateAcceptedListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */

    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\EstimateAcceptedEvent  $event
     * @return void
     */
    public function handle(EstimateAcceptedEvent $event)
    {
        $company = $event->estimate->company;
        Notification::send(User::allAdmins($company->id), new EstimateAccepted($event->estimate));
    }

}
