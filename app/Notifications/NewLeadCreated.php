<?php

namespace App\Notifications;

use App\Models\EmailNotificationSetting;
use App\Models\Lead;

class NewLeadCreated extends BaseNotification
{

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    private $leadContact;
    private $emailSetting;

    public function __construct(Lead $leadContact)
    {
        $this->leadContact = $leadContact;
        $this->company = $this->leadContact->company;
        $this->emailSetting = EmailNotificationSetting::where('company_id', $this->company->id)->where('slug', 'lead-notification')->first();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $via = array('database');

        if ($this->emailSetting->send_email == 'yes' && $notifiable->email_notifications && $notifiable->email != '') {
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
    public function toMail($notifiable)
    {
        $build = parent::build();
        $url = route('lead-contact.show', $this->leadContact->id);
        $url = getDomainSpecificUrl($url, $this->company);

        $salutation = ($this->leadContact->salutation ? $this->leadContact->salutation->label() : '') .' ';
        $leadEmail = __('modules.lead.clientEmail') . ': ';
        $clientEmail = !is_null($this->leadContact->email) ? $leadEmail : '';
        $content = __('email.lead.subject') . '<br>' . __('modules.lead.clientName') . ': ' . $salutation . $this->leadContact->client_name . '<br>' . $clientEmail . $this->leadContact->email;

        return $build
            ->subject(__('email.lead.subject') . ' - ' . config('app.name'))
            ->markdown('mail.email', [
                'url' => $url,
                'content' => $content,
                'themeColor' => $this->company->header_color,
                'actionText' => __('email.lead.action'),
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
            'id' => $this->leadContact->id,
            'name' => $this->leadContact->client_name,
            'agent_id' => $notifiable->id,
            'added_by' => $this->leadContact->added_by
        ];
    }

}
