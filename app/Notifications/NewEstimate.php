<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Estimate;
use Illuminate\Notifications\Messages\MailMessage;

class NewEstimate extends BaseNotification
{


    /**
     * Create a new notification instance.
     *
     * @return void
     */
    private $estimate;
    private $user;

    public function __construct(Estimate $estimate)
    {
        $this->estimate = $estimate;
        $this->user = User::findOrFail($estimate->client_id);
        $this->company = $this->estimate->company;
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

        if ($notifiable->email_notifications && $notifiable->email != '') {
            array_push($via, 'mail');
        }

        return $via;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    // phpcs:ignore
    public function toMail($notifiable): MailMessage
    {
        $build = parent::build();
        $url = route('front.estimate.show', $this->estimate->hash);
        $url = getDomainSpecificUrl($url, $this->company);

        $content = __('email.estimate.text');

        return $build
            ->subject(__('email.estimate.subject') . ' - ' . config('app.name') . '.')
            ->markdown('mail.email', [
                'url' => $url,
                'content' => $content,
                'themeColor' => $this->company->header_color,
                'actionText' => __('email.estimateDeclined.action'),
                'notifiableName' => $this->user->name
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
            'id' => $this->estimate->id,
            'estimate_number' => $this->estimate->estimate_number
        ];
    }

}
