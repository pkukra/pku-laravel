<?php

namespace App\Http\Controllers\RM;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Repositories\RM\PasienRujukanRepository;

class PasienRujukanController extends Controller
{
    protected $pasienRujukanRepo;

    // Dependency Injection Repository
    public function __construct(PasienRujukanRepository $pasienRujukanRepo)
    {
        $this->pasienRujukanRepo = $pasienRujukanRepo;
    }

    /**
     * index
     * Load halaman utama daftar pasien rujukan
     */
    public function index(Request $request)
    {
        return Inertia::render('RM/PasienRujukan/PasienRujukansList');
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

        return Inertia::render('RM/PasienRujukan/PasienRujukansDetail', [
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
        ]);

        // Mengambil data yang diperlukan untuk penyimpanan
        $data = [
            'icd10_code' => $validated['icd10_code'],
            'no_transaksikj' => $validated['no_transaksikj'],
            'no_rm' => $validated['no_rm'],
            'kd_unit' => $validated['kd_unit'],
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
}
