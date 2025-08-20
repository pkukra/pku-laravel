<?php

namespace App\Http\Controllers\RM;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Repositories\RM\PasienRujukanRepository;
use App\Repositories\RM\PasienRujukanEklaimRepository;

class PasienRujukanController extends Controller
{
    protected $pasienRujukanRepo;
    protected $bridgingEKlaimRepo;

    // Dependency Injection Repository
    public function __construct(
        PasienRujukanRepository $pasienRujukanRepo,
        PasienRujukanEklaimRepository $bridgingEKlaimRepo,
    ) {
        $this->pasienRujukanRepo = $pasienRujukanRepo;
        $this->bridgingEKlaimRepo = $bridgingEKlaimRepo;
    }

    /**
     * get_cusromers
     * Menampilkan daftar pasien rujukan dalam format JSON
     */
    public function get_cusromers()
    {
        // Mendapatkan detail pasien rujukan berdasarkan kode_reg
        $data = $this->pasienRujukanRepo->getCustomers();
        return response()->json($data);
    }

    /**
     * agregate_sep
     */
    public function agregate_sep($pasien_id)
    {
        // Mendapatkan detail pasien rujukan berdasarkan pasien_id
        $data = $this->pasienRujukanRepo->agregateSEP($pasien_id);
        return response()->json($data);
    }

    /**
     * search_diagnosis_cbg langsung dari eklaim
     */
    public function search_diagnosis_cbg(Request $request)
    {
        $validated = $request->validate([
            'keyword' => 'nullable|string',
        ]);

        $keyword = $validated['keyword'];
        $data = $this->bridgingEKlaimRepo->searchDiagnosis($keyword);
        return response()->json($data);
    }

    /**
     * search_procedure_cbg langsung dari eklaim
     */
    public function search_procedure_cbg(Request $request)
    {
        $validated = $request->validate([
            'keyword' => 'nullable|string',
        ]);

        $keyword = $validated['keyword'];
        $data = $this->bridgingEKlaimRepo->searchProcedure($keyword);
        return response()->json($data);
    }

    /**
     * index
     * Load halaman utama daftar pasien rujukan
     */
    public function index(Request $request)
    {
        return Inertia::render('RM/ListKunjungan');
    }

    /**
     * list_rujukan
     * Menampilkan daftar pasien rujukan dalam format JSON
     */
    public function list_rujukan()
    {
        return Inertia::render('RM/PasienRujukan/PasienRujukanList');
    }

    public function list_rujukan_data(Request $request)
    {
        $date = $request->get('date');
        $page = (int) $request->get('page', 1);
        $per_page = (int) $request->get('per_page', 20);
        $kode_poly = $request->get('kode_poly'); // filter kode poli
        $kode_dokter = $request->get('kode_dokter'); // filter kode dokter
        $no_rm = $request->get('no_rm'); // filter no rekam medis
        $is_inacbg_final = $request->get('is_inacbg_final');

        $pasien_rujukans = $this->pasienRujukanRepo->getAllPasienRujukans(
            $date,
            $page,
            $per_page,
            $kode_poly,
            $kode_dokter,
            $no_rm,
            $is_inacbg_final
        );

        return response()->json([
            'status' => "ok",
            'page' => $page,
            'data' => $pasien_rujukans
        ]);
    }

    /**
     * index_data
     * Menampilkan daftar pasien rujukan dalam format JSON
     */
    public function index_data($no_rm)
    {
        // Mendapatkan data pasien rujukan menggunakan repository
        $pasien_rujukans = $this->pasienRujukanRepo->getPasienRujukans($no_rm);
        $count = $this->pasienRujukanRepo->countPasienRujukan($no_rm);

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
        return Inertia::render('RM/PasienRujukan/PasienRujukanDetail', [
            'pasien' => $pasien_rujukans,
            'kode_reg' => $kode_reg,
        ]);
    }

    /**
     * show_data
     * Menampilkan detail pasien rujukan berdasarkan kode_reg
     * RESPONSE JSON
     */
    public function show_data($kode_reg)
    {
        // Mendapatkan detail pasien rujukan berdasarkan kode_reg
        $pasien_rujukan = $this->pasienRujukanRepo->getPasienRujukanDetail($kode_reg);

        return response()->json([
            'pasien' => $pasien_rujukan->data,
        ]);
    }

