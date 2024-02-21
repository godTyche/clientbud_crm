<?php

namespace App\Notifications;

use App\Http\Controllers\InvoiceController;
use App\Models\EmailNotificationSetting;
use App\Models\Invoice;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;

class PaymentReminder extends BaseNotification
{


    /**
     * Create a new notification instance.
     *
     * @return void
     */
    private $invoice;
    private $user;
    private $emailSetting;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
        $this->company = $this->invoice->company;

        if ($invoice->project_id != null && $invoice->project_id != '') {
            $this->user = $invoice->project->client;
        }
        elseif ($invoice->client_id != null && $invoice->client_id != '') {
            $this->user = $invoice->client;
        }

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
     * @param mixed $notifiable
     * @return MailMessage
     */
    // phpcs:ignore
    public function toMail($notifiable): MailMessage
    {
        $build = parent::build();
        // For Sending pdf to email
        $invoiceController = new InvoiceController();

        if ($pdfOption = $invoiceController->domPdfObjectForDownload($this->invoice->id)) {
            $pdf = $pdfOption['pdf'];
            $filename = $pdfOption['fileName'];

            $url = route('front.invoice', [$this->invoice->hash]);
            $paymentUrl = getDomainSpecificUrl($url, $this->company);

            $content = __('app.invoiceNumber') . ' : ' . $this->invoice->invoice_number . '<p>
                <b style="color: green">' . __('app.dueDate') . ' : ' . $this->invoice->due_date->format($this->company->date_format) . '</b>
            </p>';

            $build->subject(__('email.paymentReminder.subject') . ' - ' . config('app.name') . '.')
                ->greeting(__('email.hello') . ' ' . $this->user->name . '!')
                ->markdown('mail.payment.reminder', [
                    'paymentUrl' => $paymentUrl,
                    'content' => $content,
                    'themeColor' => $this->company->header_color
                ]);
            $build->attachData($pdf->output(), $filename . '.pdf');

            return $build;
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
        return [
            'id' => $this->invoice->id,
            'created_at' => $this->invoice->created_at->format('Y-m-d H:i:s'),
            'heading' => $this->invoice->invoice_number
        ];
    }

    /**
     * Get the Slack representation of the notification.
     *
     * @param mixed $notifiable
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
                ->content('*' . __('email.paymentReminder.subject') . '*' . "\n" . __('app.invoice') . ' - ' . $this->invoice->invoice_number);
        }

    }

    // phpcs:ignore
    public function toOneSignal($notifiable)
    {
        return OneSignalMessage::create()
            ->setSubject(__('email.paymentReminder.subject'))
            ->setBody($this->invoice->heading);
    }

}
