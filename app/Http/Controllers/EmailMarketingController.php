<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helper\Reply;
use App\Models\EmailMarketing;
use App\Mail\EmailMarketingMail;
use App\DataTables\EmailMarketingDataTable;
use Illuminate\Support\Facades\Mail;

class EmailMarketingController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.emailMarketing';
    }
    /**
     * Display a listing of the resource.
     */
    public function index(EmailMarketingDataTable $dataTable)
    {
        //
        return $dataTable->render('email-marketing.index', $this->data);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $this->pageTitle = __('modules.emailMarketing.addEmail');

        // $this->addPermission = user()->permission('add_product');
        // abort_403(!in_array($this->addPermission, ['all', 'added']));

        if (request()->ajax()) {
            $html = view('email-marketing.ajax.create', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'email-marketing.ajax.create';

        return view('email-marketing.create', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $emailMarketing = new EmailMarketing();
        $emailMarketing->title = $request->title;
        $emailMarketing->content = urldecode($request->content);
        $emailMarketing->addedBy = user()->id;
        $emailMarketing->save();

        $redirectUrl = urldecode($request->redirect_url);

        if ($redirectUrl == '') {
            $redirectUrl = route('email-marketing.index');
        }

        if($request->add_more == 'true') {
            $html = $this->create();

            return Reply::successWithData(__('messages.recordSaved'), ['html' => $html, 'add_more' => true, 'eamilID' => $emailMarketing->id]);
        }

        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => $redirectUrl, 'emailID' => $emailMarketing->id]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //
        $this->emailTemplate = EmailMarketing::findOrFail($id);

        $this->pageTitle = $this->emailTemplate->title;

        
        if (request()->ajax()) {
            $html = view('email-marketing.ajax.show', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'email-marketing.ajax.show';

        return view('email-marketing.create', $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        //
        $this->emailTemplate = EmailMarketing::findOrFail($id);

        $this->pageTitle = __('modules.emailMarketing.editEmail');

        
        if (request()->ajax()) {
            $html = view('email-marketing.ajax.edit', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'email-marketing.ajax.edit';

        return view('email-marketing.create', $this->data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
        $emailMarketing = EmailMarketing::findOrFail($id);
        $emailMarketing->title = $request->title;
        $emailMarketing->content = urldecode($request->content);
        $emailMarketing->save();

        $redirectUrl = urldecode($request->redirect_url);

        return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => route('email-marketing.index')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $emailMarketing = EmailMarketing::findOrFail($id);

        $emailMarketing->delete();

        return Reply::successWithData(__('messages.deleteSuccess'), ['redirectUrl' => route('email-marketing.index')]);
    }

    /**
     * Show the email composer
     */
    public function compose($id)
    {
        $this->emailTemplate = EmailMarketing::findOrFail($id);

        $this->pageTitle = __('modules.emailMarketing.sendEmail');

        
        if (request()->ajax()) {
            $html = view('email-marketing.ajax.compose', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'email-marketing.ajax.compose';

        return view('email-marketing.create', $this->data);
    }

    /**
     * Send the email to clients
     */
    public function sendEmail(Request $request)
    {
        $subject = $request->subject;
        $emailTo = $request->emailTo;
        $content = urldecode($request->content);
        
        Mail::to($emailTo)->send(new EmailMarketingMail($subject, $content, company()));
        return Reply::successWithData(__('messages.sendEmailSuccess'), ['redirectUrl' => route('email-marketing.index')]);
    }
}
