<?php

namespace App\Http\Requests\TicketType;

use App\Http\Requests\CoreRequest;

class UpdateTicketType extends CoreRequest
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
            'type' => 'required|unique:ticket_types,type,'.$this->route('ticketType').',id,company_id,' . company()->id,
        ];
    }

}
