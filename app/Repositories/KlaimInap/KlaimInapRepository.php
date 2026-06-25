<?php

namespace App\Repositories\KlaimInap;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class KlaimInapRepository
{
    public function getAllJOK($no_sep)
    {
        $data = DB::connection('sqlsrvsimrs')
            ->table('PASIEN_DIAGNOSA_IM')
            ->leftJoin('ICD', 'PASIEN_DIAGNOSA_IM.code', '=', 'ICD.code')
            ->select(
                'PASIEN_DIAGNOSA_IM.*',
                'ICD.description'
            )
            ->where('no_sep', $no_sep)
            ->get();

        return $data;
    }
}
