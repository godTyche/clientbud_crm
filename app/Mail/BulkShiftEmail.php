<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Models\EmployeeShiftSchedule;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Contracts\Queue\ShouldQueue;

class BulkShiftEmail extends Mailable implements ShouldQueue
{

    use Queueable, SerializesModels;

    public $dateRange;
    public $userId;
    public $company;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($dateRange, $userId, Company $company)
    {
        $this->dateRange = $dateRange;
        $this->userId = $userId;
        $this->company = $company;
        Config::set('app.logo', $company->masked_logo_url);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $employeeShifts = EmployeeShiftSchedule::with('shift')
            ->whereIn('date', $this->dateRange)
            ->where('user_id', $this->userId)
            ->get();

        return $this->subject(__('email.shiftScheduled.subject'))
            ->markdown('mail.bulk-shift-email', [
                'employeeShifts' => $employeeShifts,
                'company' => $this->company,
            ]);
    }

}
