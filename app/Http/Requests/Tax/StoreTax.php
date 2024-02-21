<?php

namespace App\Http\Requests\Tax;

use App\Http\Requests\CoreRequest;

class StoreTax extends CoreRequest
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
            'tax_name' => 'required',
            'rate_percent' => 'required|numeric'
        ];
    }

}
