<?php

namespace App\Notifications;

use App\Models\Holiday;
use Illuminate\Bus\Queueable;
use App\Models\EmailNotificationSetting;
use Illuminate\Notifications\Messages\SlackMessage;

class NewHoliday extends BaseNotification
{

    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    private $holiday;
    private $emailSetting;

    public function __construct(Holiday $holiday)
    {
        $this->holiday = $holiday;
        $this->company = $this->holiday->company;
        $this->emailSetting = EmailNotificationSetting::where('company_id', $this->company->id)->where('slug', 'holiday-notification')->first();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function via()
    {
        $via = [];

        if ($this->emailSetting->send_slack == 'yes' && $this->company->slackSetting->status == 'active') {
            array_push($via, 'slack');
        }

        return $via;
    }

    public function toSlack($notifiable)
    {
        $slack = $notifiable->company->slackSetting;

        if (count($notifiable->employee) > 0 && (!is_null($notifiable->employee[0]->slack_username) && ($notifiable->employee[0]->slack_username != ''))) {
            return (new SlackMessage())
                ->from(config('app.name'))
                ->image($slack->slack_logo_url)
                ->to('@' . $notifiable->employee[0]->slack_username)
                ->content(__('email.holidays.subject') . "\n" . $notifiable->name . "\n" . '*' . __('app.date') . '*: ' . $this->holiday->date->format($this->company->date_format) . "\n" . __('modules.holiday.occasion') . ': ' . $this->holiday->occassion);
        }

        return $this->slackRedirectMessage('email.holidays.subject', $notifiable);

    }

}
