<?php

namespace App\Http\Controllers\RM;

use App\Models\PasienRujukan;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PasienRujukanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(PasienRujukan $pasien_rujukan)
    {
        return Inertia::render('PasienRujukan/PasienRujukansList', [
            'pasien_rujukans' => $pasien_rujukan->orderBy('FRPTGL', 'desc')->take(10)->get(),
            'count' => $pasien_rujukan->count(),
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
