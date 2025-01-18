<?php

namespace App\Http\Controllers\RM;

use App\Models\PasienRujukan;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PasienRujukanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Ambil parameter pencarian dari request
        $search = $request->input('search');

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

        if ($search) {
            $query->where('PASIEN_RUJUKAN.FRPPASIEN_ID', $search);
        } else {
            $query->take(10);
        }

        $pasien_rujukans = $query->get();

        $count = DB::connection('sqlsrv')
            ->table('PASIEN_RUJUKAN')
            ->count();

        return Inertia::render('RM/PasienRujukan/PasienRujukansList', [
            'pasien_rujukans' => $pasien_rujukans,
            'count' => $count,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(PasienRujukan $pasien_rujukan)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PasienRujukan $pasien_rujukan)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PasienRujukan $pasien_rujukan)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PasienRujukan $pasien_rujukan)
    {
        //
    }
}
