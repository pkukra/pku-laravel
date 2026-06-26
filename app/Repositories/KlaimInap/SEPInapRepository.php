<?php

namespace App\Repositories\KlaimInap;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SEPInapRepository
{
    public function getSEPDetail($kode_reg)
    {
        $sep = DB::connection('sqlsrvsimrs')
            ->table('BPJS_SEP')
            ->leftJoin('TRANSAKSIPASIENINAP AS TPI', 'TPI.FTNO_TRANSAKSI', '=', 'BPJS_SEP.FMNOTRANSAKSI')
            ->join('PASIENRAWATINAP AS PRI', function ($join) {
                $join->on(DB::raw('CAST(PRI.PRWINO_TRANSAKSI AS NVARCHAR)'), '=', 'TPI.FTNO_TRANSAKSI')
                    ->whereRaw('CAST(PRI.PRWINO_URUT AS NVARCHAR) = CAST(TPI.FTNO_URUT AS NVARCHAR)');
            })
            ->leftJoin('DOKTER AS DR', 'PRI.PRWIKD_DOKTER', '=', 'DR.FMDDOKTER_ID')
            ->leftJoin('SPESIALISASI AS PS', 'PRI.PRWIKD_SPECIAL', '=', 'PS.FMSPESIALISASI_ID')
            ->leftJoin('ICD', 'BPJS_SEP.FMDIAGNOSA', '=', 'ICD.code')
            ->leftJoin('PASIEN', 'BPJS_SEP.FMPASIEN_ID', '=', 'PASIEN.KD_PASIEN')
            ->select(
                'BPJS_SEP.*',
                'DR.FMDDOKTERN',
                'PS.FMSPESIALISASIN as SPESIALISASI',
                'PASIEN.*',
                'ICD.description as DX_AWAL'
            )
            ->where('FMNOTRANSAKSI', $kode_reg)
            ->first();

        return $sep;
    }

    public function getAllDiagnosis($no_sep)
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

    public function getAllProcedures($no_sep)
    {
        $data = DB::connection('sqlsrvsimrs')
            ->table('PASIEN_TINDAKAN_IM')
            ->leftJoin('ICD', 'PASIEN_TINDAKAN_IM.code', '=', 'ICD.code')
            ->select(
                'PASIEN_TINDAKAN_IM.*',
                'ICD.description'
            )
            ->where('no_sep', $no_sep)
            ->get();

        return $data;
    }
}
