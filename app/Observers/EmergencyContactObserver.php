<?php

namespace App\Observers;

use App\Helper\Files;
use App\Models\EmergencyContact;
use App\Models\EmployeeDocument;

class EmergencyContactObserver
{

    public function saving(EmergencyContact $emergencyContact)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $emergencyContact->last_updated_by = user()->id;
        }
    }

    public function creating(EmergencyContact $emergencyContact)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $emergencyContact->added_by = user()->id;

        }

        if (company()) {
            $emergencyContact->company_id = company()->id;
        }
    }

}
