<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DailyRecordsTable extends Component
{
    /**
     * Create a new component instance.
     */
    public $index;

    public $week;

    public $auftragsnummer;

    public $positionId;

    public $machineId;

    public $userCompany;

    public $autoLoad;

    public function __construct(
        $index = null,
        $week = null,
        $auftragsnummer = null,
        $positionId = null,
        $machineId = null,
        $userCompany = null,
        $autoLoad = false,
    ) {
        $this->index = $index;
        $this->week = $week;
        $this->auftragsnummer = $auftragsnummer;
        $this->positionId = $positionId;
        $this->machineId = $machineId;
        $this->userCompany = $userCompany;
        $this->autoLoad = $autoLoad;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.daily-records-table');
    }
}
