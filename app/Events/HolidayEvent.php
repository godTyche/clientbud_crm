<?php

namespace App\Events;

use App\Models\Holiday;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class HolidayEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $holiday;
    public $date;
    public $occassion;
    public $notifyUser;

    public function __construct(Holiday $holiday, $date, $occassion, $notifyUser)
    {
        $this->holiday = $holiday;
        $this->date = $date;
        $this->occassion = $occassion;
        $this->notifyUser = $notifyUser;
    }

}
