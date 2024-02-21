<?php

namespace App\Notifications;

use App\Models\EmailNotificationSetting;
use App\Models\Project;
use Illuminate\Notifications\Messages\SlackMessage;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;

class NewProjectNote extends BaseNotification
{


    /**
     * Create a new notification instance.
     *
     * @return void
     */
    private $project;
    private $emailSetting;
    private $event;

    public function __construct(Project $project, $event)
    {
        $this->project = $project;
        $this->event = $event;
        $this->company = $this->project->company;
        $this->emailSetting = EmailNotificationSetting::where('company_id', $this->company->id)->where('slug', 'employee-assign-to-project')->first();
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
        $url = route('projects.show', $this->project->id);
        $url = getDomainSpecificUrl($url, $this->company);

        $content = __('email.projectNote.text') . ' - ' . $this->project->project_name . '<br>';

        return parent::build()
            ->subject(__('email.projectNote.subject') . ' - ' . config('app.name') . '.')
            ->markdown(
                'mail.email', [
                'url' => $url,
                'content' => $content,
                'themeColor' => $this->company->header_color,
                'actionText' => __('email.projectNote.action'),
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
            'id' => $this->project->id,
            'created_at' => $this->project->created_at->format('Y-m-d H:i:s'),
            'heading' => $this->project->project_name,
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
                ->content('*' . __('email.projectNote.subject') . '*' . "\n" . __('email.projectNote.mentionText') . ' - ' . $this->project->project_name);

        }


    }

    // phpcs:ignore
    public function toOneSignal($notifiable)
    {
        return OneSignalMessage::create()
            ->subject(__('email.projectNote.subject'))
            ->body(ucfirst($this->project->project_name) . ' ' . __('email.projectNote.subject'));
    }

}
