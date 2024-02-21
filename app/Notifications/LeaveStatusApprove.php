<?php

namespace App\Notifications;

use App\Models\Leave;
use App\Models\EmailNotificationSetting;
use Illuminate\Notifications\Messages\SlackMessage;

class LeaveStatusApprove extends BaseNotification
{


    /**
     * Create a new notification instance.
     *
     * @return void
     */
    private $leave;
    private $emailSetting;

    public function __construct(Leave $leave)
    {
        $this->leave = $leave;
        $this->company = $this->leave->company;
        $this->emailSetting = EmailNotificationSetting::where('company_id', $this->company->id)->where('slug', 'new-leave-application')->first();

    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
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
        $url = route('leaves.show', $this->leave->id);
        $url = getDomainSpecificUrl($url, $this->company);

        $content = __('email.leave.approve') . '<br>' . __('app.date') . ': ' . $this->leave->leave_date->format($this->company->date_format) . '<br>' . __('app.status') . ': ' . $this->leave->status . '<br>';

        if(!is_null($this->leave->approve_reason))
        {
            $content .= __('messages.reasonForLeaveApproval') . ': ' . $this->leave->approve_reason;
        }

        return $build
            ->subject(__('email.leaves.statusSubject') . ' - ' . config('app.name'))
            ->markdown('mail.email', [
                'url' => $url,
                'content' => $content,
                'themeColor' => $this->company->header_color,
                'actionText' => __('email.leaves.action'),
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
        return $this->leave->toArray();
    }

    public function toSlack($notifiable)
    {
        $slack = $notifiable->company->slackSetting;

        if (count($notifiable->employee) > 0 && (!is_null($notifiable->employee[0]->slack_username) && ($notifiable->employee[0]->slack_username != ''))) {
            return (new SlackMessage())
                ->from(config('app.name'))
                ->to('@' . $notifiable->employee[0]->slack_username)
                ->image($slack->slack_logo_url)
                ->content(__('email.leave.approve') . "\n" . __('app.date') . ': ' . $this->leave->leave_date->format($this->company->date_format) ."\n" . __('app.status') . ': ' . $this->leave->status);
        }

        return $this->slackRedirectMessage('email.leave.approve', $notifiable);
    }

}
