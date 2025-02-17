<?php

namespace App\Repositories\RM;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class BridgingEKlaimRepository
{
    /**
     * Get detail tarif transaksi based on kode_reg
     * 
     * @param string $kode_reg
     * @return array
     */
    public function getDetailTarifTransaksi($kode_reg)
    {
        $tarif = [
            'prosedur_non_bedah' => 0,
            'prosedur_bedah' => 0,
            'konsultasi' => 0,
            'tenaga_ahli' => 0,
            'keperawatan' => 0,
            'penunjang' => 0,
            'radiologi' => 0,
            'laboratorium' => 0,
            'pelayanan_darah' => 0,
            'rehabilitasi' => 0,
            'kamar' => 0,
            'rawat_intensif' => 0,
            'obat' => 0,
            'alkes' => 0,
            'bmhp' => 0,
            'sewa_alat' => 0,
        ];

        $transaksiPasien = DB::connection('sqlsrv')
            ->table('TRANSAKSIPASIEN AS a')
            ->join('TRANSAKSIPASIEND AS b', 'a.FTNO_TRANSAKSI', '=', 'b.FDTNO_TRANSAKSI')
            ->join('PRODUK AS p', 'p.FMPPRODUK_ID', '=', 'b.FDTKD_PRODUK')
            ->join('PRODUK_UNIT AS pu', 'p.FMPUNITPRODUK', '=', 'pu.FTUKODE')
            ->whereNull('b.FDTNO_FAKTUR')
            ->where('b.FDTJENISTRANSAKSI', 'DB')
            ->where('a.FTNO_TRANSAKSI', $kode_reg)
            ->select('a.FTNO_TRANSAKSI', 'b.FDTQTY', 'b.FDTHARGA', 'b.FDTKD_PRODUK', 'pu.FTUKD_EKLAIM')
            ->get();

        foreach ($transaksiPasien as $transaksi) {
            $total = $transaksi->FDTQTY * $transaksi->FDTHARGA;
            switch ($transaksi->FTUKD_EKLAIM) {
                case '1':
                    $tarif['prosedur_non_bedah'] += $total;
                    break;
                case '2':
                    $tarif['prosedur_bedah'] += $total;
                    break;
                case '3':
                    $tarif['konsultasi'] += $total;
                    break;
                case '4':
                    $tarif['tenaga_ahli'] += $total;
                    break;
                case '5':
                    $tarif['keperawatan'] += $total;
                    break;
                case '6':
                    $tarif['penunjang'] += $total;
                    break;
                case '7':
                    $tarif['radiologi'] += $total;
                    break;
                case '8':
                    $tarif['laboratorium'] += $total;
                    break;
                case '9':
                    $tarif['pelayanan_darah'] += $total;
                    break;
                case '10':
                    $tarif['rehabilitasi'] += $total;
                    break;
                case '11':
                    $tarif['kamar'] += $total;
                    break;
                case '12':
                    $tarif['rawat_intensif'] += $total;
                    break;
                case '13':
                    $tarif['obat'] += $total;
                    break;
                case '14':
                    $tarif['alkes'] += $total;
                    break;
                case '15':
                    $tarif['bmhp'] += $total;
                    break;
                case '16':
                    $tarif['sewa_alat'] += $total;
                    break;
                default:
                    $tarif['bmhp'] += $total;
                    break;
            }
        }

        $fjinkotaData = DB::connection('sqlsrv')
            ->table('FJINKOTA')
            ->where('FHFJNO_TRANSAKSI', '=', $kode_reg)
            ->where('FHFJKRONIS', '=', 0)
            ->select('FHFJBUKTI_ID', 'FHFJTOTAL')
            ->get();

        foreach ($fjinkotaData as $fjinkota) {
            $tarif['obat'] += (float) $fjinkota->FHFJTOTAL;
        }

        return $tarif;
    }

    /**
     * Get the klaim data dari nomer sep
     * 
     * @param string $no_sep
     * @return \Illuminate\Support\Collection
     */
    public function getKlaimData($no_sep)
    {
        $client = new Client();

        $url = env("EKLAIM_WS_URL", "");
        $request = json_encode((object)[
            'metadata' => (object)[
                'method' => 'get_claim_data'
            ],
            'data' => (object)[
                'nomor_sep' => $no_sep,
            ]
        ]);

        $key = "3286e120fea9b340164f0c76c50bbf0f529746666ce38e2d372dd2b4c5f0a946";
        $data = mc_encrypt($request, $key);

        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];

        $response = $client->post($url, [
            'headers' => $headers,
            'body'    => $data
        ]);

        $response = $response->getBody()->getContents();

        $first = strpos($response, "\n") + 1;
        $last = strrpos($response, "\n") - 1;
        $response = substr($response, $first, strlen($response) - $first - $last);
        $response = mc_decrypt($response, $key);

        return $response;
    }

    /**
     * Get the klaim data dari nomer sep
     * 
     * @param string $no_sep
     * @return \Illuminate\Support\Collection
     */
    public function bridgingDataProcess($kode_reg)
    {
        $client = new Client();

        $url = env("EKLAIM_WS_URL", "");
        $request = json_encode((object)[
            'metadata' => (object)[
                'method' => 'set_claim_data',
                'nomor_sep' => '0153R0030125V010002'
            ],
            'data' => (object)[
                'nomor_sep' => '0153R0030125V010002',
                'tgl_masuk' => '2025-01-22 00:00:00',
                'tgl_pulang' => '2025-01-22 00:00:00',
                'jenis_rawat' => '2',
                'kelas_rawat' => '3',
                'birth_weight' => '0',
                'discharge_status' => '1',
                'diagnosa' => 'Z50.1#M48.0',
                'diagnosa_inagrouper' => 'Z50.1#M48.0',
                'procedure' => '93.35#93.39#93.15',
                'procedure_inagrouper' => '93.35#93.39#93.15',
                'adl_sub_acute' => '',
                'adl_chronic' => '',
                'tarif_rs' => $this->getDetailTarifTransaksi($kode_reg),
                'tarif_poli_eks' => '179000',
                'nama_dokter' => 'DR. NINIK DWIASTUTI, SP. KFR',
                'icu_indikator' => '',
                'icu_los' => '',
                'ventilator_hour' => '',
                'kode_tarif' => 'CS',
                'payor_id' => '3',
                'payor_cd' => 'JKN',
                'coder_nik' => '123123123123',
                'sistole' => '',
                'diastole' => '',
                'cara_masuk' => 'outp'
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $key = "3286e120fea9b340164f0c76c50bbf0f529746666ce38e2d372dd2b4c5f0a946";
        $data = mc_encrypt($request, $key);

        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];

        $response = $client->post($url, [
            'headers' => $headers,
            'body'    => $data
        ]);

        $response = $response->getBody()->getContents();

        $first = strpos($response, "\n") + 1;
        $last = strrpos($response, "\n") - 1;
        $response = substr($response, $first, strlen($response) - $first - $last);
        $response = mc_decrypt($response, $key);

        return $response;
    }
}
