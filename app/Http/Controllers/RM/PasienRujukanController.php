<?php

namespace App\Http\Controllers\RM;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PasienRujukanController extends Controller
{
    /**
     * index
     * Load jsx page
     */
    public function index(Request $request)
    {
        return Inertia::render('RM/PasienRujukan/PasienRujukansList');
    }

    /**
     * index_data
     * Listing JSON data
     */
    public function index_data(Request $request, $no_rm)
    {
        $query = DB::connection('sqlsrv')
            ->table('PASIEN_RUJUKAN')
            ->join('DOKTER', 'PASIEN_RUJUKAN.FRPDOKTER_ID', '=', 'DOKTER.FMDDOKTER_ID')
            ->join('POLIKLINIK', 'PASIEN_RUJUKAN.FRPUNIT', '=', 'POLIKLINIK.FMPKLINIK_ID')
            ->orderBy('FRPTGL', 'desc')
            ->select(
                'PASIEN_RUJUKAN.*',
                'DOKTER.FMDDOKTERN',
                'POLIKLINIK.FMPKLINIKN'
            );
        $query->where('PASIEN_RUJUKAN.FRPPASIEN_ID', $no_rm);

        $pasien_rujukans = $query->get();

        $count = DB::connection('sqlsrv')
            ->table('PASIEN_RUJUKAN')
            ->count();

        return response()->json([
            'status'=> "ok",
            'pasien_rujukans' => $pasien_rujukans,
            'count' => $count,
        ]);
    }

    /**
     * show
     * Load jsx page
     */
    public function show($kode_reg)
    {
        $query = DB::connection('sqlsrv')
            ->table('PASIEN_RUJUKAN')
            ->join('PASIEN', 'PASIEN_RUJUKAN.FRPPASIEN_ID', '=', 'PASIEN.KD_PASIEN')
            ->join('DOKTER', 'PASIEN_RUJUKAN.FRPDOKTER_ID', '=', 'DOKTER.FMDDOKTER_ID')
            ->join('POLIKLINIK', 'PASIEN_RUJUKAN.FRPUNIT', '=', 'POLIKLINIK.FMPKLINIK_ID')
            ->orderBy('FRPTGL', 'desc')
            ->select(
                'PASIEN.NAMAPASIEN',
                'PASIEN.TGL_LAHIR',
                'PASIEN.GOL_DARAH',
                'PASIEN.JENIS_KELAMIN',
                'PASIEN.ALAMAT',
                'PASIEN_RUJUKAN.*',
                'DOKTER.FMDDOKTERN',
                'POLIKLINIK.FMPKLINIKN'
            );
        $query->where('PASIEN_RUJUKAN.FRPNOTRANSAKSIKJ', $kode_reg);

        $pasien_rujukans = $query->first();

        $count = DB::connection('sqlsrv')
            ->table('PASIEN_RUJUKAN')
            ->count();

        return Inertia::render('RM/PasienRujukan/PasienRujukansDetail', [
            'pasien' => $pasien_rujukans,
            'count' => $count,
        ]);
    }

    /**
     * list_diagnosa
     * Listing penyakit/diagnosa dari setiap pasien rujukan berdasar kode transaksi (rrj)
     */
    public function list_diagnosa(Request $request, $no_transaksi)
    {
        $query = DB::connection('sqlsrv')
            ->table('MR_PENYAKIT')
            ->join('PENYAKIT', 'MR_PENYAKIT.MRPKD_PENYAKIT', '=', 'PENYAKIT.KD_PENYAKIT')
            ->orderBy('MR_PENYAKIT.MRPURUT_MASUK', 'ASC')
            ->select(
                'MR_PENYAKIT.*',
                'PENYAKIT.PENYAKIT',
            );
        $query->where('MR_PENYAKIT.MRPNO_TRANSAKSI', $no_transaksi);

        $data = $query->get();

        return response()->json([
            'status'=> "ok",
            'data' => $data,
        ]);
    }

    /**
     * cari_penyakit
     * Listing penyakit/diagnosa dari database penyakit (icd)
     */
    public function cari_penyakit(Request $request)
    {
        $searchTerm = $request->input('query');
        $page = $request->input('page', 1); // Get the current page number (default is 1)
        $selectedDiagnosa = $request->input('selected_diagnosa', []); // Get the list of selected diagnosa (IDs)

        $query = DB::connection('sqlsrv')
            ->table('PENYAKIT')
            ->select('PENYAKIT.*')
            ->when($searchTerm, function ($q) use ($searchTerm) {
                $q->where(function ($q) use ($searchTerm) {
                    $q->where('PENYAKIT.KD_PENYAKIT', 'like', '%' . $searchTerm . '%')
                        ->orWhere('PENYAKIT.PENYAKIT', 'like', '%' . $searchTerm . '%');
                });
            });
            // ->when(count($selectedDiagnosa) > 0, function ($q) use ($selectedDiagnosa) {
            //     $q->whereNotIn('PENYAKIT.KD_PENYAKIT', $selectedDiagnosa); // Exclude the selected diagnosa from results
            // });

        // Paginate the results, 20 items per page
        $data = $query->offset(($page - 1) * 20) // Skip based on current page
            ->limit(20) // Limit the results per page
            ->get();

        return response()->json([
            'status' => "ok",
            'data' => $data,
            'page' => $page,
        ]);
    }


    /**
     * save_diagnosa
     * save 
     */
    public function save_diagnosa(Request $request)
    {
        $validated = $request->validate([
            'icd10_code' => 'required|string|max:10',
            'no_transaksikj' => 'required|string|max:20',
            'no_rm' => 'required|string|max:20',
            'kd_unit' => 'required|string|max:20',
            'tgl_masuk' => 'required|date',
        ]);

        $no_transaksikj = $request->input('no_transaksikj');
        $tgl_masuk = Carbon::parse($validated['tgl_masuk']);
        $now = date('Y-m-d H:i:s');

        $query = DB::connection('sqlsrv')
            ->table('MR_PENYAKIT')
            ->orderBy('MR_PENYAKIT.MRPURUT_MASUK', 'DESC')
            ->limit(1)
            ->select(
                'MRPURUT_MASUK',
            );
        $query->where('MR_PENYAKIT.MRPNO_TRANSAKSI', $no_transaksikj);
        $no_urut_masuk = $query->first();
        $no_urut_masuk = $no_urut_masuk ? $no_urut_masuk->MRPURUT_MASUK + 1 : 1;

        try {
            DB::connection('sqlsrv')
                ->table('MR_PENYAKIT')
                ->insert([
                    'MRPKD_PENYAKIT' => $request->input('icd10_code'),
                    'MRPNO_TRANSAKSI' => $no_transaksikj,
                    'MRPKD_PASIEN' => $request->input('no_rm'),
                    'MRPKD_UNIT' => $request->input('kd_unit'),
                    'MRPTGL_MASUK' => $tgl_masuk,
                    'MRPURUT_MASUK' => $no_urut_masuk,
                    'MRPJENIS' => 'RJ',
                    'MRPSTAT_DIAG' => 1, // xxx
                    'MRPKASUS' => 1, // xxx
                    'STATUS_IMUN' => 1, // xxx
                    'MRPIMUNKE' => 1, // xxx
                    'USER_ID' => Auth::id(),
                    'UPDATE_DT' => $now,
                ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => "nok",
                'message' => 'Terjadi kesalahan, silakan coba lagi.',
                'error' => $e
            ], 500);
        }

        return response()->json([
            'status'=> "ok",
            'message' => 'Diagnosa berhasil disimpan',
        ]);
    }
}
