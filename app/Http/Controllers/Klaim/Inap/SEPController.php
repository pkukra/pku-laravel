<?php

namespace App\Http\Controllers\Klaim\Inap;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Repositories\KlaimInap\SEPInapRepository;

class SEPController extends Controller
{
    protected $sepRepo;

    public function __construct(SEPInapRepository $sepRepo)
    {
        $this->sepRepo = $sepRepo;
    }

    public function viewHtml()
    {
        $data = $this->sepRepo->getDummyData();

        return view('klaim.inap.sep', $data);
    }

    public function index($kode_reg)
    {
        $data = (array)$this->sepRepo->getSEPDetail($kode_reg);
        
        // return response()->json($data);
        // return view('klaim.inap.sep', $data);
        $data['penyakit_premiers']= [];
        $data['penyakit_sekunders']= [];
        $data['tindakans']= [];
        $data['catatans']= [];

        $pdf = Pdf::loadView(
            'klaim.inap.sep',
            $data
        );

        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('SEP.pdf');

        // kalau mau langsung download:
        // return $pdf->download('SEP.pdf');
    }
}