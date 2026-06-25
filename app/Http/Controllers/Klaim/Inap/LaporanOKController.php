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
use App\Repositories\KlaimInap\KlaimInapRepository;

class LaporanOKController extends Controller
{
    protected $klaimInapRepo;

    public function __construct(KlaimInapRepository $klaimInapRepo)
    {
        $this->klaimInapRepo = $klaimInapRepo;
    }

    public function get_all_jok($kode_reg)
    {
        $jok_arr = $this->klaimInapRepo->getAllJOK($kode_reg);
        return response()->json($jok_arr);
    }
}
