<?php

namespace App\Http\Controllers\Klaim\Inap;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Repositories\KlaimInap\SEPInapRepository;

class LaporanOKController extends Controller
{
    protected $sepRepo;

    public function __construct(SEPInapRepository $sepRepo)
    {
        $this->sepRepo = $sepRepo;
    }

    public function index($kode_reg)
    {
        $data = $this->sepRepo->getSEPDetail($kode_reg);
        $diagnosis = $this->sepRepo->getAllDiagnosis($data->FMNOSEP);
        $procedures = $this->sepRepo->getAllProcedures($data->FMNOSEP);
        $data = (array) $data;

        // return response()->json($diagnosis);
        // return view('klaim.inap.sep', $data);

        $qrDPJP = Builder::create()
            ->writer(new PngWriter())
            ->data($data['FMDDOKTERN'].' - '.$data['FMTGL_SEP'])
            ->size(100)
            ->build();

        $qrPasien = Builder::create()
            ->writer(new PngWriter())
            ->data($data['NAMAPASIEN'] . ' - ' . $data['FMTGL_LAHIR'])
            ->size(100)
            ->build();

        $data['qrDPJP'] = base64_encode($qrDPJP->getString());
        $data['qrPasien'] = base64_encode($qrPasien->getString());

        $data['penyakit_premiers'] = $diagnosis->where('is_primary', '1')->values()->all();
        $data['penyakit_sekunders'] = $diagnosis->where('is_primary', '0')->values()->all();
        $data['tindakans'] = $procedures->values()->all();
        $data['catatans'] = [];

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