    /**
     * get_nomer_sep
     * Menampilkan nomer sep berdasarkan kode transaksi
     */
    public function get_nomer_sep($kode_reg, $kode_reg_kj)
    {
        // Mendapatkan diagnosa berdasarkan kode transaksi
        $data = $this->pasienRujukanRepo->getSepPasienRujukan($kode_reg, $kode_reg_kj);
        return response()->json([
            'status' => "ok",
            'data' => $data,
        ]);
    }

    /**
     * update_nomer_sep
     * Update nomer sep berdasarkan kode transaksi
     */
    public function update_nomer_sep(Request $request, $kode_reg, $kode_reg_kj)
    {
        $validated = $request->validate([
            'no_rm' => 'required|string',
            'new_sep' => 'required|string|max:19|min:19',
            'poli' => 'required',
            'dpjp' => 'required',
        ]);

        // Mendapatkan diagnosa berdasarkan kode transaksi
        $response = $this->pasienRujukanRepo->updateNomerSepPasienRujukan($kode_reg, $kode_reg_kj, $validated['no_rm'], $validated['new_sep'], $validated['poli'], $validated['dpjp']);
        return response()->json($response);
    }

    /**
     * get_keadaan_keluar_rs
     * Menampilkan aktual keaadaan keluar dari setiap pasien by kode_reg
     */
    public function get_keadaan_keluar_rs($kode_reg)
    {
        // Menampilkan aktual keaadaan keluar dari setiap pasien by kode_reg
        $data = $this->pasienRujukanRepo->getKeadaanKeluarByTransaksi($kode_reg);

        return response()->json([
            'status' => "ok",
            'data' => $data,
        ]);
    }

    /**
     * get_kunjungan_pasien
     * Menampilkan aktual kunjungan pasien data dari tabel KUNJUNGANPASIEN
     */
    public function get_kunjungan_pasien($kode_reg)
    {
        // Menampilkan aktual keaadaan keluar dari setiap pasien by kode_reg
        $data = $this->pasienRujukanRepo->getKunjunganPasienByTransaksi($kode_reg);

        return response()->json([
            'status' => "ok",
            'data' => $data,
        ]);
    }

    /**
     * list_diagnosa
     * Menampilkan diagnosa berdasarkan kode transaksi
     */
    public function list_diagnosa($kode_reg, $no_sep = null)
    {
        // Mendapatkan diagnosa berdasarkan kode transaksi
        $diagnosa = $this->pasienRujukanRepo->getDiagnosaByTransaksi($kode_reg, $no_sep);
        return response()->json([
            'status' => "ok",
            'data' => $diagnosa,
        ]);
    }

    /**
     * list_diagnosa_idrg
     * Menampilkan diagnosa berdasarkan kode transaksi
     */
    public function list_diagnosa_idrg($kode_reg, $no_sep = null)
    {
        // Mendapatkan diagnosa berdasarkan kode transaksi
        $diagnosa = $this->pasienRujukanRepo->getDiagnosaIDRGByTransaksi($kode_reg, $no_sep);

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
     * cari_penyakit_im
     * Pencarian penyakit/diagnosa IM di database berdasarkan input (ICD-10 IM)
     */
    public function cari_penyakit_im(Request $request)
    {
        $searchTerm = $request->input('query');
        $page = $request->input('page', 1); // Halaman saat ini (default 1)

        // Mendapatkan data penyakit berdasarkan pencarian
        $penyakit = $this->pasienRujukanRepo->searchPenyakitIM($searchTerm, $page);

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
            'no_transaksikj' => 'string|max:20',
            'no_sep' => 'string|max:20',
            'icd10_code' => 'required|string|max:10',
            'no_rm' => 'required|string|max:20',
            'kd_unit' => 'max:20',
            'tgl_masuk' => 'required|date',
            'status_diagnosa' => 'required|string',
            'kasus' => 'nullable|string',
        ]);

        $no_sep = $validated['no_sep'] ?? null;
        $no_transaksikj = $validated['no_transaksikj'] ?? null;

        if ($no_sep == null && $no_transaksikj == null) {
            return response()->json([
                'status' => "nok",
                'message' => 'salah satu harus diisi: no_sep atau no_transaksikj',
            ],  422);
        }

