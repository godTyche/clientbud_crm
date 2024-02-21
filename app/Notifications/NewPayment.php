<?php

namespace App\Notifications;

use App\Models\EmailNotificationSetting;
use App\Models\Payment;

class NewPayment extends BaseNotification
{


    /**
     * Create a new notification instance.
     *
     * @return void
     */
    private $payment;
    private $emailSetting;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
        $this->company = $this->payment->company;

        $this->emailSetting = EmailNotificationSetting::where('company_id', $this->company->id)->where('slug', 'payment-notification')->first();
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

        return $via;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage|void
     */
    public function toMail($notifiable)
    {
        $build = parent::build();

        if (($this->payment->project_id && $this->payment->project->client_id != null) || ($this->payment->invoice_id && $this->payment->invoice->client_id != null)) {
            $url = route('payments.show', $this->payment->id);
            $url = getDomainSpecificUrl($url, $this->company);
            $payment_gateway = !is_null($this->payment->gateway) ? $this->payment->gateway . (($this->payment->offlineMethods) ? ' (' . $this->payment->offlineMethods->name . ')' : '') : '--';
            $payment_invoice = $this->payment->invoice->custom_invoice_number ?? '--';
            $projectName = $this->payment->project->project_name ?? '--';
            $clientName = $this->payment->invoice->client->name ?? '--';
            $clientEmail = $this->payment->invoice->client->email ?? '--';
            $subject = __('email.payment.clientsubject') . ' - ' . config('app.name') . '.';

            if ($notifiable->hasRole('admin')) {
                $subject = __('email.payment.subject') . ' - ' . config('app.name') . '.';
                $content = __('email.payment.text') .
                    '<br>' . __('email.payment.amount') . '   :   ' . $this->payment->currency->currency_symbol . $this->payment->amount .
                    '<br>' . __('email.payment.method') . '   :   ' . $payment_gateway .
                    '<br>' . __('email.payment.invoiceNumber') . '   :   ' . $payment_invoice .
                    '<br>' . __('email.payment.Project') . '   :   ' . $projectName .
                    '<br>' . __('email.payment.clientName') . '   :   ' . $clientName .
                    '<br>' . __('email.payment.clientEmail') . '   :   ' . $clientEmail;
            }
            else {
                $content = __('email.payment.text') .
                    '<br>' . __('email.payment.amount') . '   :   ' . $this->payment->currency->currency_symbol . $this->payment->amount .
                    '<br>' . __('email.payment.method') . '   :   ' . $payment_gateway .
                    '<br>' . __('email.payment.invoiceNumber') . '   :   ' . $payment_invoice;
            }

            return $build
                ->subject($subject)
                ->markdown('mail.email', [
                    'url' => $url,
                    'content' => $content,
                    'themeColor' => $this->company->header_color,
                    'actionText' => __('email.payment.action'),
                    'notifiableName' => $notifiable->name,
                ]);
        }
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
        return $this->payment->toArray();
    }

}
