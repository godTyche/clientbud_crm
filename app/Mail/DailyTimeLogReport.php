<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Contracts\Queue\ShouldQueue;

class DailyTimeLogReport extends Mailable implements ShouldQueue
{

    use Queueable, SerializesModels;

    public $todayDate;
    public $company;
    public $user;
    public $role;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Company $company, $user, $role)
    {
        $this->todayDate = now()->timezone($company->timezone)->format('Y-m-d');
        $this->company = $company;
        $this->user = $user;
        $this->role = $role;
        Config::set('app.logo', $company->masked_logo_url);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject(__('email.dailyTimelogReport.subject') . ' ' . $this->todayDate)
            ->markdown('mail.timelog.timelog-report', ['date' => $this->todayDate, 'name' => $this->user->name]);
    }

    public function attachments()
    {
        return [
            Attachment::fromData(fn() => $this->domPdfObjectForDownload()['pdf']->output(), 'TimeLog-Report-' . $this->todayDate . '.pdf')
                ->withMime('application/pdf'),
        ];
    }

    public function domPdfObjectForDownload()
    {
        $company = $this->company;

        $employees = User::select('users.id', 'users.name')
            ->with(['timeLogs' => function ($query) use ($company) {
                $query->whereRaw('DATE(start_time) = ?', [$this->todayDate]);
                $query->where('company_id', $company->id);
            }, 'timeLogs.breaks'])
            ->when($this->role->name != 'admin', function ($query) {
                $query->where('users.id', $this->user->id);
            })
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')->onlyEmployee()
            ->where('roles.company_id', $company->id)
            ->groupBy('users.id');

        $employees = $employees->get();

        $employeeData = [];

        foreach ($employees as $employee) {
            $employeeData[$employee->name] = [];
            $employeeData[$employee->name]['timelog'] = 0;
            $employeeData[$employee->name]['timelogBreaks'] = 0;

            if (count($employee->timeLogs) > 0) {

                foreach ($employee->timeLogs as $timeLog) {
                    $employeeData[$employee->name]['timelog'] += $timeLog->total_minutes;

                    if (count($timeLog->breaks) > 0) {
                        foreach ($timeLog->breaks as $timeLogBreak) {
                            $employeeData[$employee->name]['timelogBreaks'] += $timeLogBreak->total_minutes;
                        }
                    }
                }
            }
        }

        $now = $this->todayDate;
        $requestedDate = $now;

        $pdf = app('dompdf.wrapper')->setPaper('A4', 'landscape');

        $options = $pdf->getOptions();
        $options->set(array('enable_php' => true));
        $pdf->getDomPDF()->setOptions($options); /** @phpstan-ignore-line */

        $pdf->loadView('timelog-report', ['employees' => $employeeData, 'date' => $now, 'company' => $company]); /** @phpstan-ignore-line */

        $dom_pdf = $pdf->getDomPDF(); /** @phpstan-ignore-line */
        $canvas = $dom_pdf->getCanvas();
        $canvas->page_text(530, 820, 'Page {PAGE_NUM} of {PAGE_COUNT}', null, 10);

        $filename = 'timelog-report';

        return [
            'pdf' => $pdf,
            'fileName' => $filename
        ];
    }

}
