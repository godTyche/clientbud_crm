<?php

namespace App\Notifications;

use App\Models\EmailNotificationSetting;
use App\Models\SlackSetting;
use App\Models\UserChat;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;

class NewChat extends BaseNotification
{


    /**
     * Create a new notification instance.
     *
     * @return void
     */
    private $userChat;
    private $emailSetting;

    public function __construct(UserChat $userChat)
    {
        $this->userChat = $userChat;
        $this->emailSetting = EmailNotificationSetting::where('slug', 'message-notification')->first();
        $this->company = $this->userChat->company;

    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    // phpcs:ignore
    public function via($notifiable)
    {
        $via = ['database'];

        if ($this->emailSetting->send_email == 'yes' && $notifiable->email_notifications && $notifiable->email != '') {
            array_push($via, 'mail');
        }

        if ($this->emailSetting->send_slack == 'yes' && slack_setting()->status == 'active') {
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
     * @return MailMessage
     */
    // phpcs:ignore
    public function toMail($notifiable): MailMessage
    {
        $build = parent::build();
        $content = $this->userChat->message;
        $url = route('messages.index');
        $url = getDomainSpecificUrl($url, $this->company);

        return $build
            ->subject(__('email.newChat.subject'). ' ' . __('app.from') . ' ' . $this->userChat->fromUser->name)
            ->markdown('mail.email', [
                'url' => $url,
                'content' => $content,
                'themeColor' => $this->company->header_color,
                'actionText' => __('email.newChat.action'),
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
            'id' => $this->userChat->id,
            'user_one' => $this->userChat->user_one,
            'from_name' => $this->userChat->fromUser->name,
        ];
    }

    public function toSlack($notifiable)
    {
        $slack = SlackSetting::setting();
        $url = route('messages.index');
        $url = getDomainSpecificUrl($url, $this->company);

        if (count($notifiable->employee) > 0 && (!is_null($notifiable->employee[0]->slack_username) && ($notifiable->employee[0]->slack_username != ''))) {
            return (new SlackMessage())
                ->from(config('app.name'))
                ->image($slack->slack_logo_url)
                ->to('@' . $notifiable->employee[0]->slack_username)
                ->content('<' . $url . '|' .  __('email.newChat.subject') . ' ' . __('app.from') . ' ' . $this->userChat->fromUser->name . '>');
        }

        return $this->slackRedirectMessage('email.newChat.subject', $notifiable);

    }

    public function toOneSignal()
    {
        return OneSignalMessage::create()
            ->setSubject(__('email.newChat.subject') . ' ' . __('app.from') . ' ' . $this->userChat->fromUser->name)
            ->setBody($this->userChat->message)
            ->setUrl(route('messages.index'));
    }

}