        // Mengambil data yang diperlukan untuk penyimpanan
        $data = [
            'icd10_code' => $validated['icd10_code'],
            'no_transaksikj' => $no_transaksikj,
            'no_sep' => $no_sep,
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
     * update_diagnosa
     * Update data diagnosa
     */
    public function update_diagnosa(Request $request, $id)
    {
        // Validasi input
        $validated = $request->validate([
            'icd10_code' => 'required|string|max:10',
            'status_diagnosa' => 'required|string',
        ]);

        $code = $validated['icd10_code'];
        $status_diagnosa = $validated['status_diagnosa'];

        // Menyimpan data diagnosa melalui repository
        $isSaved = $this->pasienRujukanRepo->updateDiagnosa($id, $code, $status_diagnosa);

        if ($isSaved) {
            return response()->json([
                'status' => "ok",
                'message' => 'Diagnosa berhasil diupdate',
            ]);
        }

        return response()->json([
            'status' => "nok",
            'message' => 'Terjadi kesalahan saat update diagnosa',
        ], 500);
    }

    /**
     * update_procedure
     * Update data procedure
     */
    public function update_procedure(Request $request, $id)
    {
        // Validasi input
        $validated = $request->validate([
            'icd9_code' => 'required|string|max:10',
        ]);

        $code = $validated['icd9_code'];

        // Menyimpan data procedure melalui repository
        $isSaved = $this->pasienRujukanRepo->updateProcedure($id, $code);

        if ($isSaved) {
            return response()->json([
                'status' => "ok",
                'message' => 'Procedure berhasil diupdate',
            ]);
        }

        return response()->json([
            'status' => "nok",
            'message' => 'Terjadi kesalahan saat update procedure',
        ], 500);
    }

    /**
     * save_diagnosa_idrg
     * Menyimpan data diagnosa versi IDRG untuk pasien rujukan 
     */
    public function save_diagnosa_idrg(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'code' => 'required|string|max:10',
            'no_sep' => 'string|max:20',
            'no_transaksikj' => 'string|max:20',
            'pasien_id' => 'required|string|max:20',
        ]);

        if (isset($validated['no_sep']) && empty($validated['no_sep']) && empty($validated['no_transaksikj'])) {
            return response()->json([
                'status' => "nok",
                'message' => 'salah satu harus diisi: no_sep atau no_transaksikj',
            ],  422);
        }

        // Menyimpan data diagnosa melalui repository
        $isSaved = $this->pasienRujukanRepo->saveDiagnosaIDRG($validated);

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
     * delete_diagnosa_idrg
     * Hapus diagnosa berdasarkan ID
     */
    public function delete_diagnosa_idrg($id)
    {
        // Hapus diagnosa berdasarkan ID dari tabel PASIEN_DIAGNOSA_IM
        $deleted = $this->pasienRujukanRepo->deleteDiagnosaIDRGById($id);

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
     * set primary diagnosa im
     * Hapus diagnosa berdasarkan ID
     */
    public function diagnosa_idrg_set_primary($id)
    {
        // Hapus diagnosa berdasarkan ID dari tabel PASIEN_DIAGNOSA_IM
        $deleted = $this->pasienRujukanRepo->setDiagnosaIDRGPrimary($id);

        if ($deleted) {
            return response()->json([
                'status' => "ok",
                'message' => 'Diagnosa berhasil di set primary',
            ]);
        }

        return response()->json([
            'status' => "nok",
            'message' => 'Terjadi kesalahan saat set primary diagnosa',
        ], 500);
    }

    /**
     * list_procedure
     * Menampilkan procedure berdasarkan kode transaksi
     */
    public function list_procedure($kode_reg, $no_sep = null)
    {
        // Mendapatkan procedure berdasarkan kode transaksi
        $procedure = $this->pasienRujukanRepo->getProcedureByTransaksi($kode_reg, $no_sep);

        return response()->json([
            'status' => "ok",
            'data' => $procedure,
        ]);
    }

    /**
     * list_procedure_idrg
     * Menampilkan procedure berdasarkan kode transaksi
     */
    public function list_procedure_idrg($kode_reg, $no_sep = null)
    {
        // Mendapatkan procedure berdasarkan kode transaksi
        $procedure = $this->pasienRujukanRepo->getProcedureIDRGByTransaksi($kode_reg, $no_sep);

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
     * cari_procedure_im
     * Pencarian procedure/diagnosa di database berdasarkan input
     */
    public function cari_procedure_im(Request $request)
    {
        $searchTerm = $request->input('query');
        $page = $request->input('page', 1); // Halaman saat ini (default 1)

        // Mendapatkan data procedure berdasarkan pencarian
        $procedure = $this->pasienRujukanRepo->searchProcedureIM($searchTerm, $page);

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
            'no_transaksikj' => 'string|max:20',
            'no_sep' => 'string|max:20',
            'no_rm' => 'required|string|max:20',
            'kd_unit' => 'max:20',
            'tgl_masuk' => 'required|date',
        ]);

        $no_sep = $validated['no_sep'] ?? null;
        $no_transaksikj = $validated['no_transaksikj'] ?? null;

        if ($no_sep == null && $no_transaksikj == null) {
            return response()->json([
                'status' => "nok",
                'message' => 'salah satu harus diisi: no_sep atau no_transaksikj',
            ],  422);
        }

        // Mengambil data yang diperlukan untuk penyimpanan
        $data = [
            'icd9_code' => $validated['icd9_code'],
            'no_sep' => $no_sep,
            'no_transaksikj' => $no_transaksikj,
            'no_rm' => $validated['no_rm'],
            'kd_unit' => $validated['kd_unit'],
            'tgl_masuk' => Carbon::parse($validated['tgl_masuk']),
            'user_id' => Auth::id(),
        ];

        // Menyimpan data procedure melalui repository
        $isSaved = $this->pasienRujukanRepo->saveProcedureRajal($data);

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
     * save_procedure_idrg
     * Menyimpan data procedure versi IDRG untuk pasien rujukan 
     */
    public function save_procedure_idrg(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'code' => 'required|string|max:10',
            'no_sep' => 'string|max:20',
            'multiplicity' => 'required|integer|min:1',
            'no_transaksikj' => 'string|max:20',
            'pasien_id' => 'required|string|max:20',
        ]);

