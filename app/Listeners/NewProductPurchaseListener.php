<?php

namespace App\Listeners;

use App\Events\NewProductPurchaseEvent;
use App\Notifications\NewProductPurchaseRequest;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class NewProductPurchaseListener
{

    /**
     * NewProductPurchaseListener constructor.
     */
    public function __construct()
    {
        //
    }

    /**
     * @param NewProductPurchaseEvent $event
     */

    public function handle(NewProductPurchaseEvent $event)
    {
        $admins = User::allAdmins($event->invoice->company->id);
        Notification::send($admins, new NewProductPurchaseRequest($event->invoice));
    }

}
