<?php

namespace App\Observers;

use App\Models\DealNote;

class DealNoteObserver
{

    /**
     * @param DealNote $dealNote
     */
    public function saving(DealNote $dealNote)
    {
        if (!isRunningInConsoleOrSeeding()) {
            if (user()) {
                $dealNote->last_updated_by = user()->id;
            }
        }
    }

    public function creating(DealNote $dealNote)
    {
        if (!isRunningInConsoleOrSeeding()) {
            if (user()) {
                $dealNote->added_by = user()->id;
            }
        }
    }

}
