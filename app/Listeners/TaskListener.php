<?php

namespace App\Listeners;

use App\Events\TaskEvent;
use App\Models\User;
use App\Notifications\NewTask;
use App\Notifications\TaskUpdated;
use App\Notifications\NewClientTask;
use App\Notifications\TaskCompleted;
use App\Notifications\TaskUpdatedClient;
use App\Notifications\TaskCompletedClient;
use App\Notifications\TaskMention;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class TaskListener
{

    /**
     * Handle the event.
     *
     * @param TaskEvent $event
     * @return void
     */

    public function handle(TaskEvent $event)
    {
        if (!$event->task->is_private) {
            if ($event->notificationName == 'NewClientTask') {
                Notification::send($event->notifyUser, new NewClientTask($event->task));
            }
            elseif ($event->notificationName == 'NewTask') {
                Notification::send($event->notifyUser, new NewTask($event->task));
            }
            elseif ($event->notificationName == 'TaskUpdated') {
                Notification::send($event->notifyUser, new TaskUpdated($event->task));
            }
            elseif ($event->notificationName == 'TaskCompleted') {
                Notification::send($event->notifyUser, new TaskCompleted($event->task, user()));
            }
            elseif ($event->notificationName == 'TaskCompletedClient') {
                Notification::send($event->notifyUser, new TaskCompletedClient($event->task));
            }
            elseif ($event->notificationName == 'TaskUpdatedClient') {
                Notification::send($event->notifyUser, new TaskUpdatedClient($event->task));
            }
            elseif ($event->notificationName == 'TaskMention') {
                Notification::send($event->notifyUser, new TaskMention($event->task));
            }
        }
    }

}
