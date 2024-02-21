<?php

namespace App\Http\Requests\Admin\TaskLabel;

use App\Http\Requests\CoreRequest;

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
            'label_name' => 'required|max:50',
            'color' => 'required|max:50',
        ];
    }

}
