<?php

namespace App\Listeners;

use App\Events\AutoTaskReminderEvent;
use App\Notifications\AutoTaskReminder;
use Illuminate\Support\Facades\Notification;

class AutoTaskReminderListener
{

    /**
     * Handle the event.
     *
     * @param AutoTaskReminderEvent $event
     * @return void
     */

    public function handle(AutoTaskReminderEvent $event)
    {
        Notification::send($event->task->users, new AutoTaskReminder($event->task));
    }

}
