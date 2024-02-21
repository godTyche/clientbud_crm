<?php

namespace App\View\Components\Forms;

use Illuminate\View\Component;

class CustomField extends Component
{

    public $fields;
    public $model;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($fields, $model = false)
    {
        $this->fields = $fields;
        $this->model = $model;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.forms.custom-field');
    }

}
