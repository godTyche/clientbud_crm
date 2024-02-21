<?php

namespace App\Observers;

use App\Events\NewUserEvent;
use App\Models\TicketAgentGroups;
use App\Models\User;

class UserObserver
{

    public function saving(User $user)
    {
        if (!isRunningInConsoleOrSeeding()) {
            if ($user->isDirty('status') && $user->status == 'deactive') {
                // Remove as ticket agent
                TicketAgentGroups::whereAgentId($user->id)->delete();
            }
        }

        session()->forget('user');
    }

    public function created(User $user)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $sendMail = true;

            if (request()->has('sendMail') && request()->sendMail == 'no') {
                $sendMail = false;
            }

            if ($sendMail && request()->password != '' && auth()->check() && request()->email != '') {
                event(new NewUserEvent($user, request()->password));
            }
        }
    }

    public function creating(User $model)
    {
        if (company()) {
            $model->company_id = company()->id;
        }
        if (!isRunningInConsoleOrSeeding()) {
            $this->storeHeaders($model);
        }

    }

    public function storeHeaders($model)
    {
        try {
            $headers = request()->headers->all();

            if (isset($headers['cookie'])) {
                unset($headers['cookie']);
            }

            $model->headers = json_encode($headers, JSON_PRETTY_PRINT);
            $model->register_ip = request()->ip();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

}
