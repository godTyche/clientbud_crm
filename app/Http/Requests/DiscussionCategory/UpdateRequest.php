<?php

namespace App\Http\Requests\DiscussionCategory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
        if (request()->has('name')) {
            return [
                'name' => 'required|unique:discussion_categories,name,' . $this->route('discussion_category').',id,company_id,' . company()->id,
            ];
        }

        return [];
    }

}
