<?php

namespace App\Http\Controllers\Klaim\Inap;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Repositories\KlaimInap\KlaimInapRepository;
use App\Repositories\KlaimInap\LaporanAnastesiRepository;

class LaporanAnastesiController extends Controller
{
    protected $klaimInapRepo;
    protected $lapAnastesiRepo;

    public function __construct(KlaimInapRepository $klaimInapRepo, LaporanAnastesiRepository $lapAnastesiRepo)
    {
        $this->klaimInapRepo = $klaimInapRepo;
        $this->lapAnastesiRepo = $lapAnastesiRepo;
    }

    public function get_all_jok($kode_reg)
    {
        $jok_arr = $this->klaimInapRepo->getAllJOK($kode_reg);
        return response()->json($jok_arr);
    }

    public function generatePdf($kode_reg)
    {
        $data['data_anastesi'] = $this->lapAnastesiRepo->getLaporanAnestesi($kode_reg);
        $data['ttd_parawat'] = $this->lapAnastesiRepo->getTTDPerawat($kode_reg, 'ANESTESI');
        $data['rs_pasien'] = $this->lapAnastesiRepo->getJadwalOK($kode_reg);
        $data['jenis_tindakan'] = $this->lapAnastesiRepo->getAnestesiSedasi($kode_reg);
        $data['ttd'] = $this->lapAnastesiRepo->getTTDAnestesi($kode_reg);
        $data['op'] = $this->lapAnastesiRepo->getTacRiOkByFjok($kode_reg);

        // return response()->json($data);


        $chart = [
            [
                'jam' => '2026-06-25 08:00:00',
                'o2' => 2,
                'n2o' => 0,
                'udara' => 3,
                'infus' => 500,
                'transfusi' => 0,
                'urine' => 50,
                'bleeding' => 10,
                'spo2' => 99,
                'etco2' => 34,
                'sistole' => 120,
                'diastole' => 80,
                'nadi' => 78,
                'respirasi' => 18,
            ],
            [
                'jam' => '2026-06-25 08:15:00',
                'o2' => 2,
                'n2o' => 0,
                'udara' => 3,
                'infus' => 500,
                'transfusi' => 0,
                'urine' => 75,
                'bleeding' => 15,
                'spo2' => 99,
                'etco2' => 35,
                'sistole' => 125,
                'diastole' => 82,
                'nadi' => 80,
                'respirasi' => 18,
            ],
            [
                'jam' => '2026-06-25 08:30:00',
                'o2' => 2,
                'n2o' => 0,
                'udara' => 3,
                'infus' => 750,
                'transfusi' => 0,
                'urine' => 100,
                'bleeding' => 20,
                'spo2' => 98,
                'etco2' => 35,
                'sistole' => 130,
                'diastole' => 85,
                'nadi' => 82,
                'respirasi' => 19,
            ],
            [
                'jam' => '2026-06-25 08:45:00',
                'o2' => 2,
                'n2o' => 0,
                'udara' => 3,
                'infus' => 1000,
                'transfusi' => 250,
                'urine' => 150,
                'bleeding' => 25,
                'spo2' => 99,
                'etco2' => 36,
                'sistole' => 128,
                'diastole' => 84,
                'nadi' => 79,
                'respirasi' => 18,
            ],
            [
                'jam' => '2026-06-25 09:00:00',
                'o2' => 2,
                'n2o' => 0,
                'udara' => 3,
                'infus' => 1250,
                'transfusi' => 250,
                'urine' => 200,
                'bleeding' => 30,
                'spo2' => 99,
                'etco2' => 35,
                'sistole' => 122,
                'diastole' => 81,
                'nadi' => 76,
                'respirasi' => 17,
            ],
        ];

        $data['chart'] = $chart;

        $pdf = Pdf::loadView(
            'klaim.inap.laporan_anastesi',
            (array)$data
        );

        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('Laporan_Anastesi.pdf');

        // kalau mau langsung download:
        // return $pdf->download('Laporan_Anastesi.pdf');
    }
}
