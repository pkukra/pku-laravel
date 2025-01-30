<?php

namespace App\Http\Controllers\RM;

// use App\Models\PasienRujukan;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

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
                'PASIEN.NAMAPASIEN','PASIEN.TGL_LAHIR','PASIEN.GOL_DARAH','PASIEN.JENIS_KELAMIN','PASIEN.ALAMAT',
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
}
