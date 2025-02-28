<?php

namespace App\Repositories\Casemix;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\CasemixRanap;

class RanapMonitRepository
{
    /**
     * Get the list of pasien ranap each bangsal based on bangsal_induk
     * @param string $bulan, $tahun, $bangsal_induk = "IK043", $status = "dirawat"
     * accepted $status ["dirawat", "sudah_pulang", "semua"]
     * $bulan bernilai 1 sampai dengan 12
     * $tahun 4 digit tahun misalnya:2025
     * @return \Illuminate\Support\Collection
     */
    public function getPasienRanap($bulan, $tahun, $bangsal_induk = "IK043", $status = "dirawat")
    {
        $data = DB::connection('sqlsrv')
            ->table('PASIENRAWATINAP AS A')
            ->join('PASIEN AS B', 'A.PRWIKD_PASIEN', '=', 'B.KD_PASIEN')
            ->leftJoin('DOKTER AS C', 'A.PRWIKD_DOKTER', '=', 'C.FMDDOKTER_ID')
            ->join('KAMAR AS D', 'A.PRWIKD_KAMAR', '=', 'D.FMKKAMAR_ID')
            ->leftJoin('CASEMIX_RANAP AS CR', 'A.PRWINO_TRANSAKSI', '=', 'CR.NO_TRANSAKSI')
            ->select(
                'A.PRWINO_TRANSAKSI',
                'C.FMDDOKTERN',
                DB::raw('MAX(A.PRWITGL_MASUK) as PRWITGL_MASUK'),
                DB::raw('MAX(A.PRWITGL_KELUAR) as PRWITGL_KELUAR'),
                'A.PRWIKD_PASIEN',
                'B.NAMAPASIEN',
                DB::raw('CASE 
                        WHEN MAX(A.PRWITGL_KELUAR) IS NULL THEN DATEDIFF(DAY, MAX(A.PRWITGL_MASUK), GETDATE()) + 1
                        ELSE DATEDIFF(DAY, MAX(A.PRWITGL_MASUK), MAX(A.PRWITGL_KELUAR)) + 1
                        END as TOTAL_HARI')
            )
            ->whereRaw('MONTH(A.PRWITGL_INAP) = ?', [$bulan])
            ->whereRaw('YEAR(A.PRWITGL_INAP) = ?', [$tahun])
            ->where('D.FMKKAMARINDUK', "$bangsal_induk") // Filter based on room
            ->when($status == 'dirawat', function ($query) {
                return $query->whereNull('A.PRWITGL_KELUAR');  // Patients still being treated (exit date is NULL)
            })
            ->when($status == 'sudah_pulang', function ($query) {
                return $query->whereNotNull('A.PRWITGL_KELUAR');  // Patients who have been discharged (exit date is NOT NULL)
            })
            ->when($status == 'semua', function ($query) {
                return $query;  // No status filter
            })
            ->groupBy('A.PRWINO_TRANSAKSI', 'A.PRWIKD_PASIEN', 'B.NAMAPASIEN', 'C.FMDDOKTERN')
            ->orderBy('PRWITGL_MASUK', 'desc')
            ->get();

        // Looping data pasien dan tambahkan diagnosa
        $data = $data->map(function ($pasien) {
            $pasien->DIAGNOSA = get_diagnosa_ri($pasien->PRWINO_TRANSAKSI);
            $ranap = get_casemix_ranap_data($pasien->PRWINO_TRANSAKSI);
            // Gabungkan data ranap ke dalam pasien jika ada
            if ($ranap) {
                foreach ($ranap as $key => $value) {
                    $pasien->$key = $value;
                }
            }
            return $pasien;
        });

        return $data;
    }

    /**
     * Save data into CASEMIX_RANAP table
     * 
     * @param string $no_transaksi no kode_reg
     * @param array $data merupakan request controller
     * @return boolean
     */
    public function updateCasemixRanap($no_transaksikj, $request)
    {
        // Siapkan data yang akan diperbarui
        $data = [];
        if ($request->has('diagnosa_sekunder')) {
            $data["DIAGNOSA_SEKUNDER"] = $request->diagnosa_sekunder;
        }

        try {
            DB::connection('sqlsrv')
                ->table('CASEMIX_RANAP')
                ->updateOrInsert(
                    ['NO_TRANSAKSI' => $no_transaksikj], // Kondisi pencarian
                    $data // Data yang di-update/insert
                );
        } catch (\Exception $e) {
            Log::error("Error while insert/updating CASEMIX_RANAP: " . $e->getMessage());
            return false;
        }
        return true;
    }
}
