<?php

namespace App\Repositories\KlaimInap;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class KlaimInapRepository
{
    public function getAllJOK($kode_reg)
    {
        $transaksi = DB::connection('sqlsrvsimrs')
            ->table('TRANSAKSIPASIEN')
            ->select('FTNO_TRANSAKSI')
            ->where('FTNO_TRANSAKSIINAP', $kode_reg)
            ->where('FTKD_UNIT', "PK002")
            ->get();

        // N+1: query ke OK_JADWAL per transaksi
        $jadwal_arr = [];
        foreach ($transaksi as $item) {
            $jadwal = DB::connection('sqlsrvsimrs')
                ->table('OK_JADWAL')
                ->select('*')
                ->where('FJOKNO_TRANSAKSI', $item->FTNO_TRANSAKSI)
                ->first();

            $jadwal_arr[] = [
                'FTNO_TRANSAKSI' => $item->FTNO_TRANSAKSI,
                'FJOKNO_JADWAL' => $jadwal ? $jadwal->FJOKNO_JADWAL : null,
                'FJOKKD_TINDAKAN' => $jadwal ? $jadwal->FJOKKD_TINDAKAN : null,
            ];
        }

        $laporan_ok_arr = [];
        foreach ($jadwal_arr as $jadwal) {
            $laporan_ok_arr[] = DB::connection('sqlsrvemr')
                ->table('TAC_RI_OK')
                ->select('FJOKNO_JADWAL')
                ->where('FJOKNO_JADWAL', $jadwal['FJOKNO_JADWAL'])
                ->first();
        }

        return $laporan_ok_arr;
    }

    public function getKodeRegRJByInap($kode_reg_rbi)
    {
        $data = DB::connection('sqlsrvsimrs')
            ->table('TRANSAKSIPASIENINAPD')
            ->select('FDTNO_FAKTUR')
            ->where('FDTNO_TRANSAKSI', $kode_reg_rbi)
            ->where(function ($q) {
                $q->where('FDTNO_FAKTUR', 'like', 'RGD%')
                    ->orWhere('FDTNO_FAKTUR', 'like', 'RJR%');
            })
            ->first();

        return $data;
    }

    public function checkIsPersalinan($kode_reg_rbi)
    {
        $data = DB::connection('sqlsrvemr')
            ->table('laporan_tindakan_persalinan')
            ->select('*')
            ->where('KD_REG', $kode_reg_rbi)
            ->first();

        if ($data) {
            return true;
        }

        return false;
    }
}
