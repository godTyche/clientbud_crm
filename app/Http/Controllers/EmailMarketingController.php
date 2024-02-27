<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helper\Reply;
use App\Helper\Files;
use App\Models\EmailMarketing;
use App\Models\EmailMarketingImage;
use App\Models\User;
use App\Mail\EmailMarketingMail;
use App\DataTables\EmailMarketingDataTable;
use Illuminate\Support\Facades\File;
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
        $emailMarketing->addedBy = user()->id;
        $emailMarketing->content = urldecode($request->content);

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
        $this->clients = User::allClients();

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
        $content = urldecode($request->content);

        $pattern = '/src="data:image\/[^;]+;base64,([^"]+)"/';
        $htmlWithUrls= preg_replace_callback($pattern, function ($matches) {
            $base64String = $matches[1];

            $decodedData = base64_decode($base64String);

            // Generate a unique filename
            $filename = uniqid('image_') . '.png';

            // Specify the path where you want to save the image
            $path = public_path(Files::UPLOAD_FOLDER . '/email-marketing');
            if (!File::exists($path)) {
                File::makeDirectory($path, 0775, true);
            }
            $path = $path . '/' . $filename;
            // Save the image file
            file_put_contents($path, $decodedData);

            return 'src="' . asset('user-uploads/email-marketing/'. $filename) . '"';
        }, $content);
        $pattern = '/style="aspect-ratio:(\d+)\/(\d+);"/';
        $htmlWithUrls = preg_replace('/<img(.*?)height=["\'](.*?)["\'](.*?)>/i', '<img$1$3>', $htmlWithUrls);

        foreach($request->user_id as $userid) {
            $user = User::findOrFail($userid);
            $email = $user->email;

            Mail::to($email)->send(new EmailMarketingMail($subject, $htmlWithUrls, company()));
            return Reply::successWithData(__('messages.sendEmailSuccess'), ['redirectUrl' => route('email-marketing.index')]);
        }

    }
}
