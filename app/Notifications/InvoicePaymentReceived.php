<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\EmailNotificationSetting;
use Illuminate\Notifications\Messages\SlackMessage;

class InvoicePaymentReceived extends BaseNotification
{

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    private $payment;

    private $invoiceSetting;

    private $emailSetting;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
        $this->company = $this->payment->company;
        $this->invoiceSetting = $this->company->invoiceSetting;
        $this->emailSetting = EmailNotificationSetting::where('company_id', $this->company->id)->where('slug', 'invoice-createupdate-notification')->first();

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
        $invoice = Invoice::findOrFail($this->payment->invoice_id);

        if (!is_null($invoice->project) && !is_null($invoice->project->client) && !is_null($invoice->project->client->clientDetails)) {

            $client = $invoice->project->client;
        }
        elseif(!is_null($invoice->client_id) && !is_null($invoice->clientDetails)) {

            $client = $invoice->client;
        }

        if ($invoice->order_id != null) {
            $number = __('app.order') . '#' . $invoice->order_id;
            $message = __('email.invoices.paymentReceivedForOrder');
            $url = route('orders.show', $invoice->order_id);
            $actionBtn = __('email.orders.action');

        }
        else {
            $number = $invoice->invoice_number;
            $message = __('email.invoices.paymentReceivedForInvoice');
            $url = route('invoices.show', $invoice->id);
            $actionBtn = __('email.invoices.action');
        }

        $message .= (isset($client->name)) ? __('app.by').' '.$client->name.'.' : '.';

        $url = getDomainSpecificUrl($url, $this->company);

        $content = $message . ':- ' . '<br>' . $number;

        return $build
            ->subject(__('email.invoices.paymentReceived') . ' - ' . config('app.name'))
            ->markdown('mail.email', [
                'url' => $url,
                'content' => $content,
                'themeColor' => $this->company->header_color,
                'actionText' => $actionBtn,
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

        $invoice = Invoice::find($this->payment->invoice_id);

        if ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number
            ];
        }

        return '';
    }

    public function toSlack($notifiable)
    {
        $slack = $notifiable->company->slackSetting;
        $invoice = Invoice::findOrFail($this->payment->invoice_id);

        if (count($notifiable->employee) > 0 && (!is_null($notifiable->employee[0]->slack_username) && ($notifiable->employee[0]->slack_username != ''))) {
            return (new SlackMessage())
                ->from(config('app.name'))
                ->to('@' . $notifiable->employee[0]->slack_username)
                ->image($slack->slack_logo_url)
                ->content(__('email.hello')  . ' ' .  $notifiable->name ."\n". __('email.invoices.paymentReceivedForInvoice') . ':' . $invoice->invoice_number );
        }

    }

}
