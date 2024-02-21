<?php

namespace App\Http\Requests\Settings;

use App\Http\Requests\CoreRequest;

class StorageAwsFileUpload extends CoreRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */

    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        return [
            'file' => 'required|mimes:png,jpg,jpeg,csv,txt,xlx,xls,pdf,zip|max:2048'
        ];
    }

}
