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
}
