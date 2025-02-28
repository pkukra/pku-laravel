<?php

namespace App\Http\Controllers\Cesemix;

use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Repositories\Casemix\RanapMonitRepository;

class RanapMonitController extends Controller
{
    protected $RanapMonitRepo;

    // Dependency Injection Repository
    public function __construct(
        RanapMonitRepository $RanapMonitRepo,
    ) {
        $this->RanapMonitRepo = $RanapMonitRepo;
    }

    public function list_pasien()
    {
        return Inertia::render('Casemix/RanapMonit/List');
    }

    /**
     * list_pasien_data json data for list_pasien view
     * @return object
     */
    public function list_pasien_data($bulan = "2", $tahun = "2025", $bangsal_induk = "IK043", $status = "dirawat")
    {
        $data = $this->RanapMonitRepo->getPasienRanap($bulan, $tahun, $bangsal_induk, $status);
        return response()->json([
            'pasiens' => $data,
        ]);
    }

    // update_monit_row
    public function update_monit_row(Request $request, $kode_reg)
    {
        // Validate the input
        $request->validate([
            'diagnosa_sekunder' => 'nullable|string',
            'tindakan' => 'nullable|string',
        ]);

        $isUpdated =  $this->RanapMonitRepo->updateCasemixRanap($kode_reg, $request);

        if ($isUpdated) {
            return response()->json([
                'status' => "ok",
                'message' => 'Data berhasil disimpan',
            ]);
        }

        return response()->json([
            'status' => "nok",
            'message' => 'Terjadi kesalahan saat menyimpan data',
        ], 500);
    }
}
