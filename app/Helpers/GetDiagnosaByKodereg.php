<?php

use Illuminate\Support\Facades\DB;

if (!function_exists('get_diagnosa_ri')) {
    function get_diagnosa_ri($kode_reg)
    {
        $diagnosa = DB::connection('sqlsrv')
            ->table('PKU.dbo.TAC_RI_MEDIS')
            ->where('FS_KD_REG', $kode_reg)
            ->value('FS_DIAGNOSA');
        return $diagnosa;
    }
}
