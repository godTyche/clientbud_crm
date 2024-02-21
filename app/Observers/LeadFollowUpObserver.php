<?php

namespace App\Observers;

use App\Services\Google;
use App\Models\LeadAgent;
use App\Models\DealFollowUp;
use App\Models\Notification;
use App\Models\GoogleCalendarModule;

class LeadFollowUpObserver
{

    public function saving(DealFollowUp $leadFollowUp)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $leadFollowUp->last_updated_by = user()->id;
        }
    }

    public function creating(DealFollowUp $leadFollowUp)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $leadFollowUp->added_by = user()->id;
        }
    }

    public function created(DealFollowUp $leadFollowUp)
    {
        if (!isRunningInConsoleOrSeeding()) {

            /* Add google calendar event */
            if (!is_null($leadFollowUp->next_follow_up_date)) {
                $leadFollowUp->event_id = $this->googleCalendarEvent($leadFollowUp);
            }
        }
    }

    public function updating(DealFollowUp $leadFollowUp)
    {
        if (!isRunningInConsoleOrSeeding()) {

            /* Update google calendar event */
            if (!is_null($leadFollowUp->next_follow_up_date)) {
                $leadFollowUp->event_id = $this->googleCalendarEvent($leadFollowUp);
            }
        }
    }

    public function deleting(DealFollowUp $leadFollowUp)
    {
        /* Start of deleting event from google calendar */
        $google = new Google();
        $googleAccount = company();

        if (company()->google_calendar_status == 'active' && $googleAccount->google_calendar_verification_status == 'verified' && $googleAccount->token) {
            $google->connectUsing($googleAccount->token);
            try {
                if ($leadFollowUp->event_id) {
                    $google->service('Calendar')->events->delete('primary', $leadFollowUp->event_id);
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

        $notificationModel = ['App\Notifications\AutoFollowUpReminder'];
        Notification::whereIn('type', $notificationModel)
            ->whereNull('read_at')
            ->where(function ($q) use ($leadFollowUp) {
                $q->where('data', 'like', '{"follow_up_id":' . $leadFollowUp->id . ',%');
            })->delete();


        /* End of deleting event from google calendar */
    }

    protected function googleCalendarEvent($event)
    {
        $googleAccount = company();
        $module = GoogleCalendarModule::first();

        if (company()->google_calendar_status == 'active' && $googleAccount->google_calendar_verification_status == 'verified' && $googleAccount->token && $module->lead_status == 1) {
            $google = new Google();
            $attendiesData = [];


            $attendee = $event->lead?->leadAgent;

            if (!is_null($attendee->user) && !is_null($attendee->user->email)) {
                $attendiesData[] = ['email' => $attendee->user->email];
            }

            if ($event->next_follow_up_date) {
                $dateTime = \Carbon\Carbon::parse($event->next_follow_up_date)->shiftTimezone($googleAccount->timezone);

                // Create event
                $google = $google->connectUsing($googleAccount->token);

                $eventData = new \Google_Service_Calendar_Event(array(
                    'summary' => __('app.lead') . ' ' . __('app.followUp') . ': ' . $event->remark,
                    'location' => '',
                    'description' => $event->remark,
                    'colorId' => 5,
                    'start' => array(
                        'dateTime' => $dateTime,
                        'timeZone' => $googleAccount->timezone,
                    ),
                    'end' => array(
                        'dateTime' => $dateTime,
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
