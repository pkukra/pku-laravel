<?php

namespace App\Http\Controllers\Klaim\Inap;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Repositories\KlaimInap\KlaimInapRepository;
use App\Repositories\KlaimInap\LaporanAnastesiRepository;
use Spatie\Browsershot\Browsershot;

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

    public function snapshot2($kode_reg)
    {
        $jok_arr = $this->klaimInapRepo->getAllJOK($kode_reg);
        if (!$jok_arr) {
            return "kosong";
        }
        $file = storage_path('app/anestesi.png');

        $shot = \Spatie\Browsershot\Browsershot::url(
            'http://10.10.10.10/emr/index.php/ok/ok_no_auth/cetak_laporan_anestesi/' . $jok_arr[0]->FJOKNO_JADWAL
        )
            ->fullPage()
            ->setDelay(5000);

        $shot = $this->configureBrowsershotBinary($shot);
        $shot->save($file);

        return response()->file($file);
    }

    public function snapshot($kode_reg)
    {
        $jok_arr = $this->klaimInapRepo->getAllJOK($kode_reg);

        if (empty($jok_arr)) {
            abort(404, 'Data JOK tidak ditemukan');
        }

        $folder = storage_path('app/public/anestesi');

        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }

        $images = [];

        foreach ($jok_arr as $jok) {

            $fjok = $jok->FJOKNO_JADWAL;

            $file = $folder . DIRECTORY_SEPARATOR . $fjok . '.png';

            if (!file_exists($file)) {
            $shot = Browsershot::url(
                'http://10.10.10.10/emr/index.php/ok/ok_no_auth/cetak_laporan_anestesi/' . $fjok
            )
                ->fullPage()
                ->setDelay(5000);

            $shot = $this->configureBrowsershotBinary($shot);
            $shot->save($file);
        }

            $images[] = [
                'fjok' => $fjok,
            ];
        }

        $pdf = Pdf::loadView('klaim.inap.anestesi-snapshot', [
            'kode_reg' => $kode_reg,
            'images'   => $images,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("anestesi-{$kode_reg}.pdf");
    }

    protected function configureBrowsershotBinary($shot)
    {
        $linuxNode = trim(shell_exec('which node 2>/dev/null')) ?: null;
        $linuxNpm = trim(shell_exec('which npm 2>/dev/null')) ?: null;

        if ($linuxNode && $linuxNpm) {
            return $shot->setNodeBinary($linuxNode)->setNpmBinary($linuxNpm);
        }

        if (str_starts_with(PHP_OS_FAMILY, 'Windows')) {
            return $shot
                ->setNodeBinary('C:\\Program Files\\nodejs\\node.exe')
                ->setNpmBinary('C:\\Program Files\\nodejs\\npm.cmd');
        }

        return $shot;
    }
}
