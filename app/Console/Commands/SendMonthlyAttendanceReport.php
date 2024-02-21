<?php

namespace App\Console\Commands;

use App\Mail\MonthlyAttendance;
use App\Models\AttendanceSetting;
use App\Models\Company;
use App\Models\Role;
use App\Notifications\BaseNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendMonthlyAttendanceReport extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-monthly-attendance-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send monthly attendance report';

    public function handle()
    {
        $companies = Company::select('id', 'logo', 'company_name')->get();

        foreach ($companies as $company) {
            $attendanceSetting = AttendanceSetting::where('company_id', $company->id)->first();

            if (!$attendanceSetting->monthly_report) {
                continue;
            }

            $roles = Role::with('users')
                ->whereIn('id', json_decode($attendanceSetting->monthly_report_roles))
                ->get();

            foreach ($roles as $role) {
                foreach ($role->users as $user) {
                    Mail::to($user->email)->send(new MonthlyAttendance($company));
                }
            }
        }

    }

}
