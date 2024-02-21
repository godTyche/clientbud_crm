<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Flag extends Component
{

    public $country;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($country)
    {
        $this->country = $country;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.flag');
    }

}
