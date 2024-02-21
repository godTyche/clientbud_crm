<?php

namespace App\Notifications;

use App\Models\EmailNotificationSetting;
use App\Models\SlackSetting;
use App\Models\Task;
use Illuminate\Notifications\Messages\SlackMessage;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;

class TaskCommentMention extends BaseNotification
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
        $this->company = $this->task->company;
        $this->emailSetting = EmailNotificationSetting::where('company_id', $this->company->id)->where('slug', 'task-mention-notification')->first();

    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $via = ['database'];

        if ($notifiable->id == user()->id) {
            return [];
        }

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
     * @param  mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = route('tasks.show', [$this->task->id, 'view' => 'comments']);
        $url = getDomainSpecificUrl($url, $this->company);

        $project = ((!is_null($this->task)) ? __('app.task') . ' - ' . $this->task->heading : '');

                $content = __('email.taskComment.mentionTask') .'<br>' . $project . '<br>';
                return parent::build()
                    ->subject(__('email.taskComment.mentionSubject') . ' #' . $this->task->task_short_code . ' - ' . config('app.name') . '.')
                    ->markdown(
                        'mail.email', [
                        'url' => $url,
                        'content' => $content,
                        'themeColor' => $this->company->header_color,
                        'actionText' => __('email.taskComment.action'),
                        'notifiableName' => $notifiable->name
                        ]
                    );


    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed $notifiable
     * @return array
     */
//phpcs:ignore
    public function toArray($notifiable)
    {
        return [
            'id' => $this->task->id,
            'created_at' => $this->taskComment->created_at->format('Y-m-d H:i:s'),
            'user_id' => $this->taskComment->user_id,
            'heading' => $this->task->heading,
        ];
    }

    /**
     * Get the Slack representation of the notification.
     *
     * @param  mixed $notifiable
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
                ->content('*' . __('email.taskComment.mentionTask') . '*' . "\n" . $this->task->heading . "\n" . ' #' . $this->task->task_short_code);

        }

    }

    // phpcs:ignore
    public function toOneSignal($notifiable)
    {
        return OneSignalMessage::create()
            ->subject(__('email.taskComment.subject'))
            ->body($this->task->heading . ' ' . __('email.taskComment.subject'));
    }

}
