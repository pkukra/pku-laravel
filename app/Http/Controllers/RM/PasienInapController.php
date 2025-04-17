<?php

namespace App\Http\Controllers\RM;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Repositories\RM\PasienInapRepository;
use App\Repositories\RM\PasienInapEklaimRepository;
use App\Repositories\Casemix\RanapMonitRepository;

class PasienInapController extends Controller
{
    protected $pasienInapRepo;
    protected $bridgingEKlaimRepo;
    protected $RanapMonitRepo;

    // Dependency Injection Repository
    public function __construct(
        PasienInapRepository $pasienInapRepo,
        PasienInapEklaimRepository $bridgingEKlaimRepo,
        RanapMonitRepository $RanapMonitRepo,
    ) {
        $this->pasienInapRepo = $pasienInapRepo;
        $this->bridgingEKlaimRepo = $bridgingEKlaimRepo;
        $this->RanapMonitRepo = $RanapMonitRepo;
    }

    /**
     * index_data
     * Menampilkan daftar pasien inap dalam format JSON
     */
    public function index_data($no_rm)
    {
        // Mendapatkan data pasien inap menggunakan repository
        $pasien_inaps = $this->pasienInapRepo->getPasienInaps($no_rm);
        // $count = $this->pasienInapRepo->countPasienInap();

        return response()->json([
            'status' => "ok",
            'pasien_inaps' => $pasien_inaps,
            'count' => 0,
        ]);
    }

    /**
     * list_inap
     * Menampilkan detail pasien inap semuanya
     */
    public function list_inap()
    {
        // Mendapatkan detail pasien inap berdasarkan kode_reg
        $bangsal =  $this->RanapMonitRepo->getListKamarIndukRanap();
        return Inertia::render('RM/PasienInap/PasienInapList', [
            'bangsal' => $bangsal,
        ]);
    }

    /**
     * list_inap_data
     * Menampilkan daftar pasien inap dalam format JSON
     */
    public function list_inap_data(Request $request)
    {
        $tanggal_masuk = $request->get('tanggal_masuk');
        $tanggal_keluar = $request->get('tanggal_keluar');
        $page = (int) $request->get('page', 1);
        $per_page = (int) $request->get('per_page', 20);
        $kode_dokter = $request->get('kode_dokter');
        $no_rm = $request->get('no_rm');
        $kode_bangsal = $request->input('kode_bangsal'); // tambahkan ini

        $result = $this->pasienInapRepo->getAllPasienInaps(
            $tanggal_masuk,
            $page,
            $per_page,
            $kode_dokter,
            $no_rm,
            $tanggal_keluar,
            $kode_bangsal
        );

        return response()->json([
            'status' => "ok",
            'page' => $page,
            'data' => $result
        ]);
    }

    /**
     * show
     * Menampilkan detail pasien inap berdasarkan kode_reg
     */
    public function show($kode_reg)
    {
        // Mendapatkan detail pasien inap berdasarkan kode_reg
        $pasien_inap = $this->pasienInapRepo->getPasienInapDetail($kode_reg);
        return Inertia::render('RM/PasienInap/PasienInapDetail', [
            'pasien' => $pasien_inap,
            'kode_reg' => $kode_reg,
        ]);
    }

    /**
     * show_data
     * Menampilkan detail pasien inap berdasarkan kode_reg
     * RESPONSE JSON
     */
    public function show_data($kode_reg)
    {
        // Mendapatkan detail pasien inap berdasarkan kode_reg
        $pasien_inap = $this->pasienInapRepo->getPasienInapDetail($kode_reg);

        return response()->json([
            'pasien' => $pasien_inap,
        ]);
    }

    /**
     * get_nomer_sep
     * Menampilkan nomer sep berdasarkan kode transaksi
     */
    public function get_nomer_sep($kode_reg)
    {
        // Mendapatkan diagnosa berdasarkan kode transaksi
        $data = $this->pasienInapRepo->getSepPasienInap($kode_reg);
        return response()->json([
            'status' => "ok",
            'data' => $data,
        ]);
    }

    /**
     * update_nomer_sep
     * Update nomer sep berdasarkan kode transaksi
     */
    public function update_nomer_sep(Request $request, $kode_reg)
    {
        $validated = $request->validate([
            'no_rm' => 'required|string',
            'new_sep' => 'required|string|max:19|min:19',
            'poli' => 'required',
            'dpjp' => 'required',
        ]);

        // Mendapatkan diagnosa berdasarkan kode transaksi
        $response = $this->pasienInapRepo->updateNomerSepPasienInap($kode_reg, $validated['no_rm'], $validated['new_sep'], $validated['poli'], $validated['dpjp']);
        return response()->json($response);
    }

    /**
     * get_keadaan_keluar_rs
     * Menampilkan aktual keaadaan keluar dari setiap pasien by kode_reg
     */
    public function get_keadaan_keluar_rs($kode_reg)
    {
        // Menampilkan aktual keaadaan keluar dari setiap pasien by kode_reg
        $data = $this->pasienInapRepo->getKeadaanKeluarByTransaksi($kode_reg);

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
        $data = $this->pasienInapRepo->getKunjunganPasienByTransaksi($kode_reg);

        return response()->json([
            'status' => "ok",
            'data' => $data,
        ]);
    }

