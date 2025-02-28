<?php

use Illuminate\Support\Facades\DB;

if (!function_exists('get_diagnosa_ri')) {
    function get_diagnosa_ri($kode_reg)
    {
        $data = DB::connection('sqlsrv')
            ->table('PKU.dbo.TAC_RI_MEDIS')
            ->where('FS_KD_REG', $kode_reg)
            ->value('FS_DIAGNOSA');
        return $data;
    }
}

if (!function_exists('get_casemix_ranap_data')) {
    function get_casemix_ranap_data($kode_reg)
    {
        return DB::connection('sqlsrv')
            ->table('CASEMIX_RANAP')
            ->where('NO_TRANSAKSI', $kode_reg)
            ->first();
    }
}
