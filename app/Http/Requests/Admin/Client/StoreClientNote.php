<?php

namespace App\Http\Requests\Admin\Client;

use App\Http\Requests\CoreRequest;

class StoreClientNote extends CoreRequest
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
        $rules = [
            'title' => 'required',
            'details' => 'required',
        ];

        if ($this->type == 1 && is_null($this->user_id) && !in_array('client', user_roles())) {
            $rules['user_id'] = 'required';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'user_id.required' => 'The employee field is required.',
        ];
    }

}
