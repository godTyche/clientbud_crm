<?php

namespace App\Observers;

use App\Events\TicketReplyEvent;
use App\Mail\TicketReply as MailTicketReply;
use App\Models\TicketActivity;
use App\Models\TicketEmailSetting;
use App\Models\TicketReply;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class TicketReplyObserver
{

    public function saving(TicketReply $ticketReply)
    {
        if (user() && is_null($ticketReply->ticket->agent_id)) {
            $ticket = $ticketReply->ticket;
            $ticket->save();
        }
    }

    public function created(TicketReply $ticketReply)
    {
        $ticketReply->ticket->touch();
        $ticketEmailSetting = TicketEmailSetting::where('company_id', $ticketReply->ticket->company_id)->first();

        if (isRunningInConsoleOrSeeding()) {
            return true;
        }

        if ($ticketEmailSetting->status == 1) {
            if (!is_null($ticketReply->ticket->agent_id)) {
                if ($ticketReply->ticket->agent_id == user()->id) {
                    $toEmail = $ticketReply->ticket->client->email;

                }
                else {
                    $toEmail = $ticketReply->ticket->agent->email;
                }

                if (smtp_setting()->mail_connection == 'database') {
                    Mail::to($toEmail)->queue(new MailTicketReply($ticketReply, $ticketEmailSetting));

                }
                else {
                    Mail::to($toEmail)->send(new MailTicketReply($ticketReply, $ticketEmailSetting));
                }

            } else if(!in_array('client', user_roles())) {
                $toEmail = $ticketReply->ticket->client->email;

                if (smtp_setting()->mail_connection == 'database') {
                    Mail::to($toEmail)->queue(new MailTicketReply($ticketReply, $ticketEmailSetting));

                }
                else {
                    Mail::to($toEmail)->send(new MailTicketReply($ticketReply, $ticketEmailSetting));
                }
            }

        }

        $message = trim_editor($ticketReply->message);

        if ($message != '') {
            if (count($ticketReply->ticket->reply) > 1) {

                if (!is_null($ticketReply->ticket->agent) && user()->id != $ticketReply->ticket->agent_id) {
                    event(new TicketReplyEvent($ticketReply, $ticketReply->ticket->agent));
                    event(new TicketReplyEvent($ticketReply, $ticketReply->ticket->client));
                }
                else if (is_null($ticketReply->ticket->agent)) {
                    event(new TicketReplyEvent($ticketReply, null));
                    event(new TicketReplyEvent($ticketReply, $ticketReply->ticket->client));
                }
                else {
                    event(new TicketReplyEvent($ticketReply, $ticketReply->ticket->client));
                }

                $ticketActivity = new TicketActivity();
                $ticketActivity->ticket_id = $ticketReply->ticket->id;
                $ticketActivity->user_id = $ticketReply->user_id;
                $ticketActivity->assigned_to = $ticketReply->ticket->agent_id;
                $ticketActivity->channel_id = $ticketReply->ticket->channel_id;
                $ticketActivity->group_id = $ticketReply->ticket->group_id;
                $ticketActivity->type_id = $ticketReply->ticket->type_id;
                $ticketActivity->status = $ticketReply->ticket->status;
                $ticketActivity->priority = $ticketReply->ticket->priority;
                $ticketActivity->type = 'reply';
                $ticketActivity->save();
            }
        }

    }

}
