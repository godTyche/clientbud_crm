<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\CoreRequest;

class StoreUserRole extends CoreRequest
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
            'user_id.0' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'user_id.0.required' => 'Choose at-least 1 member'
        ];
    }

}
