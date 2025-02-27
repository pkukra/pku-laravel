<?php

namespace App\Http\Controllers\Cesemix;

use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Repositories\RM\PasienRujukanRepository;

class CesemixRanapMonitController extends Controller
{
    protected $pasienRujukanRepo;

    // Dependency Injection Repository
    public function __construct(
        PasienRujukanRepository $pasienRujukanRepo,
    ) {
        $this->pasienRujukanRepo = $pasienRujukanRepo;
    }

    public function list_pasien()
    {
        $kamar_induk = "IK043";
        $status = "dirawat"; // Bisa bernilai 'dirawat', 'sudah_pulang', atau 'semua'

        $data = DB::connection('sqlsrv')
            ->table('PASIENRAWATINAP AS A')
            ->join('PASIEN AS B', 'A.PRWIKD_PASIEN', '=', 'B.KD_PASIEN')
            ->leftJoin('DOKTER AS C', 'A.PRWIKD_DOKTER', '=', 'C.FMDDOKTER_ID')
            ->join('KAMAR AS D', 'A.PRWIKD_KAMAR', '=', 'D.FMKKAMAR_ID')
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
            ->whereRaw('MONTH(A.PRWITGL_INAP) = ?', [2])
            ->whereRaw('YEAR(A.PRWITGL_INAP) = ?', [2025])
            ->where('D.FMKKAMARINDUK', $kamar_induk) // Filter based on room
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
            return $pasien;
        });

        return Inertia::render('Casemix/RanapMonit/List',  [
            'pasiens' => $data,
        ]);
    }
}
