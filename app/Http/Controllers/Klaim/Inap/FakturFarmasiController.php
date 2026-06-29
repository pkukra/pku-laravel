<?php

namespace App\Http\Controllers\Klaim\Inap;

use App\Http\Controllers\Controller;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Repositories\RM\PasienInapRepository;
use App\Repositories\KlaimInap\SEPInapRepository;

class FakturFarmasiController extends Controller
{
    protected $pasienInapRepo;
    protected $sepRepo;

    public function __construct(PasienInapRepository $pasienInapRepo, SEPInapRepository $sepRepo)
    {
        $this->pasienInapRepo = $pasienInapRepo;
        $this->sepRepo = $sepRepo;
    }

    public function index($kode_reg)
    {
        $reseps = $this->pasienInapRepo->getListAllObatByTransaksi($kode_reg);
        $viewData = (array)$this->sepRepo->getSEPDetail($kode_reg);
        // return response()->json($viewData);

        $viewData['data'] = $reseps;

        $pdf = Pdf::loadView(
            'klaim.inap.faktur_farmasi',
            $viewData
        );

        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('faktur_farmasi.pdf');
    }
}
