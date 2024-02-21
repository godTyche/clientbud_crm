<?php

namespace App\View\Components;

use App\Models\GlobalSetting;
use Illuminate\View\Component;

class CronMessage extends Component
{


    /**
     * @var false|mixed
     */
    private mixed $modal;

    public function __construct($modal = false)
    {
        $this->modal = $modal;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        $globalSetting = GlobalSetting::first();

        $modal = $this->modal;

        return view('components.cron-message', compact('globalSetting', 'modal'));
    }

}
