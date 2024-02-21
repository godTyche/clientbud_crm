<?php

namespace App\Observers;

use App\Models\Attendance;

class AttendanceObserver
{

    public function saving(Attendance $attendance)
    {
        if (user()) {
            $attendance->last_updated_by = user()->id;
        }


    }

    public function creating(Attendance $attendance)
    {
        if (user()) {
            $attendance->added_by = user()->id;
        }

        if ($attendance->work_from_type != 'other') {
            $attendance->working_from = $attendance->work_from_type;
        }

        $attendance->company_id = $attendance->user->company_id;
    }

}
