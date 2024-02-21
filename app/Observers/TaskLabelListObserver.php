<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\TaskLabelList;

class TaskLabelListObserver
{

    public function creating(TaskLabelList $model)
    {
        if(company()) {
            $model->company_id = company()->id;
        }
    }

    public function updated($taskLabel)
    {
        if ($taskLabel->isDirty('project_id') && request()->task_id != null) {

            $task = Task::with('labels')->findOrFail(request()->task_id);

            if ($task->project_id != $taskLabel->project_id) {
                $task->labels()->detach(request()->label_id);
            }

        }
    }

}


