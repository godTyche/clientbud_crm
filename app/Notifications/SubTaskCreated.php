<?php

namespace App\Notifications;

use App\Models\SubTask;

class SubTaskCreated extends BaseNotification
{


    /**
     * Create a new notification instance.
     *
     * @return void
     */
    private $subTask;

    public function __construct(SubTask $subTask)
    {
        $this->subTask = $subTask;
        $this->company = $this->subTask->task->company;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    //phpcs:ignore
    public function via($notifiable)
    {
        $via = ['database'];

        return $via;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
//phpcs:ignore
    public function toArray($notifiable)
    {
        return [
            'id' => $this->subTask->task->id,
            'created_at' => $this->subTask->created_at->format('Y-m-d H:i:s'),
            'heading' => $this->subTask->title
        ];
    }

}
