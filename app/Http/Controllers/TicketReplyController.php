<?php

namespace App\Http\Controllers;

use App\Helper\Files;
use App\Helper\Reply;
use App\Models\TicketFile;
use App\Models\TicketReply;

class TicketReplyController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.tickets';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('tickets', $this->user->modules));
            return $next($request);
        });
    }

    public function destroy($id)
    {
        $ticketReply = TicketReply::findOrFail($id);

        $this->deletePermission = user()->permission('delete_tickets');

        abort_403(!(
            $this->deletePermission == 'all'
            || ($this->deletePermission == 'added' && user()->id == $ticketReply->user_id)
            || ($this->deletePermission == 'owned' && (user()->id == $ticketReply->agent_id || user()->id == $ticketReply->user_id))
            || ($this->deletePermission == 'both' && (user()->id == $ticketReply->agent_id || user()->id == $ticketReply->added_by || user()->id == $ticketReply->user_id))
        ));


        $ticketFiles = TicketFile::where('ticket_reply_id', $id)->get();

        foreach ($ticketFiles as $file) {
            Files::deleteFile($file->hashname, 'ticket-files/' . $file->ticket_reply_id);
            $file->delete();
        }

        TicketReply::destroy($id);
        return Reply::success(__('messages.deleteSuccess'));

    }

}
