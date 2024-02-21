<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketEvent
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ticket;
    public $mentionUser;
    public $notificationName;

    public function __construct(Ticket $ticket, $mentionUser, $notificationName,)
    {
        $this->ticket = $ticket;
        $this->mentionUser = $mentionUser;
        $this->notificationName = $notificationName;

    }

}
