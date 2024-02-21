<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrefixSetting extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        $rules = [];

        if(in_array('invoices', user_modules())){
            $rules['invoice_prefix'] = 'required';
            $rules['credit_note_prefix'] = 'required';
        }

        if(in_array('estimates', user_modules())){
            $rules['estimate_prefix'] = 'required';
        }

        if(in_array('orders', user_modules())){
            $rules['order_prefix'] = 'required';
        }

        return $rules;
    }

}
