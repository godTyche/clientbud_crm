<?php

namespace App\Console\Commands;

use App\Events\TimelogEvent;
use App\Models\AttendanceSetting;
use App\Models\Company;
use App\Models\LogTimeFor;
use App\Models\ProjectTimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoStopTimer extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto-stop-timer';

    /**
     * The console command description.p
     *
     * @var string
     */
    protected $description = 'Stop all employees timer after office time.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
        $companies = Company::select('id', 'timezone')->get();

        foreach ($companies as $company) {

            $logTimeFor = LogTimeFor::where('company_id', $company->id)->first();
            $admin = User::allAdmins($company->id)->first();

            $attendanceSetting = AttendanceSetting::where('company_id', $company->id)->first();

            if ($logTimeFor->auto_timer_stop !== 'yes') {
                continue;
            }

            $activeTimers = ProjectTimeLog::with('user', 'activeBreak')
                ->where('project_time_logs.company_id', $company->id)
                ->whereNull('project_time_logs.end_time')
                ->join('users', 'users.id', '=', 'project_time_logs.user_id')
                ->select('project_time_logs.*', 'users.name')
                ->get();

            foreach ($activeTimers as $activeTimer) {
                $endDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $activeTimer->start_time->format('Y-m-d') . ' ' . $attendanceSetting->office_end_time);
                $endDateTime = Carbon::parse($endDateTime)->shiftTimezone($company->timezone)->timestamp;

                if ($endDateTime < Carbon::now($company->timezone)->timestamp) {

                    $activeTimer->end_time = Carbon::createFromTimestamp($endDateTime);
                    $activeTimer->edited_by_user = $admin->id;
                    $activeTimer->save();

                    $activeTimer->total_hours = ((int)$activeTimer->end_time->diff($activeTimer->start_time)->format('%d') * 24) + ((int)$activeTimer->end_time->diff($activeTimer->start_time)->format('%H'));

                    if ($activeTimer->total_hours == 0) {
                        $activeTimer->total_hours = (int)$activeTimer->end_time->diff($activeTimer->start_time)->format('%d') * 24 + (int)$activeTimer->end_time->diff($activeTimer->start_time)->format('%H');
                    }

                    $activeTimer->total_minutes = ((int)$activeTimer->total_hours * 60) + (int)($activeTimer->end_time->diff($activeTimer->start_time)->format('%i'));

                    $activeTimer->saveQuietly();

                    event(new TimelogEvent($activeTimer));

                    // Stop breaktime if active
                    /** @phpstan-ignore-next-line */
                    if (!is_null($activeTimer->activeBreak)) {
                        /** @phpstan-ignore-next-line */
                        $activeBreak = $activeTimer->activeBreak;
                        $activeBreak->end_time = $activeTimer->end_time;
                        $activeBreak->save();
                    }

                }
            }
        }

    }

}
