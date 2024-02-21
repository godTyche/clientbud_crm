<?php

namespace App\Listeners;

use App\Events\TaskReminderEvent;
use App\Notifications\TaskReminder;
use Illuminate\Support\Facades\Notification;

class TaskReminderListener
{

    /**
     * Handle the event.
     *
     * @param TaskReminderEvent $event
     * @return void
     */

    public function handle(TaskReminderEvent $event)
    {
        Notification::send($event->task->activeUsers, new TaskReminder($event->task));
    }

}
