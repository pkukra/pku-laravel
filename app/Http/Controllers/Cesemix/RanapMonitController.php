<?php

namespace App\Http\Controllers\Cesemix;

use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Repositories\Casemix\RanapMonitRepository;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PasienRanapExport;

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
        $bangsal =  $this->RanapMonitRepo->getListKamarIndukRanap();

        return Inertia::render('Casemix/RanapMonit/RanapMonitList', [
            "bangsal" => $bangsal,
            'role' => Auth::user()->role,
        ]);
    }

    /**
     * list_pasien_data json data for list_pasien view
     * @return object
     */
    public function list_pasien_data(Request $request)
    {
        $bangsal_induk = $request->bangsal_induk ?? "IK009";
        $status = $request->status ?? "dirawat";
        $nomer_rm = $request->nomer_rm ?? "";

        $month = $request->month;
        $year = $request->year;

        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);

        $offset = ($page - 1) * $perPage;

        // Ambil total pasien untuk pagination
        $total = $this->RanapMonitRepo->getOrCountPasienRanap($month, $year, $bangsal_induk, $nomer_rm, $status, null, null, true);

        // Ambil data pasien
        $data = $this->RanapMonitRepo->getOrCountPasienRanap($month, $year, $bangsal_induk, $nomer_rm, $status, $perPage, $offset, false);

        return response()->json([
            'pasiens' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /**
     * Download patient list as Excel
     */
    public function download_pasien_data(Request $request)
    {
        $bangsal_induk = $request->bangsal_induk ?? "IK009";
        $status = $request->status ?? "dirawat";
        $nomer_rm = $request->nomer_rm ?? "";

        $month = $request->month ?? date('m');
        $year = $request->year ?? date('Y');

        // Get all data without pagination
        $data = $this->RanapMonitRepo->getOrCountPasienRanap($month, $year, $bangsal_induk, $nomer_rm, $status, null, null, false);

        // return view('casemix.pasien_ranap_xls', [
        //     'data' => $data,
        // ]);

        // Create and return Excel file
        return Excel::download(new PasienRanapExport($data), 'pasien-ranap-' . date('Y-m-d') . '.xlsx');
    }

    // update_monit_row
    public function update_monit_row(Request $request, $kode_reg)
    {
        // Validate the input
        $request->validate([
            'key' => 'required|string',
            'data' => 'required|string',
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
        $isSaved = $this->RanapMonitRepo->saveProcedureRanap($data);

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


    /**
     * list_billing_temp
     * Menampilkan list_billing_temp berdasarkan kode transaksi dari tabel CASEMIX_BILLING_TEMP
     */
    public function list_billing_temp($kode_reg)
    {
        $data = $this->RanapMonitRepo->getListBillingTempByTransaksi($kode_reg);

        return response()->json([
            'status' => "ok",
            'data' => $data,
        ]);
    }

    /**
     * list_billing_temp
     * Menampilkan list_billing_temp berdasarkan kode transaksi dari tabel CASEMIX_BILLING_TEMP
     */
    public function save_billing_temp(Request $request)
    {
        // Validasi input
        $data = $request->validate([
            'NO_TRANSAKSI' => 'required',
            'KETERANGAN' => 'required',
            'NOMINAL' => 'required',
        ]);

        $isSaved = $this->RanapMonitRepo->saveBillingTemp($data);

        if ($isSaved) {
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

    public function delete_billing_temp($id)
    {
        $deleted = $this->RanapMonitRepo->deleteBillingTempById($id);

        if ($deleted) {
            return response()->json([
                'status' => "ok",
                'message' => 'Billing Temp berhasil dihapus',
            ]);
        }

        return response()->json([
            'status' => "nok",
            'message' => 'Terjadi kesalahan saat menghapus billing temp',
        ], 500);
    }

    /**
     * get_list_cppt
     * Menampilkan list_mr_diagnosa berdasarkan kode transaksi
     */
    public function get_list_cppt($kode_reg)
    {
        $data = $this->RanapMonitRepo->getCPPTByTransaksi($kode_reg);

        return response()->json([
            'status' => "ok",
            'data' => $data,
        ]);
    }

    /**
     * list_kamar_induk
     * Menampilkan list_kamar_induk yang bangsal pasien aja
     */
    public function list_kamar_induk()
    {
        $data = $this->RanapMonitRepo->getListKamarIndukRanap();

        return response()->json([
            'status' => "ok",
            'data' => $data,
        ]);
    }
}
