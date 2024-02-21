<?php

namespace App\Events;

use App\Models\Notice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewNoticeEvent
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notice;
    public $notifyUser;
    public $action;

    public function __construct(Notice $notice, $notifyUser, $action)
    {
        $this->notice = $notice;
        $this->notifyUser = $notifyUser;
        $this->action = $action;
    }

}
