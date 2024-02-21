<?php

namespace App\Observers;

use App\Models\Event;
use App\Services\Google;
use App\Models\Notification;
use App\Models\EventAttendee;
use App\Models\GoogleCalendarModule;

class EventObserver
{

    public function saving(Event $event)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $event->last_updated_by = user()->id;

            // Add/Update event to google calendar
            $event->event_id = $this->googleCalendarEvent($event);
        }
    }

    public function updated(Event $event)
    {
        if (!isRunningInConsoleOrSeeding()) {
            // Add/Update event to google calendar
            $event->event_id = $this->googleCalendarEvent($event);
        }
    }

    public function creating(Event $event)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $event->added_by = user()->id;
        }

        if (company()) {
            $event->company_id = company()->id;
        }
    }

    public function deleting(Event $event)
    {
        /* Start of deleting event from google calendar */
        $google = new Google();
        $googleAccount = company();

        if (company()->google_calendar_status == 'active' && $googleAccount->google_calendar_verification_status == 'verified' && $googleAccount->token) {
            $google->connectUsing($googleAccount->token);
            try {
                if ($event->event_id) {
                    $google->service('Calendar')->events->delete('primary', $event->event_id);
                }
            } catch (\Google\Service\Exception $error) {
                if (is_null($error->getErrors())) {
                    // Delete google calendar connection data i.e. token, name, google_id
                    $googleAccount->name = null;
                    $googleAccount->token = null;
                    $googleAccount->google_id = null;
                    $googleAccount->google_calendar_verification_status = 'non_verified';
                    $googleAccount->save();
                }
            }
        }

        $notifyData = ['App\Notifications\EventInvite', 'App\Notifications\EventReminder'];
        \App\Models\Notification::deleteNotification($notifyData, $event->id);


        /* End of deleting event from google calendar */
    }

    protected function googleCalendarEvent($event)
    {
        $module = GoogleCalendarModule::first();
        $googleAccount = company();

        if (company()->google_calendar_status == 'active' && $googleAccount->google_calendar_verification_status == 'verified' && $googleAccount->token && $module->event_status == 1) {
            $google = new Google();
            $attendiesData = [];

            $attendees = EventAttendee::with(['user'])->whereHas('user', function ($query) {
                $query->where('status', 'active')->where('google_calendar_status', true);
            })->where('event_id', $event->id)->get();

            foreach ($attendees as $attend) {
                if (!is_null($attend->user) && !is_null($attend->user->email)) {
                    $attendiesData[] = ['email' => $attend->user->email];
                }
            }

            if ($event->start_date_time && $event->end_date_time) {

                $startDate = \Carbon\Carbon::parse($event->start_date_time)->shiftTimezone($googleAccount->timezone);
                $endDate = \Carbon\Carbon::parse($event->end_date_time)->shiftTimezone($googleAccount->timezone);

                // Create event
                $google = $google->connectUsing($googleAccount->token);

                $eventData = new \Google_Service_Calendar_Event(array(
                    'summary' => $event->event_name,
                    'location' => $event->where,
                    'description' => $event->description,
                    'colorId' => 3,
                    'start' => array(
                        'dateTime' => $startDate,
                        'timeZone' => $googleAccount->timezone,
                    ),
                    'end' => array(
                        'dateTime' => $endDate,
                        'timeZone' => $googleAccount->timezone,
                    ),
                    'attendees' => $attendiesData,
                    'reminders' => array(
                        'useDefault' => false,
                        'overrides' => array(
                            array('method' => 'email', 'minutes' => 24 * 60),
                            array('method' => 'popup', 'minutes' => 10),
                        ),
                    ),
                ));

                try {
                    if ($event->event_id) {
                        $results = $google->service('Calendar')->events->patch('primary', $event->event_id, $eventData);
                    }
                    else {
                        $results = $google->service('Calendar')->events->insert('primary', $eventData);
                    }

                    return $results->id;
                } catch (\Google\Service\Exception $error) {
                    if (is_null($error->getErrors())) {
                        // Delete google calendar connection data i.e. token, name, google_id
                        $googleAccount->name = null;
                        $googleAccount->token = null;
                        $googleAccount->google_id = null;
                        $googleAccount->google_calendar_verification_status = 'non_verified';
                        $googleAccount->save();
                    }
                }
            }

        }

        return $event->event_id;
    }

}
