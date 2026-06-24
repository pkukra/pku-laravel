<?php

namespace App\Repositories\KlaimInap;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Bpjs\Bridging\Vclaim\BridgeVclaim;
use App\Repositories\RM\RMAuditTrail;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SEPInapRepository
{
    public function getSEPDetail($kode_reg)
    {
        $sep = DB::connection('sqlsrvsimrs')
            ->table('BPJS_SEP')
            // ->leftJoin('TRANSAKSIPASIENINAP AS TPI', 'TPI.FTNO_TRANSAKSI', '=', 'BPJS_SEP.FMNOTRANSAKSI')
            ->leftJoin('PASIEN', 'BPJS_SEP.FMPASIEN_ID', '=', 'PASIEN.KD_PASIEN')
            ->select(
                'BPJS_SEP.*',
                'PASIEN.*'
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
