<?php

namespace App\Http\Requests\User;

use App\Http\Requests\CoreRequest;

class UpdateEmployee extends CoreRequest
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
            'email' => 'required|unique:users,email,' . $this->route('employee').',id,company_id,' . company()->id,
            'slack_username' => 'nullable|unique:employee_details,slack_username,' . $this->route('employee').',id,company_id,' . company()->id,
            'name' => 'required',
        ];
    }

}
