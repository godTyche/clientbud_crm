<?php

namespace App\Http\Controllers;

use App\Helper\Files;
use App\Helper\Reply;
use App\Models\Leave;
use App\Models\LeaveFile;
use Illuminate\Http\Request;

class LeaveFileController extends AccountBaseController
{

    public function store(Request $request)
    {

        if ($request->hasFile('file')) {
            foreach ($request->file as $fileData) {
                $file = new LeaveFile();
                $file->leave_id = $request->leave_id;

                $filename = Files::uploadLocalOrS3($fileData, LeaveFile::FILE_PATH . '/' . $request->leave_id);

                $file->user_id = user()->id;
                $file->filename = $fileData->getClientOriginalName();
                $file->hashname = $filename;
                $file->size = $fileData->getSize();
                $file->save();
            }
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */

    public function destroy($id)
    {
        $file = LeaveFile::findOrFail($id);
        $this->leave = Leave::findorFail($file->leave_id);
        Files::deleteFile($file->hashname, LeaveFile::FILE_PATH . '/' . $file->leave_id);

        LeaveFile::destroy($id);

        $this->files = LeaveFile::where('leave_id', $file->leave_id)->orderBy('id', 'desc')->get();
        $view = view('leaves.files.show', $this->data)->render();

        return Reply::successWithData(__('messages.deleteSuccess'), ['view' => $view]);

    }

    public function download($id)
    {
        $file = LeaveFile::whereRaw('md5(id) = ?', $id)->firstOrFail();

        return download_local_s3($file, LeaveFile::FILE_PATH . '/' . $file->leave_id . '/' . $file->hashname);

    }

}
