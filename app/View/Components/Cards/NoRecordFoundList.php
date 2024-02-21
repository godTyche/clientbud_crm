<?php

namespace App\View\Components\Cards;

use Illuminate\View\Component;

class NoRecordFoundList extends Component
{

    public $colspan;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($colspan = 3)
    {
        $this->colspan = $colspan;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.cards.no-record-found-list');
    }

}
