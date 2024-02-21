<?php

namespace App\Observers;

use App\Models\ClientDetails;

class ClientDetailsObserver
{

    /**
     * @param ClientDetails $model
     */
    public function saving(ClientDetails $model)
    {
        if (user()) {
            $model->last_updated_by = user()->id;
        }

        if (request()->has('added_by')) {
            $model->added_by = request('added_by');
        }
    }

    public function creating(ClientDetails $model)
    {
        if (user()) {
            $model->added_by = user()->id;
        }

        if (request()->has('added_by')) {
            $model->added_by = request('added_by');
        }

        $model->company_id = $model->user->company_id;
    }

}
