<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Models\ResourceCenter;
use Illuminate\Http\Request;

class ResourceCenterController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.resourceCenter';
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $this->resourceCenters = ResourceCenter::orderBy('id', 'desc')->get();
        return view('resource-center.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $this->pageTitle = __('modules.resourceCenter.addResource');

        // $this->addPermission = user()->permission('add_product');
        // abort_403(!in_array($this->addPermission, ['all', 'added']));

        if (request()->ajax()) {
            $html = view('resource-center.ajax.create', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'resource-center.ajax.create';

        return view('resource-center.create', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $resourceCenter = new ResourceCenter();
        $resourceCenter->title = $request->title;
        $resourceCenter->url = $request->url;
        $resourceCenter->icon = $request->icon;
        $resourceCenter->colour = $request->colour;
        $resourceCenter->addedBy = user()->id;
        $resourceCenter->save();

        $redirectUrl = urldecode($request->redirect_url);

        if ($redirectUrl == '') {
            $redirectUrl = route('resource-center.index');
        }

        if($request->add_more == 'true') {
            $html = $this->create();

            return Reply::successWithData(__('messages.recordSaved'), ['html' => $html, 'add_more' => true, 'resourceID' => $resourceCenter->id]);
        }

        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => $redirectUrl, 'resourceID' => $resourceCenter->id]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
        $this->resourceCenter = ResourceCenter::findOrFail($id);

        $this->pageTitle = __('modules.resourceCenter.editResource');

        
        if (request()->ajax()) {
            $html = view('resource-center.ajax.edit', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'resource-center.ajax.edit';

        return view('resource-center.create', $this->data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $resourceCenter = ResourceCenter::findOrFail($id);
        $resourceCenter->title = $request->title;
        $resourceCenter->url = $request->url;
        $resourceCenter->icon = $request->icon;
        $resourceCenter->colour = $request->colour;
        $resourceCenter->addedBy = user()->id;
        $resourceCenter->save();

        $redirectUrl = urldecode($request->redirect_url);

        return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => route('resource-center.index')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $resourceCenter = ResourceCenter::findOrFail($id);

        $resourceCenter->delete();

        return Reply::successWithData(__('messages.deleteSuccess'), ['redirectUrl' => route('resource-center.index')]);
    }
}
