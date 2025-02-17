<?php

namespace App\Http\Controllers\RM;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Repositories\RM\PasienRujukanRepository;
use App\Repositories\RM\BridgingEKlaimRepository;
use Illuminate\Support\Facades\DB;

class PasienRujukanController extends Controller
{
    protected $pasienRujukanRepo;
    protected $bridgingEKlaimRepo;

    // Dependency Injection Repository
    public function __construct(
        PasienRujukanRepository $pasienRujukanRepo,
        BridgingEKlaimRepository $bridgingEKlaimRepo,
    ) {
        $this->pasienRujukanRepo = $pasienRujukanRepo;
        $this->bridgingEKlaimRepo = $bridgingEKlaimRepo;
    }

    /**
     * index
     * Load halaman utama daftar pasien rujukan
     */
    public function index(Request $request)
    {
        return Inertia::render('RM/PasienRujukan/PasienRujukanList');
    }

    /**
     * index_data
     * Menampilkan daftar pasien rujukan dalam format JSON
     */
    public function index_data($no_rm)
    {
        // Mendapatkan data pasien rujukan menggunakan repository
        $pasien_rujukans = $this->pasienRujukanRepo->getPasienRujukans($no_rm);
        $count = $this->pasienRujukanRepo->countPasienRujukan();

        return response()->json([
            'status' => "ok",
            'pasien_rujukans' => $pasien_rujukans,
            'count' => $count,
        ]);
    }

    /**
     * show
     * Menampilkan detail pasien rujukan berdasarkan kode_reg
     */
    public function show($kode_reg)
    {
        // Mendapatkan detail pasien rujukan berdasarkan kode_reg
        $pasien_rujukans = $this->pasienRujukanRepo->getPasienRujukanDetail($kode_reg);
        $count = $this->pasienRujukanRepo->countPasienRujukan();

        return Inertia::render('RM/PasienRujukan/PasienRujukanDetail', [
            'pasien' => $pasien_rujukans,
            'count' => $count,
        ]);
    }

    /**
     * list_diagnosa
     * Menampilkan diagnosa berdasarkan kode transaksi
     */
    public function list_diagnosa(Request $request, $kode_reg)
    {
        // Mendapatkan diagnosa berdasarkan kode transaksi
        $diagnosa = $this->pasienRujukanRepo->getDiagnosaByTransaksi($kode_reg);

        return response()->json([
            'status' => "ok",
            'data' => $diagnosa,
        ]);
    }

    /**
     * cari_penyakit
     * Pencarian penyakit/diagnosa di database berdasarkan input
     */
    public function cari_penyakit(Request $request)
    {
        $searchTerm = $request->input('query');
        $page = $request->input('page', 1); // Halaman saat ini (default 1)

        // Mendapatkan data penyakit berdasarkan pencarian
        $penyakit = $this->pasienRujukanRepo->searchPenyakit($searchTerm, $page);

        return response()->json([
            'status' => "ok",
            'data' => $penyakit,
            'page' => $page,
        ]);
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
        $isSaved = $this->pasienRujukanRepo->saveDiagnosa($data);

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
     * delete_diagnosa
     * Hapus diagnosa berdasarkan ID
     */
    public function delete_diagnosa($id)
    {
        // Hapus diagnosa berdasarkan ID dari tabel MR_PENYAKIT
        $deleted = $this->pasienRujukanRepo->deleteDiagnosaById($id);

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
     * list_procedure
     * Menampilkan procedure berdasarkan kode transaksi
     */
    public function list_procedure($kode_reg)
    {
        // Mendapatkan procedure berdasarkan kode transaksi
        $procedure = $this->pasienRujukanRepo->getProcedureByTransaksi($kode_reg);

        return response()->json([
            'status' => "ok",
            'data' => $procedure,
        ]);
    }

    /**
     * cari_procedure
     * Pencarian procedure/diagnosa di database berdasarkan input
     */
    public function cari_procedure(Request $request)
    {
        $searchTerm = $request->input('query');
        $page = $request->input('page', 1); // Halaman saat ini (default 1)

        // Mendapatkan data procedure berdasarkan pencarian
        $procedure = $this->pasienRujukanRepo->searchProcedure($searchTerm, $page);

        return response()->json([
            'status' => "ok",
            'data' => $procedure,
            'page' => $page,
        ]);
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
        $isSaved = $this->pasienRujukanRepo->saveProcedure($data);

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
     * delete_procedure
     * Hapus procedure berdasarkan ID
     */
    public function delete_procedure($id)
    {
        // Hapus procedure berdasarkan ID dari tabel MR_TINDAKAN
        $deleted = $this->pasienRujukanRepo->deleteProcedureById($id);

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
     * get_mr_diagnosa
     * Menampilkan list_mr_diagnosa berdasarkan kode transaksi
     */
    public function get_mr_diagnosa($kode_reg)
    {
        $data = $this->pasienRujukanRepo->getMrDiagnosaByTransaksi($kode_reg);

        return response()->json([
            'status' => "ok",
            'data' => $data,
        ]);
    }

    public function update_catatan_khusus(Request $request, $no_transaksi)
    {
        // Validate the input
        $validated = $request->validate([
            'catatan_khusus' => 'max:255',
        ]);
        // Get the validated catatan_khusus value
        $catatanKhusus = $validated['catatan_khusus'];

        $isUpdated = $this->pasienRujukanRepo->updateCatatanKhususByTransaksi($no_transaksi, $catatanKhusus);

        if ($isUpdated) {
            return response()->json([
                'status' => "ok",
                'message' => 'Cat khusus berhasil disimpan',
            ]);
        }

        return response()->json([
            'status' => "nok",
            'message' => 'Terjadi kesalahan saat menyimpan Cat khusus',
        ], 500);
    }

    /**
     * get_detail_tarif_transakasi
     * Menampilkan detail tarif setiap transaksi berdasarkan kode transaksi
     */
    public function get_detail_tarif_transakasi($kode_reg)
    {
        $tarif = $this->bridgingEKlaimRepo->getDetailTarifTransaksi($kode_reg);
        return response()->json($tarif);
    }
}
