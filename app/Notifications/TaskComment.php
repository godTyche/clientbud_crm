<?php

namespace App\Notifications;

use App\Models\EmailNotificationSetting;
use App\Models\Task;
use Illuminate\Notifications\Messages\SlackMessage;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;

class TaskComment extends BaseNotification
{


    /**
     * Create a new notification instance.
     *
     * @return void
     */
    private $task;
    private $taskComment;
    private $emailSetting;

    public function __construct(Task $task, $taskComment)
    {
        $this->task = $task;
        $this->taskComment = $taskComment;
        $this->emailSetting = EmailNotificationSetting::userAssignTask();
        $this->company = $this->task->company;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {

        if ($notifiable->id == user()->id) {
            return [];
        }

        $via = ['database'];

        if ($this->emailSetting->send_email == 'yes' && $notifiable->email_notifications && $notifiable->email != '') {
            array_push($via, 'mail');
        }

        if ($this->emailSetting->send_slack == 'yes' && $this->company->slackSetting->status == 'active') {
            array_push($via, 'slack');
        }

        if ($this->emailSetting->send_push == 'yes') {

            array_push($via, OneSignalChannel::class);
        }

        return $via;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $build = parent::build();
        $url = route('tasks.show', [$this->task->id, 'view' => 'comments']);
        $url = getDomainSpecificUrl($url, $this->company);

        $heading = __('email.taskComment.subject') . ' - ' . $this->task->heading . ' #' . $this->task->task_short_code . '<br>';
        $projectName = ($this->task->project != null) ? '<br>' . __('app.project') . ' - ' . $this->task->project->project_name . '<br>' : '<br>';
        $comment = '<br>' . __('app.comment') . ' - ' . $this->taskComment->comment . '<br>';
        $commentBy = ($this->taskComment && $this->taskComment->user) ? __('email.taskComment.commentedBy') . ' - ' . $this->taskComment->user->name . '<br>' : '<br>';

        $content = $heading . $projectName . $comment . $commentBy;

        return $build
            ->subject(__('email.taskComment.subject') . ' #' . $this->task->task_short_code . ' - ' . config('app.name') . '.')
            ->markdown('mail.email', [
                'url' => $url,
                'content' => $content,
                'themeColor' => $this->company->header_color,
                'actionText' => __('email.taskComment.action'),
                'notifiableName' => $notifiable->name
            ]);
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
            'id' => $this->task->id,
            'created_at' => $this->taskComment->created_at->format('Y-m-d H:i:s'),
            'user_id' => $this->taskComment->user_id,
            'heading' => $this->task->heading
        ];
    }

    /**
     * Get the Slack representation of the notification.
     *
     * @param mixed $notifiable
     * @return SlackMessage
     */
    public function toSlack($notifiable)
    {
        $slack = $notifiable->company->slackSetting;

        if (count($notifiable->employee) > 0 && (!is_null($notifiable->employee[0]->slack_username) && ($notifiable->employee[0]->slack_username != ''))) {
            return (new SlackMessage())
                ->from(config('app.name'))
                ->image($slack->slack_logo_url)
                ->to('@' . $notifiable->employee[0]->slack_username)
                ->content('*' . __('email.taskComment.subject') . '*' . "\n" . $this->task->heading . "\n" . ' #' . $this->task->task_short_code);
        }

    }

    // phpcs:ignore
    public function toOneSignal($notifiable)
    {
        return OneSignalMessage::create()
            ->setSubject(__('email.taskComment.subject'))
            ->setBody($this->task->heading . ' ' . __('email.taskComment.subject'));
    }

}
