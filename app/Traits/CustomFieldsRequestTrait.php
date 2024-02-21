<?php

namespace App\Traits;

use App\Models\CustomField;

trait CustomFieldsRequestTrait
{

    public function customFieldRules($rules = [])
    {
        $fields = request()->custom_fields_data;

        if ($fields) {

            foreach ($fields as $key => $value) {
                $idarray = explode('_', $key);
                $id = end($idarray);

                $customField = CustomField::findOrFail($id);

                if ($customField->required == 'yes') {
                    $rules['custom_fields_data.'.$key] = 'required';

                    if($customField->type == 'file' && request()->hasFile('custom_fields_data.'.$key))
                    {
                        $rules['custom_fields_data.'.$key] = 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png,webp,xls,xlsx,zip,rar,txt,svg,ppt,pptx,mp4,mp3,avi,flv,wmv,3gp,webm,psd,ai,eps,indd,svg,ttf,otf,woff,woff2,zip,rar,7z';
                    }
                }
            }
        }

        return $rules;
    }

    public function customFieldsAttributes($attributes = [])
    {
        $fields = request()->custom_fields_data;

        if ($fields) {

            foreach ($fields as $key => $value) {
                $idarray = explode('_', $key);
                $id = end($idarray);
                $customField = CustomField::findOrFail($id);

                if ($customField->required == 'yes') {
                    $attributes['custom_fields_data.'.$key] = str($customField->label);
                }
            }
        }

        return $attributes;
    }

}
