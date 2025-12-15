<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PasienRanapExport implements FromView
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
        libxml_use_internal_errors(true);

        return view('casemix.pasien_ranap_xls', [
            'data' => $this->data,
        ]);
    }
}
