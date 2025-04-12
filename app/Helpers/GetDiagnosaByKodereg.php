<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

if (!function_exists('get_diagnosa_ri')) {
    function get_diagnosa_ri($kode_reg)
    {
        $data = DB::connection('sqlsrvsimrs')
            ->table('PKU.dbo.TAC_RI_MEDIS')
            ->where('FS_KD_REG', $kode_reg)
            ->value('FS_DIAGNOSA');
        return $data;
    }
}


if (!function_exists('get_casemix_ranap_data')) {
    function get_casemix_ranap_data($kode_reg)
    {
        return DB::connection('sqlsrvsimrs')
            ->table('CASEMIX_RANAP')
            ->select('*')
            ->where('NO_TRANSAKSI', $kode_reg)
            ->first();
    }
}

if (!function_exists('get_pasien_by_no_rm')) {
    function get_pasien_by_no_rm($no_rm)
    {
        $cacheKey = "pasien:$no_rm"; // Kunci cache unik untuk tiap pasien

        return Cache::remember($cacheKey, 3600, function () use ($no_rm) { // Simpan cache selama 1 jam (3600 detik)
            return DB::connection('sqlsrvsimrs')
                ->table('PASIEN')
                ->where('KD_PASIEN', $no_rm)
                ->select('NAMAPASIEN')
                ->first();
        });
    }
}

if (!function_exists('get_dokter_by_kode')) {
    function get_dokter_by_kode($kode)
    {
        return DB::connection('sqlsrvsimrs')
            ->table('DOKTER')
            ->where('FMDDOKTER_ID', $kode)
            ->select('FMDDOKTERN AS DPJP')
            ->first();
    }
}

if (!function_exists('get_sep_by_kode_reg')) {
    function get_sep_by_kode_reg($kode_reg)
    {
        $cacheKey = "sep:$kode_reg";
        return Cache::remember($cacheKey, 300, function () use ($kode_reg) {
            return DB::connection('sqlsrvsimrs')
                ->table('BPJS_SEP')
                ->where('FMNOTRANSAKSI', $kode_reg)
                ->select('FMKODEKELAS AS KELAS_RAWAT', 'RAWAT_NAIK', 'FMNOSEP AS NO_SEP')
                ->first();
        });
    }
}


if (!function_exists('get_tgl_keluar_inap')) {
    function get_tgl_keluar_inap($kode_reg)
    {
        return DB::connection('sqlsrvsimrs')
            ->table('PASIENRAWATINAP')
            ->where('PRWINO_TRANSAKSI', $kode_reg)
            ->orderBy('PRWITGL_KELUAR', 'desc')
            ->value('PRWITGL_KELUAR');
    }
}

if (!function_exists('get_total_bill')) {
    function get_total_bill($kode_reg)
    {
        $bills = DB::connection('sqlsrvsimrs')
            ->table('TRANSAKSIPASIENINAPD')
            ->select('FDTQTY', 'FDTHARGA')
            ->where('FDTNO_TRANSAKSI', $kode_reg)
            ->where('FDTJENISTRANSAKSI', "DB")
            ->get();

        $total = 0;
        foreach ($bills as $bill) {
            $total += $bill->FDTQTY * $bill->FDTHARGA;
        }
        return $total;
    }
}


// TRANSAKSIPASIENINAPD