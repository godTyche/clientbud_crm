<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Support\HtmlString;

class InvoiceReminder extends BaseNotification
{

    private $invoice;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($invoice)
    {
        $this->invoice = $invoice;
        $this->company = $this->invoice->company;
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

        if ($notifiable->email != '') {
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
        $setting = $this->company;
        $invoice_setting = $this->company->invoiceSetting->send_reminder;
        $invoice_number = $this->invoice->invoice_number;

        $url = route('front.invoice', $this->invoice->hash);
        $url = getDomainSpecificUrl($url, $this->company);

        $content = __('email.invoiceReminder.text') . ' ' . Carbon::now($setting->timezone)->addDays($invoice_setting)->toFormattedDateString() . '<br>' . new HtmlString($invoice_number) . '<br>' . __('email.messages.loginForMoreDetails');

        return $build
            ->subject(__('email.invoiceReminder.subject') . ' - ' . config('app.name'))
            ->markdown('mail.email', [
                'url' => $url,
                'content' => $content,
                'themeColor' => $this->company->header_color,
                'actionText' => __('email.invoiceReminder.action'),
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
        return $notifiable->toArray();
    }

}
