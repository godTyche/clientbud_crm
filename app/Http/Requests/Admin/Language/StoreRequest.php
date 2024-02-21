<?php

namespace App\Http\Requests\Admin\Language;

use App\Http\Requests\CoreRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends CoreRequest
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
            'language_name' => 'required|unique:language_settings,language_name|max:30',
            'language_code' => 'required|unique:language_settings,language_code|max:10',
            'flag' => 'required',
            'status' => 'required|max:100',
        ];
    }

}
