<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrder extends FormRequest
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

    protected function prepareForValidation()
    {
        if ($this->order_number) {
            $this->merge([
                'order_number' => \App\Helper\NumberFormat::order($this->order_number),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [];

        $rules['status'] = 'sometimes|in:pending,on-hold,failed,processing,completed,canceled';

        $rules['order_number'] = [
            'required',
            Rule::unique('orders')->where('company_id', company()->id)
        ];

        if (request()->has('client_id')) {
            $rules['client_id'] = 'required';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'client_id.required' => __('modules.projects.selectClient')
        ];
    }

}
