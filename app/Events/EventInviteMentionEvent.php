<?php

namespace App\Events;

use App\Models\Event;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventInviteMentionEvent
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $event;
    public $notifyUser;

    public function __construct(Event $event, $notifyUser)
    {

        $this->event = $event;
        $this->notifyUser = $notifyUser;
    }

}
