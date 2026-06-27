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
        putenv('LD_LIBRARY_PATH=');
        putenv('HOME=/tmp');

        return $shot
            ->setNodeBinary('/usr/bin/node')
            ->setNpmBinary('/usr/bin/npm')
            ->setChromePath('/usr/bin/google-chrome')
            ->noSandbox();
    }
}
