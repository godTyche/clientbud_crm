<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Holiday;
use App\Events\HolidayEvent;
use App\Models\EmployeeDetails;

class HolidayObserver
{

    public function saving(Holiday $lead)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $lead->last_updated_by = user()->id;
        }
    }

    public function creating(Holiday $lead)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $lead->added_by = user()->id;
        }

        if (company()) {
            $lead->company_id = company()->id;
        }
    }

    public function created (Holiday $holiday)
    {
        $notifyUser = User::allEmployees();
        event(new HolidayEvent($holiday, request()->date, request()->occassion, $notifyUser));
    }

}
