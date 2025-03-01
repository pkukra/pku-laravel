<?php

namespace App\Http\Controllers\Cesemix;

use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
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
        return Inertia::render('Casemix/RanapMonit/RanapMonitList');
    }

    /**
     * list_pasien_data json data for list_pasien view
     * @return object
     */
    public function list_pasien_data($bulan = "12", $tahun = "2024", $bangsal_induk = "IK043", $status = "")
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
            'pemeriksaan_penunjang' => 'nullable|string',
            'hasil_penunjang_abnormal' => 'nullable|string',
            'naik_kelas' => 'nullable|string',
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

    /**
     * list_diagnosa
     * Menampilkan list_mr_diagnosa berdasarkan kode transaksi
     */
    public function list_diagnosa($kode_reg)
    {
        $data = $this->RanapMonitRepo->getDiagnosaByTransaksi($kode_reg);

        return response()->json([
            'status' => "ok",
            'data' => $data,
        ]);
    }

    /**
     * delete_diagnosa
     * Hapus diagnosa berdasarkan ID
     */
    public function delete_diagnosa($id)
    {
        // Hapus diagnosa berdasarkan ID dari tabel MR_PENYAKIT
        $deleted = $this->RanapMonitRepo->deleteDiagnosaById($id);

        if ($deleted) {
            return response()->json([
                'status' => "ok",
                'message' => 'Diagnosa berhasil dihapus',
            ]);
        }

        return response()->json([
            'status' => "nok",
            'message' => 'Terjadi kesalahan saat menghapus diagnosa',
        ], 500);
    }

    /**
     * save_diagnosa
     * Menyimpan data diagnosa untuk pasien rujukan
     */
    public function save_diagnosa(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'icd10_code' => 'required|string|max:10',
            'no_transaksikj' => 'required|string|max:20',
            'no_rm' => 'required|string|max:20',
            'kd_unit' => 'required|string|max:20',
            'tgl_masuk' => 'required|date',
            'status_diagnosa' => 'required|string',
            'kasus' => 'required|string',
        ]);

        // Mengambil data yang diperlukan untuk penyimpanan
        $data = [
            'icd10_code' => $validated['icd10_code'],
            'no_transaksikj' => $validated['no_transaksikj'],
            'no_rm' => $validated['no_rm'],
            'kd_unit' => $validated['kd_unit'],
            'status_diagnosa' => $validated['status_diagnosa'],
            'kasus' => $validated['kasus'],
            'tgl_masuk' => Carbon::parse($validated['tgl_masuk']),
            'user_id' => Auth::id(),
        ];

        // Menyimpan data diagnosa melalui repository
        $isSaved = $this->RanapMonitRepo->saveDiagnosa($data);

        if ($isSaved) {
            return response()->json([
                'status' => "ok",
                'message' => 'Diagnosa berhasil disimpan',
            ]);
        }

        return response()->json([
            'status' => "nok",
            'message' => 'Terjadi kesalahan saat menyimpan diagnosa',
        ], 500);
    }

    /**
     * save_diagnosa
     * Menyimpan data diagnosa untuk pasien rujukan
     */
    public function save_procedure(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'icd9_code' => 'required|string|max:10',
            'no_transaksikj' => 'required|string|max:20',
            'no_rm' => 'required|string|max:20',
            'kd_unit' => 'required|string|max:20',
            'tgl_masuk' => 'required|date',
        ]);

        // Mengambil data yang diperlukan untuk penyimpanan
        $data = [
            'icd9_code' => $validated['icd9_code'],
            'no_transaksikj' => $validated['no_transaksikj'],
            'no_rm' => $validated['no_rm'],
            'kd_unit' => $validated['kd_unit'],
            'tgl_masuk' => Carbon::parse($validated['tgl_masuk']),
            'user_id' => Auth::id(),
        ];

        // Menyimpan data procedure melalui repository
        $isSaved = $this->RanapMonitRepo->saveProcedure($data);

        if ($isSaved) {
            return response()->json([
                'status' => "ok",
                'message' => 'Diagnosa berhasil disimpan',
            ]);
        }

        return response()->json([
            'status' => "nok",
            'message' => 'Terjadi kesalahan saat menyimpan procedure',
        ], 500);
    }

    /**
     * list_procedure
     * Menampilkan procedure berdasarkan kode transaksi
     */
    public function list_procedure($kode_reg)
    {
        // Mendapatkan procedure berdasarkan kode transaksi
        $procedure = $this->RanapMonitRepo->getProcedureByTransaksi($kode_reg);

        return response()->json([
            'status' => "ok",
            'data' => $procedure,
        ]);
    }

    /**
     * delete_procedure
     * Hapus procedure berdasarkan ID
     */
    public function delete_procedure($id)
    {
        // Hapus procedure berdasarkan ID dari tabel MR_TINDAKAN
        $deleted = $this->RanapMonitRepo->deleteProcedureById($id);

        if ($deleted) {
            return response()->json([
                'status' => "ok",
                'message' => 'Procedure berhasil dihapus',
            ]);
        }

        return response()->json([
            'status' => "nok",
            'message' => 'Terjadi kesalahan saat menghapus procedure',
        ], 500);
    }
}
