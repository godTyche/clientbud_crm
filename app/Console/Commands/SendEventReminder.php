<?php

namespace App\Console\Commands;

use App\Events\EventReminderEvent;
use App\Models\Company;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendEventReminder extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-event-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send event reminder to the attendees before time specified in database';

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
        $companies = Company::select('id', 'timezone')->get();

        foreach ($companies as $company) {

            $events = Event::with('attendee')
                ->select('id', 'event_name', 'label_color', 'where', 'description', 'start_date_time', 'end_date_time', 'repeat', 'send_reminder', 'remind_time', 'remind_type', 'company_id')
                ->where('start_date_time', '>=', Carbon::now($company->timezone))
                ->where('send_reminder', 'yes')
                ->where('company_id', $company->id)
                ->get();

            foreach ($events as $event) {
                $reminderDateTime = $this->calculateReminderDateTime($event, $company);

                if ($reminderDateTime->equalTo(Carbon::now($company->timezone)->startOfMinute())) {
                    event(new EventReminderEvent($event));
                }
            }
        }

    }

    public function calculateReminderDateTime(Event $event, $company)
    {
        $time = $event->remind_time;
        $type = $event->remind_type;

        $reminderDateTime = '';

        switch ($type) {
        case 'day':
            $reminderDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $event->start_date_time, $company->timezone)->subDays($time);
            break;
        case 'hour':
            $reminderDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $event->start_date_time, $company->timezone)->subHours($time);
            break;
        case 'minute':
            $reminderDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $event->start_date_time, $company->timezone)->subMinutes($time);
            break;
        }

        return $reminderDateTime;
    }

}
