<?php

namespace App\Console\Commands;

use App\Mail\DailyTimeLogReport;
use App\Mail\MonthlyAttendance;
use App\Models\AttendanceSetting;
use App\Models\Company;
use App\Models\LogTimeFor;
use App\Models\Role;
use App\Models\User;
use App\Notifications\BaseNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyTimelogReport extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-daily-timelog-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily timelog report';

    public function handle()
    {
        $companies = Company::select('id', 'logo', 'company_name')->get();

        foreach ($companies as $company) {
            $timelogSetting = LogTimeFor::where('company_id', $company->id)->first();

            if ($timelogSetting->timelog_report == 1) {
                $roles = Role::with('users')
                    ->where('company_id', $company->id)
                    ->whereIn('id', json_decode($timelogSetting->daily_report_roles))
                    ->get();

                foreach ($roles as $role) {
                    foreach ($role->users as $user) {
                        Mail::to($user->email)->send(new DailyTimeLogReport($company, $user, $role));
                    }
                }
            }

        }

    }

}
