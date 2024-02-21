<?php

namespace App\Observers;

use App\Models\CompanyAddress;

class CompanyAddressObserver
{

    public function creating(CompanyAddress $model)
    {
        if (company()) {
            $model->company_id = company()->id;
        }
    }

}
