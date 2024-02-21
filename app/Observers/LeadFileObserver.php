<?php

namespace App\Observers;

use App\Helper\Files;
use App\Models\DealFile;

class LeadFileObserver
{

    public function saving(DealFile $leadFile)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $leadFile->last_updated_by = user()->id;
        }
    }

    public function creating(DealFile $leadFile)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $leadFile->added_by = user()->id;
        }
    }

    public function deleting(DealFile $leadFile)
    {
        Files::deleteFile($leadFile->hashname, DealFile::FILE_PATH . '/' . $leadFile->lead_id);
    }

}
