<?php

namespace App\Events;

use App\Models\RemovalRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RemovalRequestApproveRejectEvent
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $removalRequest;

    public function __construct(RemovalRequest $removalRequest)
    {
        $this->removalRequest = $removalRequest;
    }

}