        if (isset($validated['no_sep']) && empty($validated['no_sep']) && empty($validated['no_transaksikj'])) {
            return response()->json([
                'status' => "nok",
                'message' => 'salah satu harus diisi: no_sep atau no_transaksikj',
            ],  422);
        }

        // Menyimpan data procedure melalui repository
        $isSaved = $this->pasienRujukanRepo->saveProcedureIDRG($validated);

        if ($isSaved) {
            return response()->json([
                'status' => "ok",
                'message' => 'Procedure berhasil disimpan',
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
     * delete_procedure_idrg
     * Hapus procedure berdasarkan ID
     */
    public function delete_procedure_idrg($id)
    {
        // Hapus procedure_idrg berdasarkan ID dari tabel PASIEN_TINDAKAN_IM
        $deleted = $this->pasienRujukanRepo->deleteProcedureIDRGById($id);

        if ($deleted) {
            return response()->json([
                'status' => "ok",
                'message' => 'Procedure iDRG berhasil dihapus',
            ]);
        }

        return response()->json([
            'status' => "nok",
            'message' => 'Terjadi kesalahan saat menghapus procedure iDRG',
        ], 500);
    }

    /**
     * set primary procedure im
     * 
     */
    public function procedure_idrg_set_primary($id)
    {
        $deleted = $this->pasienRujukanRepo->setProcedureIDRGPrimary($id);

        if ($deleted) {
            return response()->json([
                'status' => "ok",
                'message' => 'Procedure berhasil di set primary',
            ]);
        }

        return response()->json([
            'status' => "nok",
            'message' => 'Terjadi kesalahan saat set primary procedure',
        ], 500);
    }

    /**
     * update multiplicity procedure im
     * 
     */
    public function procedure_idrg_udpate_multiplicity(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required',
            'multiplicity' => 'required|min:1',
        ]);

        $updated = $this->pasienRujukanRepo->procedureIDRGUpdatemultiplicity($validated['id'], $validated['multiplicity']);
        if ($updated) {
            return response()->json([
                'status' => "ok",
                'message' => 'Update miltiplicity berhasil',
            ]);
        }

        return response()->json([
            'status' => "nok",
            'message' => 'Terjadi kesalahan saat update miltiplicity',
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

    public function cari_cara_masuk_bpjs()
    {
        $data = $this->pasienRujukanRepo->getCaraMasukBPJS();
        return response()->json([
            'status' => "ok",
            'data' => $data,
        ]);
    }

    public function cari_keadaan_keluar_rs()
    {
        $data = $this->pasienRujukanRepo->getKeadaanKeluarRS();
        return response()->json([
            'status' => "ok",
            'data' => $data,
        ]);
    }

    public function cari_rs_rujukan()
    {
        $data = $this->pasienRujukanRepo->getRSRujukan();
        return response()->json([
            'status' => "ok",
            'data' => $data,
        ]);
    }

    public function update_keperawatan(Request $request, $no_transaksi_kj)
    {
        // Validate the input
        $validated = $request->validate([
            'kode_pasien' => 'required',
            'kode_unit' => 'required',
            'kode_dokter' => 'required',
            'tgl_masuk' => 'required',
            'cara_masuk' => 'required',
            'keadaan_keluar' => 'nullable',
            'sebab_kematian' => 'nullable|string',

            'keperawatan' => 'nullable',
            'kode_rs_rujuk_keluar' => 'nullable',

            'berat_lahir' => 'nullable',
            'sitb' => 'nullable',
        ]);

        // Tambahkan no_transaksi_kj ke dalam array data
        $validated['no_transaksi_kj'] = $no_transaksi_kj;

        $validated['email'] = Auth::user()->email;
        $validated['now'] = now();

        $isUpdated = $this->pasienRujukanRepo->updateKeperawatan($validated);

        if ($isUpdated) {
            return response()->json([
                'status' => "ok",
                'message' => 'Data berhasil disimpan',
            ]);
        }

        return response()->json([
            'status' => "nok",
            'message' => 'Terjadi kesalahan saat menyimpan Data',
        ], 500);
    }

    public function get_resume($kode_reg)
    {
        // Mendapatkan data resume berdasarkan kode transaksi
        $data = $this->pasienRujukanRepo->getResumeByTransaksi($kode_reg);

        return response()->json([
            'status' => "ok",
            'data' => $data,
        ]);
    }

    public function get_hasil_radiologi($kode_reg_kj)
    {
        $data = $this->pasienRujukanRepo->getListHasilRadiologiByTransaksi($kode_reg_kj);
        return response()->json([
            'status' => "ok",
            'data' => $data,
        ]);
    }

    /**
     * bridging_data_process
     * Process bridging data ke eklaim
     */
    public function bridging_data_process($no_sep)
    {
        $data = $this->bridgingEKlaimRepo->bridgingDataProcess($no_sep);
        return response()->json($data);
    }

    /**
     * bridging_import_idrg_to_inacbg
     * Process bridging data ke eklaim
     */
    public function bridging_import_idrg_to_inacbg($no_sep)
    {
        $data = $this->bridgingEKlaimRepo->bridgingImportIdrgToIncbg($no_sep);
        return response()->json($data);
    }

    /**
     * bridging_final_process
     * Process bridging data ke eklaim
     */
    public function bridging_final_process($no_sep)
    {
        $data = $this->bridgingEKlaimRepo->bridgingFinalProcess($no_sep);
        return response()->json($data);
    }

    /**
     * bridging_data_idrg
     * Process bridging data ke eklaim
     */
    public function bridging_data_idrg($no_sep)
    {
        $data = $this->bridgingEKlaimRepo->bridgingDataIDRG($no_sep);
        return response()->json($data);
    }

    /**
     * bridging_final_idrg
     * Process bridging final idrg ke eklaim
     */
    public function bridging_final_idrg($no_sep)
    {
        $data = $this->bridgingEKlaimRepo->bridgingFinalIDRG($no_sep);
        return response()->json($data);
    }

    /**
     * get_idrg_group_data
     * Menampilkan procedure berdasarkan kode transaksi
     */
    public function get_idrg_group_data($no_sep)
    {
        // Mendapatkan procedure berdasarkan kode transaksi
        $procedure = $this->pasienRujukanRepo->getIDRGGroupDataByTransaksi($no_sep);

        return response()->json([
            'status' => "ok",
            'data' => $procedure,
        ]);
    }

    /**
     * edit_ulang_idrg
     * Menampilkan procedure berdasarkan kode transaksi
     */
    public function edit_ulang_idrg($no_sep)
    {
        $data = $this->bridgingEKlaimRepo->bridgingEditUlangIDRG($no_sep);
        return response()->json($data);
    }

    /**
     * list_all_raber
     * Menampilkan procedure berdasarkan kode transaksi
     */
    public function list_all_raber($no_sep)
    {
        $data = $this->pasienRujukanRepo->listAllRaber($no_sep);
        return response()->json($data);
    }

    /**
     * grouping_inacbg_stage_satu
     * Menampilkan procedure berdasarkan kode transaksi
     */
    public function grouping_inacbg_stage_satu($no_sep)
    {
        $data = $this->bridgingEKlaimRepo->bridgingGroupingInaStageSatu($no_sep);
        return response()->json($data);
    }

    /**
     * grouping_inacbg_stage_dua
     * Menampilkan procedure berdasarkan kode transaksi
     */
    public function grouping_inacbg_stage_dua(Request $request, $no_sep)
    {
        $validated = $request->validate([
            'special_cmg' => 'required|string',
        ]);
        $special_cmg = $validated['special_cmg'];

        $data = $this->bridgingEKlaimRepo->bridgingGroupingInaStageDua($no_sep, $special_cmg);
        return response()->json($data);
    }

    /**
     * bridging_final_inacbg
     * Menampilkan procedure berdasarkan kode transaksi
     */
    public function bridging_final_inacbg($no_sep)
    {
        $data = $this->bridgingEKlaimRepo->bridgingFinalINACBG($no_sep);
        return response()->json($data);
    }

    /**
     * edit_ulang_inacbg
     * Menampilkan procedure berdasarkan kode transaksi
     */
    public function edit_ulang_inacbg($no_sep)
    {
        $data = $this->bridgingEKlaimRepo->bridgingEditUlangINACBG($no_sep);
        return response()->json($data);
    }

    /**
     * bridging_final_klaim
     * Menampilkan procedure berdasarkan kode transaksi
     */
    public function bridging_final_klaim($no_sep)
    {
        $data = $this->bridgingEKlaimRepo->bridgingFinalKlaim($no_sep);
        return response()->json($data);
    }

    /**
     * bridging_reedit_klaim
     * Menampilkan procedure berdasarkan kode transaksi
     */
    public function bridging_reedit_klaim($no_sep)
    {
        $data = $this->bridgingEKlaimRepo->bridgingReeditKlaim($no_sep);
        return response()->json($data);
    }

    /**
     * bridging_send_invidual_klaim
     * Process bridging data ke eklaim
     */
    public function bridging_send_invidual_klaim($no_sep)
    {
        $data = $this->bridgingEKlaimRepo->bridgingKirimKlaimIndividualProcess($no_sep);
        return response()->json($data);
    }

    /**
     * bridging_get_claim_data
     * Process bridging data ke eklaim
     */
    public function bridging_get_claim_data($no_sep)
    {
        $data = $this->bridgingEKlaimRepo->bridgingGetClaimData($no_sep);
        return response()->json($data);
    }

    /**
     * get_inacbg_group_data
     * Menampilkan procedure berdasarkan kode transaksi
     */
    public function get_inacbg_group_data($no_sep)
    {
        $data = $this->pasienRujukanRepo->getINACBGGroupDataByTransaksi($no_sep);

        return response()->json($data);
    }

    /**
     * get_permintaan_rad_n_lab
     */
    public function get_permintaan_rad_n_lab($no_transaksi)
    {
        $data = $this->pasienRujukanRepo->getPermintaanRadLab($no_transaksi);
        return response()->json($data);
    }

    /**
     * procedures_history
     * Menampilkan procedure berdasarkan kode pasien
     */
    public function procedures_history($pasien_id)
    {
        $data = $this->pasienRujukanRepo->getProceduresHistory($pasien_id);
        return response()->json($data);
    }

    /**
     * final_pasien_umum
     * Process bridging data ke eklaim
     */
    public function final_pasien_umum(Request $request)
    {
        $validated = $request->validate([
            'kode_reg' => 'required|string',
            'kode_reg_kj' => 'required|string',
        ]);

        $kode_reg = $validated['kode_reg'];
        $kode_reg_kj = $validated['kode_reg_kj'];

        $data = $this->pasienRujukanRepo->finalPasienUmum($kode_reg, $kode_reg_kj);
        return response()->json($data);
    }

    /**
     * dev_isi_kode_reg
     * Menampilkan procedure berdasarkan kode pasien
     */
    public function dev_isi_kode_reg()
    {
        $data = $this->pasienRujukanRepo->setKodeRegRajal();
        return response()->json($data);
    }
}