    /**
     * list_diagnosa
     * Menampilkan diagnosa berdasarkan kode transaksi
     */
    public function list_diagnosa($kode_reg)
    {
        // Mendapatkan diagnosa berdasarkan kode transaksi
        $diagnosa = $this->pasienInapRepo->getDiagnosaByTransaksi($kode_reg);

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
        $penyakit = $this->pasienInapRepo->searchPenyakit($searchTerm, $page);

        return response()->json([
            'status' => "ok",
            'data' => $penyakit,
            'page' => $page,
        ]);
    }

    /**
     * save_diagnosa
     * Menyimpan data diagnosa untuk pasien inap
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
        $isSaved = $this->pasienInapRepo->saveDiagnosa($data);

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
        $deleted = $this->pasienInapRepo->deleteDiagnosaById($id);

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
        $procedure = $this->pasienInapRepo->getProcedureByTransaksi($kode_reg);

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
        $procedure = $this->pasienInapRepo->searchProcedure($searchTerm, $page);

        return response()->json([
            'status' => "ok",
            'data' => $procedure,
            'page' => $page,
        ]);
    }

    /**
     * save_diagnosa
     * Menyimpan data diagnosa untuk pasien inap
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
        $isSaved = $this->pasienInapRepo->saveProcedureRanap($data);

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
        $deleted = $this->pasienInapRepo->deleteProcedureById($id);

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
        $data = $this->pasienInapRepo->getMrDiagnosaByTransaksi($kode_reg);

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

        $isUpdated = $this->pasienInapRepo->updateCatatanKhususByTransaksi($no_transaksi, $catatanKhusus);

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
        $data = $this->pasienInapRepo->getCaraMasukBPJS();
        return response()->json([
            'status' => "ok",
            'data' => $data,
        ]);
    }

    public function cari_keadaan_keluar_rs()
    {
        $data = $this->pasienInapRepo->getKeadaanKeluarRS();
        return response()->json([
            'status' => "ok",
            'data' => $data,
        ]);
    }

    public function cari_rs_rujukan()
    {
        $data = $this->pasienInapRepo->getRSRujukan();
        return response()->json([
            'status' => "ok",
            'data' => $data,
        ]);
    }

    public function update_cara_masuk_pulang(Request $request, $no_transaksi)
    {
        // Validate the input
        $validated = $request->validate([
            'kode_pasien' => 'required',
            'kode_unit' => 'required',
            'kode_dokter' => 'required',
            'tgl_masuk' => 'required',
            'cara_masuk' => 'required',
            'keadaan_keluar' => 'required',
            'sebab_kematian' => 'nullable|string',

            'keperawatan' => 'nullable',
            'kode_rs_rujuk_keluar' => 'nullable',
        ]);

        // Tambahkan no_transaksi ke dalam array data
        $validated['no_transaksi'] = $no_transaksi;

        $validated['email'] = Auth::user()->email;
        $validated['now'] = now();

        $isUpdated = $this->pasienInapRepo->updateCaraMasukPulangsByTransaksi($validated);

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
        $data = $this->pasienInapRepo->getResumeByTransaksi($kode_reg);

        return response()->json([
            'status' => "ok",
            'data' => $data,
        ]);
    }

    public function get_hasil_radiologi($kode_reg)
    {
        $data = $this->pasienInapRepo->getListHasilRadiologiByTransaksi($kode_reg);
        return response()->json([
            'status' => "ok",
            'data' => $data,
        ]);
    }

    public function get_berkas_rm($kode_reg)
    {
        $data = $this->pasienInapRepo->getListBerkasRMByRg($kode_reg);
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
     * bridging_final_process
     * Process bridging data ke eklaim
     */
    public function bridging_final_process($kode_reg, $no_sep)
    {
        $data = $this->bridgingEKlaimRepo->bridgingFinalProcess($kode_reg, $no_sep);
        return response()->json($data);
    }

    /**
     * bridging_kirim_klaim
     * Process bridging data ke eklaim
     */
    public function bridging_kirim_klaim($no_sep)
    {
        $data = $this->bridgingEKlaimRepo->bridgingKirimKlaimProcess($no_sep);
        return response()->json($data);
    }

    // get_all_obat
    public function get_all_obat($kode_reg)
    {
        $data = $this->pasienInapRepo->getListAllObatByTransaksi($kode_reg);
        return response()->json([
            'status' => "ok",
            'data' => $data,
        ]);
    }

    /**
     * bridging_cetak_klaim
     * Process bridging data ke eklaim
     */
    public function bridging_cetak_klaim($no_sep)
    {
        $data = $this->bridgingEKlaimRepo->bridgingCetakKlaim($no_sep);
        if (($data->status == "nok")) {
            return response()->json($data);
        }
        if (($data->response->metadata->code != 200)) {
            return response()->json($data);
        }

        // Ambil base64 string dari response
        $base64 = $data->response->data;

        // Decode base64 ke binary
        $pdfContent = base64_decode($base64);

        // Buat response file PDF
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $no_sep . '.pdf"');
    }
}
