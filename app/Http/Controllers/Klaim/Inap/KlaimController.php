<?php

namespace App\Http\Controllers\Klaim\Inap;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Klaim\Inap\LaporanAnastesiController;
use App\Http\Controllers\Klaim\Inap\SEPController;
use App\Http\Controllers\RM\PasienInapController;
use App\Repositories\KlaimInap\KlaimInapRepository;
use App\Repositories\KlaimInap\PenunjangLainRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class KlaimController extends Controller
{
    protected $inapRepo;
    protected $penunjangRepo;

    public function __construct(KlaimInapRepository $inapRepo, PenunjangLainRepository $penunjangRepo)
    {
        $this->inapRepo = $inapRepo;
        $this->penunjangRepo = $penunjangRepo;
    }

    public function cetakAllNew($kode_reg, $nomer_rm, $no_sep)
    {
        return Inertia::render('Klaim/Inap/CetakAll', [
            'kode_reg' => $kode_reg,
            'nomer_rm' => $nomer_rm,
            'no_sep' => $no_sep,
        ]);
    }

    public function proxyPdf(Request $request)
    {
        $url = $request->query('url');

        if (!$url) {
            abort(400, 'URL wajib diisi');
        }

        $response = Http::timeout(60)
            ->withoutVerifying()
            ->get($url);

        if (!$response->successful()) {
            abort($response->status(), 'Gagal mengambil PDF');
        }

        return response(
            $response->body(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="document.pdf"',
            ]
        );
    }

    public function getKodeRegRJByInap($kode_reg_rbi)
    {
        $data = $this->inapRepo->getKodeRegRJByInap($kode_reg_rbi);
        return response()->json($data);
    }

    public function checkIsPersalinan($kode_reg_rbi)
    {
        $is_partus = $this->inapRepo->checkIsPersalinan($kode_reg_rbi);
        return response()->json((object)['is_partus' => $is_partus]);
    }

    public function cetakAll($kode_reg_rbi)
    {
        $tempDir = storage_path('app/tmp/cetak_all/' . $kode_reg_rbi);
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $files = [];
        $order = 0;

        // 1. SEP
        $sepContent = $this->getSepPdf($kode_reg_rbi);
        if ($sepContent) {
            $files[] = $this->saveTempFile($tempDir, sprintf('%02d-sep.pdf', ++$order), $sepContent);
        }

        // 2. Resume
        $resumeUrl = sprintf('http://10.10.10.10/emr/index.php/rm/rawat_inap_no_auth/cetak_rm/%s/2', rawurlencode($kode_reg_rbi));
        $resumeContent = $this->downloadRemotePdf($resumeUrl);
        if ($resumeContent) {
            $files[] = $this->saveTempFile($tempDir, sprintf('%02d-resume.pdf', ++$order), $resumeContent);
        }

        $kodeRegJalan = optional($this->inapRepo->getKodeRegRJByInap($kode_reg_rbi))->FDTNO_FAKTUR;

        // 3. SPRI
        if ($kodeRegJalan) {
            $spriUrl = sprintf('http://10.10.10.10/emr/index.php/igd/cetak_spri/pdf2/%s/%s', rawurlencode($kodeRegJalan), rawurlencode($this->getNomerRm($kode_reg_rbi)));
            $spriContent = $this->downloadRemotePdf($spriUrl);
            if ($spriContent) {
                $files[] = $this->saveTempFile($tempDir, sprintf('%02d-spri.pdf', ++$order), $spriContent);
            }

            // 4. Triase
            $triaseUrl = sprintf('http://10.10.10.10/emr/index.php/igd/cetak_triase/pdf/%s/%s', rawurlencode($kodeRegJalan), rawurlencode($this->getNomerRm($kode_reg_rbi)));
            $triaseContent = $this->downloadRemotePdf($triaseUrl);
            if ($triaseContent) {
                $files[] = $this->saveTempFile($tempDir, sprintf('%02d-triase.pdf', ++$order), $triaseContent);
            }
        }

        // 5. Laporan OK
        $jokArr = $this->inapRepo->getAllJOK($kode_reg_rbi);
        foreach ($jokArr as $jok) {
            if (empty($jok->FJOKNO_JADWAL)) {
                continue;
            }
            $laporanOkUrl = sprintf('http://10.10.10.10/emr/index.php/rm/rawat_inap_no_auth/cetak_laporan_operasi_fjok/%s', rawurlencode($jok->FJOKNO_JADWAL));
            $laporanOkContent = $this->downloadRemotePdf($laporanOkUrl);
            if ($laporanOkContent) {
                $files[] = $this->saveTempFile($tempDir, sprintf('%02d-laporan-ok-%s.pdf', ++$order, preg_replace('/[^A-Za-z0-9_-]/', '_', $jok->FJOKNO_JADWAL)), $laporanOkContent);
            }
        }

        // 6. Laporan Anastesi
        $anastesiContent = $this->getAnastesiPdf($kode_reg_rbi);
        if ($anastesiContent) {
            $files[] = $this->saveTempFile($tempDir, sprintf('%02d-anastesi.pdf', ++$order), $anastesiContent);
        }

        // 7. Laporan Persalinan
        if ($this->inapRepo->checkIsPersalinan($kode_reg_rbi)) {
            $vkUrl = sprintf('http://10.10.10.10/emr/index.php/rm/rawat_inap_no_auth/cetak_laporan_persalinan/%s', rawurlencode($kode_reg_rbi));
            $vkContent = $this->downloadRemotePdf($vkUrl);
            if ($vkContent) {
                $files[] = $this->saveTempFile($tempDir, sprintf('%02d-vk.pdf', ++$order), $vkContent);
            }
        }

        // 8. Lab Radiologi
        $nomerRm = $this->getNomerRm($kode_reg_rbi);
        if ($nomerRm) {
            $labUrl = sprintf('http://10.10.10.10/emr/index.php/penunjang/cetak_hasil_penunjang/pdf2/%s/%s', rawurlencode($kode_reg_rbi), rawurlencode($nomerRm));
            $labContent = $this->downloadRemotePdf($labUrl);
            if ($labContent) {
                $files[] = $this->saveTempFile($tempDir, sprintf('%02d-lab-radiologi.pdf', ++$order), $labContent);
            }
        }

        // 9. Penunjang Lain
        $penunjangDocs = $this->penunjangRepo->getByTransaksi($kode_reg_rbi);
        foreach ($penunjangDocs as $doc) {
            if (!$doc || !isset($doc->FILE_NAME)) {
                continue;
            }
            $path = Storage::disk('local')->path($doc->FILE_NAME);
            if (!Storage::disk('local')->exists($doc->FILE_NAME)) {
                continue;
            }
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $targetFile = $tempDir . DIRECTORY_SEPARATOR . sprintf('%02d-penunjang-%s.%s', ++$order, preg_replace('/[^A-Za-z0-9_-]/', '_', basename($doc->FILE_NAME)), $extension === 'pdf' ? 'pdf' : 'pdf');
            if ($extension === 'pdf') {
                copy($path, $targetFile);
                $files[] = $targetFile;
                continue;
            }
            $imagePdf = $this->convertImageToPdf($path, $targetFile);
            if ($imagePdf) {
                $files[] = $imagePdf;
            }
        }

        // 10. Kwitansi
        $kwitansiUrl = sprintf('http://10.10.10.10/emr/index.php/vedika/cetak_billing_ri?faktur_id=%s', rawurlencode($kode_reg_rbi));
        $kwitansiContent = $this->downloadRemotePdf($kwitansiUrl);
        if ($kwitansiContent) {
            $files[] = $this->saveTempFile($tempDir, sprintf('%02d-kwitansi.pdf', ++$order), $kwitansiContent);
        }

        // 11. E-Klaim
        $sepData = DB::connection('sqlsrvsimrs')
            ->table('BPJS_SEP')
            ->select('FMNOSEP')
            ->where('FMNOTRANSAKSI', $kode_reg_rbi)
            ->first();
        if ($sepData && $sepData->FMNOSEP) {
            $eklaimContent = $this->getEklaimPdf($sepData->FMNOSEP);
            if ($eklaimContent) {
                $files[] = $this->saveTempFile($tempDir, sprintf('%02d-eklaim.pdf', ++$order), $eklaimContent);
            }
        }

        if (empty($files)) {
            abort(404, 'Tidak ada dokumen klaim yang bisa dicetak');
        }

        $outFile = $tempDir . DIRECTORY_SEPARATOR . 'klaim-all-' . $kode_reg_rbi . '.pdf';
        if (!$this->mergePdfs($files, $outFile)) {
            abort(500, 'Gagal menggabungkan dokumen PDF');
        }

        return Response::file($outFile, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="klaim_all_' . $kode_reg_rbi . '.pdf"',
        ]);
    }

    protected function getSepPdf($kode_reg)
    {
        try {
            $controller = app(SEPController::class);
            $response = $controller->index($kode_reg);
            return $response->getContent();
        } catch (\Throwable $e) {
            Log::warning('SEP PDF gagal diambil: ' . $e->getMessage());
            return null;
        }
    }

    protected function getAnastesiPdf($kode_reg)
    {
        try {
            $controller = app(LaporanAnastesiController::class);
            $response = $controller->snapshot($kode_reg);
            return $response->getContent();
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            // No JOK / no anesthesia report is an expected case for non-operative inpatient stays.
            return null;
        } catch (\Throwable $e) {
            Log::warning('Laporan anestesi gagal diambil: ' . $e->getMessage());
            return null;
        }
    }

    protected function getEklaimPdf($noSep)
    {
        try {
            $controller = app(PasienInapController::class);
            $response = $controller->bridging_cetak_klaim($noSep);
            return $response->getContent();
        } catch (\Throwable $e) {
            Log::warning('E-Klaim PDF gagal diambil: ' . $e->getMessage());
            return null;
        }
    }

    protected function downloadRemotePdf($url)
    {
        try {
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                $content = curl_exec($ch);
                $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                curl_close($ch);
            } else {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 60,
                        'header' => 'User-Agent: Mozilla/5.0\r\n',
                    ],
                ]);
                $content = @file_get_contents($url, false, $context);
                $contentType = null;
                if (!empty($http_response_header)) {
                    foreach ($http_response_header as $header) {
                        if (stripos($header, 'Content-Type:') === 0) {
                            $contentType = trim(substr($header, strlen('Content-Type:')));
                            break;
                        }
                    }
                }
            }

            if (!$content || !is_string($content)) {
                return null;
            }

            if (str_contains($content, '%PDF-') || str_contains((string)$contentType, 'pdf')) {
                return $content;
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning('Download PDF gagal: ' . $e->getMessage() . ' - ' . $url);
            return null;
        }
    }

    protected function saveTempFile($dir, $filename, $content)
    {
        $path = $dir . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($path, $content);
        return $path;
    }

    protected function getNomerRm($kode_reg_rbi)
    {
        try {
            $row = DB::connection('sqlsrvsimrs')
                ->table('TRANSAKSIPASIENINAP AS TPI')
                ->leftJoin('PASIENRAWATINAP AS PRI', function ($join) {
                    $join->on('TPI.FTNO_TRANSAKSI', '=', 'PRI.PRWINO_TRANSAKSI')
                        ->on('TPI.FTNO_URUT', '=', 'PRI.PRWINO_URUT');
                })
                ->select('PRI.PRWIKD_PASIEN')
                ->where('TPI.FTNO_TRANSAKSI', $kode_reg_rbi)
                ->first();

            return $row?->PRWIKD_PASIEN;
        } catch (\Throwable $e) {
            Log::warning('Nomor RM gagal diambil: ' . $e->getMessage());
            return null;
        }
    }

    protected function convertImageToPdf($imagePath, $targetPdf)
    {
        try {
            if (!file_exists($imagePath)) {
                return null;
            }

            $imageData = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath) ?: 'image/png';
            $html = '<html><body style="margin:0;padding:0;">'
                . '<img src="data:' . $mimeType . ';base64,' . $imageData . '" style="width:100%; height:auto;" />'
                . '</body></html>';

            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('a4', 'portrait');
            file_put_contents($targetPdf, $pdf->output());
            return $targetPdf;
        } catch (\Throwable $e) {
            Log::warning('Konversi gambar ke PDF gagal: ' . $e->getMessage());
            return null;
        }
    }

    protected function mergePdfs(array $files, $outFile)
    {
        $pdfcpu = $this->findPdfcpuBinary();
        if (!$pdfcpu) {
            Log::error('pdfcpu tidak ditemukan untuk menggabungkan PDF');
            return false;
        }

        try {
            $process = Process::path(dirname($pdfcpu))
                ->timeout(120)
                ->run([$pdfcpu, 'merge', '-bookmarks=false', $outFile, ...$files]);

            if (!$process->successful()) {
                Log::error('pdfcpu merge gagal: ' . $process->errorOutput());
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('pdfcpu merge exception: ' . $e->getMessage());
            return false;
        }
    }

    protected function findPdfcpuBinary()
    {
        $candidates = [
            'pdfcpu',
            'pdfcpu.exe',
            'C:\\Users\\PERSONAL\\go\\bin\\pdfcpu.exe',
        ];

        foreach ($candidates as $candidate) {
            try {
                $process = Process::path(dirname($candidate))
                    ->timeout(10)
                    ->run([$candidate, 'version']);
                if ($process->successful()) {
                    return $candidate;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        try {
            $process = Process::timeout(10)
                ->run(['where', 'pdfcpu']);
            if ($process->successful()) {
                $path = trim($process->output());
                if (!empty($path)) {
                    return strtok($path, "\r\n");
                }
            }
        } catch (\Throwable $e) {
            // Ignore; if where.exe fails, fallback to null.
        }

        return null;
    }
}
