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

if (!function_exists('get_pasien_by_no_rm')) {
    function get_pasien_by_no_rm($no_rm)
    {
        return DB::connection('sqlsrv')
            ->table('PASIEN')
            ->where('KD_PASIEN', $no_rm)
            ->select('NAMAPASIEN')
            ->first();
    }
}

if (!function_exists('get_dokter_by_kode')) {
    function get_dokter_by_kode($kode)
    {
        return DB::connection('sqlsrv')
            ->table('DOKTER')
            ->where('FMDDOKTER_ID', $kode)
            ->select('FMDDOKTERN AS DPJP')
            ->first();
    }
}

if (!function_exists('get_sep_by_kode_reg')) {
    function get_sep_by_kode_reg($kode_reg)
    {
        return DB::connection('sqlsrv')
            ->table('BPJS_SEP')
            ->where('FMNOTRANSAKSI', $kode_reg)
            ->select('FMKODEKELAS AS KELAS_RAWAT')
            ->first();
    }
}

if (!function_exists('get_tgl_keluar_inap')) {
    function get_tgl_keluar_inap($kode_reg)
    {
        return DB::connection('sqlsrv')
            ->table('PASIENRAWATINAP')
            ->where('PRWINO_TRANSAKSI', $kode_reg)
            ->orderBy('PRWITGL_KELUAR', 'desc')
            ->value('PRWITGL_KELUAR');
    }
}
