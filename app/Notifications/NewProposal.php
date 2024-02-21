<?php

namespace App\Notifications;

use App\Http\Controllers\ProposalController;
use App\Models\Proposal;

class NewProposal extends BaseNotification
{


    /**
     * Create a new notification instance.
     *
     * @return void
     */

    private $proposal;

    public function __construct(Proposal $proposal)
    {
        $this->proposal = $proposal;
        $this->company = $this->proposal->company;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function via()
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage|void
     */
    // phpcs:ignore
    public function toMail($notifiable)
    {
        $newProposal = parent::build();
        $proposalController = new ProposalController();

        if ($pdfOption = $proposalController->domPdfObjectForDownload($this->proposal->id)) {
            $pdf = $pdfOption['pdf'];
            $filename = $pdfOption['fileName'];

            $url = route('front.proposal', $this->proposal->hash);
            $url = getDomainSpecificUrl($url, $this->company);

            $content = __('email.proposal.text') . '<br>';

            $newProposal->subject(__('email.proposal.subject'))
                ->markdown('mail.email', [
                    'url' => $url,
                    'content' => $content,
                    'themeColor' => $this->company->header_color,
                    'actionText' => __('app.view') . ' ' . __('app.proposal'),
                    'notifiableName' => $this->proposal->lead->client_name
                ]);

            $newProposal->attachData($pdf->output(), $filename . '.pdf');

            return $newProposal;
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
        return $this->proposal->toArray();
    }

}
