<?php

namespace App\Console\Commands;

use App\Events\AutoTaskReminderEvent;
use App\Models\Company;
use App\Models\Task;
use App\Models\TaskboardColumn;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAutoTaskReminder extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-auto-task-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send task reminders';

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {

        $companies = Company::select(['id', 'before_days', 'after_days', 'timezone'])->get();

        foreach ($companies as $company) {

            $now = Carbon::now($company->timezone);

            $completedTaskColumn = TaskboardColumn::where('company_id', $company->id)->where('slug', 'completed')->first();

            if ($company->before_days > 0) {
                $beforeDeadline = $now->clone()->subDays($company->before_days)->format('Y-m-d');
                $tasks = Task::select('id')
                    ->where('due_date', $beforeDeadline)
                    ->where('company_id', $company->id)
                    ->where('board_column_id', '<>', $completedTaskColumn->id)
                    ->get();

                foreach ($tasks as $task) {
                    event(new AutoTaskReminderEvent($task));
                }
            }

            if ($company->after_days > 0) {
                $now->clone()->addDays($company->after_days)->format('Y-m-d');
            }
        }

    }

}
