<?php

namespace App\Listeners;

use App\Models\User;
use App\Events\HolidayEvent;
use App\Notifications\NewHoliday;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use App\Models\EmployeeDetails;

class HolidayListener
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
     * @param  \App\Events\HolidayEvent  $event
     * @return void
     */
    public function handle(HolidayEvent $event)
    {
        Notification::send( $event->notifyUser, new NewHoliday($event->holiday));
    }

}
