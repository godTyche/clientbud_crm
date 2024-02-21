<?php

namespace App\Http\Requests\TicketChannel;

use App\Http\Requests\CoreRequest;

class UpdateTicketChannel extends CoreRequest
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
            'channel_name' => 'required|unique:ticket_channels,channel_name,'.$this->route('ticketChannel').',id,company_id,' . company()->id
        ];
    }

}
