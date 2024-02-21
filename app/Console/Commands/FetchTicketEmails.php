<?php

namespace App\Console\Commands;

use App\Events\TicketReplyEvent;
use App\Mail\TicketReply as MailTicketReply;
use App\Models\ClientDetails;
use App\Models\Company;
use App\Models\Role;
use App\Models\SmtpSetting;
use App\Models\Ticket;
use App\Models\TicketEmailSetting;
use App\Models\TicketReply;
use App\Models\User;
use App\Scopes\ActiveScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class FetchTicketEmails extends Command
{

    private $smtpSetting;
    private $ticketEmailSetting;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch-ticket-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Ticket Emails';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $companies = Company::select('id')->get();

        $this->smtpSetting = SmtpSetting::first();

        foreach ($companies as $company) {
            $this->ticketEmailSetting = TicketEmailSetting::where('company_id', $company->id)->first();

            if ($this->ticketEmailSetting->status == 1) {

                if (!in_array(config('app.env'), ['demo', 'development'])) {

                    $driver = ($this->smtpSetting->mail_driver != 'mail') ? $this->smtpSetting->mail_driver : 'sendmail';

                    Config::set('mail.default', $driver);
                    Config::set('mail.mailers.smtp.host', $this->smtpSetting->mail_host);
                    Config::set('mail.mailers.smtp.port', $this->smtpSetting->mail_port);
                    Config::set('mail.mailers.smtp.username', $this->smtpSetting->mail_username);
                    Config::set('mail.mailers.smtp.password', $this->smtpSetting->mail_password);
                    Config::set('mail.mailers.smtp.encryption', $this->smtpSetting->mail_encryption);
                    Config::set('queue.default', $this->smtpSetting->mail_connection);
                }

                Config::set('imap.accounts.default.host', $this->ticketEmailSetting->imap_host);
                Config::set('imap.accounts.default.port', $this->ticketEmailSetting->imap_port);
                Config::set('imap.accounts.default.encryption', $this->ticketEmailSetting->imap_encryption);
                Config::set('imap.accounts.default.username', $this->ticketEmailSetting->mail_username);
                Config::set('imap.accounts.default.password', $this->ticketEmailSetting->mail_password);

                $client = \Webklex\IMAP\Facades\Client::account('default'); /* @phpstan-ignore-line */
                $client->connect();
                $oFolder = $client->getFolder('INBOX');
                $messages = $oFolder->query()->since(today())->get();
                /** @var \Webklex\PHPIMAP\Message $message */
                foreach ($messages as $message) {
                    /* echo($message->getFrom()[0]->personal)."\n";
                    // echo $message->getUid()."\n";
                    // echo $message->getSubject()."\n";
                    // echo 'Attachments: '.$message->getAttachments()->count()."\n";
                    // echo $message->getMessageId()."\n";
                    // echo $message->getInReplyTo()."\n";
                    // echo $message->getFrom()[0]->mail."\n";
                    // print_r($message->getAttributes())."\n";
                    // echo $message->getHTMLBody(true);
                    // echo $message->getTextBody(true); */
                    $data = [
                        'name' => trim($message->getFrom()[0]->personal),
                        'email' => trim($message->getFrom()[0]->mail),
                        'subject' => $message->getSubject(),
                        'text' => $message->getHTMLBody() != '' ? $message->getHTMLBody() : $message->getRawBody(),
                        'imap_message_id' => $message->getMessageId(),
                        'imap_message_uid' => $message->getUid(),
                        'imap_in_reply_to' => !is_null($message->getInReplyTo()) ? str_replace(array('<', '>'), '', $message->getInReplyTo()) : null,
                    ];

                    $checkTicket = TicketReply::with(['ticket' => function ($q) use ($company) {
                        $q->where('company_id', $company->id);
                    }])->where('imap_message_uid', $data['imap_message_uid'])->withTrashed()->first();

                    if (is_null($checkTicket) && !is_null($data['imap_in_reply_to'])) {
                        $checkReplyTo = TicketReply::with(['ticket' => function ($q) use ($company) {
                            $q->where('company_id', $company->id);
                        }])->where('imap_message_id', $data['imap_in_reply_to'])->withTrashed()->first();
                    }

                    if (is_null($checkTicket)) {
                        if (isset($checkReplyTo) && !is_null($checkReplyTo)) {
                            $this->createTicketReply($checkReplyTo->ticket, $data, $company->id);

                        }
                        else {
                            $this->createTicket($data, $company->id);
                        }
                    }

                }

            }
        }

        return true;
    }

    public function createTicket($data, $companyId)
    {
        $existing_user = User::withoutGlobalScope(ActiveScope::class)->select('id', 'email')->where('company_id', $companyId)->where('email', $data['email'])->first();
        $newUser = $existing_user;

        if (!$existing_user) {
            // create new user
            $client = new User();
            $client->company_id = $companyId;
            $client->name = $data['name'];
            $client->email = $data['email'];
            $client->save();

            // attach role
            $role = Role::where('company_id', $companyId)->where('name', 'client')->select('id')->first();
            $client->attachRole($role->id);

            $clientDetail = new ClientDetails();
            $clientDetail->company_id = $companyId;
            $clientDetail->user_id = $client->id;
            $clientDetail->save();

            $client->assignUserRolePermission($role->id);

            $newUser = $client;
        }

        // Create New Ticket
        $ticket = new Ticket();
        $ticket->company_id = $companyId;
        $ticket->subject = $data['subject'];
        $ticket->status = 'open';
        $ticket->user_id = $newUser->id;
        $ticket->priority = 'medium';
        $ticket->save();

        // Save first message
        $reply = new TicketReply();
        $reply->message = $data['text'];
        $reply->ticket_id = $ticket->id;
        $reply->user_id = $newUser->id; // Current logged in user
        $reply->imap_message_id = $data['imap_message_id'];
        $reply->imap_message_uid = $data['imap_message_uid'];
        $reply->imap_in_reply_to = $data['imap_in_reply_to'];
        $reply->save();

        $this->sendNotifications($reply);

    }

    public function createTicketReply($ticket, $data, $companyId)
    {
        $existing_user = User::withoutGlobalScope(ActiveScope::class)->select('id', 'email')->where('company_id', $companyId)->where('email', $data['email'])->first();
        $newUser = $existing_user;

        if (!$existing_user) {
            // create new user
            $client = new User();
            $client->company_id = $companyId;
            $client->name = $data['name'];
            $client->email = $data['email'];
            $client->save();

            // attach role
            $role = Role::where('name', 'client')->select('id')->first();
            $client->attachRole($role->id);

            $clientDetail = new ClientDetails();
            $clientDetail->company_id = $companyId;
            $clientDetail->user_id = $client->id;
            $clientDetail->save();

            $client->assignUserRolePermission($role->id);

            $newUser = $client;
        }

        $reply = new TicketReply();
        $reply->message = $data['text'];
        $reply->ticket_id = $ticket->id;
        $reply->user_id = $newUser->id; // Current logged in user
        $reply->imap_message_id = $data['imap_message_id'];
        $reply->imap_message_uid = $data['imap_message_uid'];
        $reply->imap_in_reply_to = $data['imap_in_reply_to'];
        $reply->save();

        $this->sendNotifications($reply);
    }

    public function sendNotifications($ticketReply)
    {
        $ticketReply->ticket->touch();

        if (!is_null($ticketReply->ticket->agent) && $ticketReply->user_id != $ticketReply->ticket->agent_id) {
            event(new TicketReplyEvent($ticketReply, $ticketReply->ticket->agent));
        }
        else if (is_null($ticketReply->ticket->agent)) {
            event(new TicketReplyEvent($ticketReply, null));
        }
        else {
            event(new TicketReplyEvent($ticketReply, $ticketReply->ticket->client));
        }

        if (!is_null($ticketReply->ticket->agent_id)) {
            if ($ticketReply->ticket->agent_id == $ticketReply->user_id) {
                $toEmail = $ticketReply->ticket->client->email;

            }
            else {
                $toEmail = $ticketReply->ticket->agent->email;
            }

            Mail::to($toEmail)->send(new MailTicketReply($ticketReply, $this->ticketEmailSetting));

        }
    }

}
