<?php

namespace App\Observers;

use App\Events\TaskEvent;
use App\Models\TaskUser;

class TaskUserObserver
{

    public function saved(TaskUser $taskUser)
    {

        if (!isRunningInConsoleOrSeeding()) {

            if(!is_null(request()->project_id)) {

                if (user() && $taskUser->user_id != user()->id && is_null($taskUser->task->recurring_task_id) && is_null(request()->mention_user_ids)) {

                    event(new TaskEvent($taskUser->task, $taskUser->user, 'NewTask'));

                }
            }

        }
    }

}
