<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Models\EmployeeShiftSchedule;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Contracts\Queue\ShouldQueue;

class EmailMarketingMail extends Mailable implements ShouldQueue
{

    use Queueable, SerializesModels;

    public $subject;
    public $content;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject, $content, Company $company)
    {
        $this->subject = $subject;
        $this->content = $content;
        Config::set('app.logo', $company->masked_logo_url);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->subject($this->subject)
            ->markdown('mail.email', [
                'content' => $this->content,
            ]);
    }

}
