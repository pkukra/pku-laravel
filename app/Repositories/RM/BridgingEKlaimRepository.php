<?php

namespace App\Repositories\RM;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class BridgingEKlaimRepository
{
    /**
     * Process new claim by nomor kartu
     *
     * @param string $no_peserta
     * @param string $no_sep
     * @param string $norm
     * @param string $nm_pasien
     * @param string $tgl_lahir
     * @param string $jns_kelamin
     */
    public function bridgingNewClaimProcess($nomor_kartu, $no_sep, $norm, $nm_pasien, $tgl_lahir, $jns_kelamin)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;

        // Format tanggal lahir
        $formattedDate = date("Y/m/d H:i:s", strtotime($tgl_lahir));

        // Data request
        $request = json_encode([
            "metadata" => ["method" => "new_claim"],
            "data" => [
                "nomor_kartu" => $nomor_kartu,
                "nomor_sep" => $no_sep,
                "nomor_rm" => $norm,
                "nama_pasien" => $nm_pasien,
                "tgl_lahir" => $formattedDate,
                "gender" => $jns_kelamin
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        // Enkripsi data sebelum dikirim
        $encryptedData = mc_encrypt($request, $key);

        $client = new Client();
        $url = env("EKLAIM_WS_URL");

        try {
            $response = $client->post($url, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json'
                ],
                'body' => $encryptedData
            ]);

            $responseBody = $response->getBody()->getContents();

            // Membersihkan response dari karakter tak diinginkan
            $first = strpos($responseBody, "\n") + 1;
            $last = strrpos($responseBody, "\n") - 1;
            $responseBody = substr($responseBody, $first, strlen($responseBody) - $first - $last);

            // Dekripsi response
            $decryptedResponse = mc_decrypt($responseBody, $key);

            return (object)[
                "status" => "ok",
                "error" => null,
                "response" => json_decode($decryptedResponse)
            ];
        } catch (RequestException $e) {
            $error = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            return (object)[
                "status" => "nok",
                "error" => $error
            ];
        } catch (\Throwable $th) {
            return (object)[
                "status" => "nok",
                "error" => $th->getMessage()
            ];
        }
    }

    /**
     * Process bridgingDataProcess by no_sep
     * 
     * @param string $no_sep
     */
    public function bridgingDataProcess($no_sep)
    {
        $semua_transaksi = $this->allTransactionsBySep($no_sep);
        if (count($semua_transaksi) < 1) {
            return false;
        }

        // menentukan dokter mana yang menjadi dpjp utama
        // jika array hanya 1, maka otomatis index 0 menjadi dpjp uatama
        // jika array lebih dari 1 maka dipilih yang RUBBER adalah false(0) yang menjadi dpjp utama
        // berarti yang bukan dokter RaBer (Rawat Bersama)
        $transaksi_uatama = $semua_transaksi[0];
        foreach ($semua_transaksi as $transaksi) {
            if ($transaksi->RUBBER == 0) {
                $transaksi_uatama = $transaksi;
                break;
            }
        }

        // buat new claim dulu
        $this->bridgingNewClaimProcess(
            $transaksi_uatama->FMNO_KARTU,
            $transaksi_uatama->FMNOSEP,
            $transaksi_uatama->FRPPASIEN_ID,
            $transaksi_uatama->NAMAPASIEN,
            $transaksi_uatama->TGL_LAHIR,
            $transaksi_uatama->JENIS_KELAMIN,
        );

        $user = Auth::user();
        $bloodPresure = $this->getBloodPressure($transaksi_uatama->FRPNOTRANSAKSI);

        // mapping data
        $data = (object)[
            'nomor_sep' => $no_sep,
            'tgl_masuk' => Carbon::parse($transaksi_uatama->FRPTGL)->format('Y-m-d H:i:s'),
            'tgl_pulang' => Carbon::parse($transaksi_uatama->FRPTGL)->format('Y-m-d H:i:s'),
            'jenis_rawat' => 2, // 1 ranap, 2 rajal, 3 igd
            'kelas_rawat' => 3, // kelas rawat BPJS 1,2,3
            'birth_weight' => 0,
            'discharge_status' => 1,
            'tarif_rs' => $this->getTotalDetailTarifTransaksi($semua_transaksi)->tarif_rs,
            'tarif_poli_eks' => $this->getTotalDetailTarifTransaksi($semua_transaksi)->tarif_poli_eks,
            'diagnosa' => $this->getAllDiagnosa($semua_transaksi),
            'diagnosa_inagrouper' => $this->getAllDiagnosa($semua_transaksi),
            'procedure' => $this->getAllProcedure($semua_transaksi),
            'procedure_inagrouper' => $this->getAllProcedure($semua_transaksi),
            'adl_sub_acute' => "",
            'adl_chronic' => "",
            'nama_dokter' => $transaksi_uatama->FMDDOKTERN,
            'icu_indikator' => "",
            'icu_los' => "",
            'ventilator_hour' => "",
            'kode_tarif' => "CS",
            'payor_id' => "3",
            'payor_cd' => "JKN",
            'coder_nik' => $user->nik,
            'sistole' => $bloodPresure->sistole,
            'diastole' => $bloodPresure->diastole,
            'cara_masuk' => $transaksi_uatama->CARA_MASUK,
        ];

        $request = json_encode((object)[
            'metadata' => (object)[
                'method' => 'set_claim_data',
                'nomor_sep' => $no_sep,
            ],
            'data' => $data
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $key = $user->eklaim_key;

        $data = mc_encrypt($request, $key);

        $client = new Client();
        $url = env("EKLAIM_WS_URL");

        try {
            $response = $client->post($url, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'body'    => $data
            ]);
        } catch (RequestException $e) {
            $err = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            return (object)[
                "status" => "nok",
                "error" => $err
            ];
        } catch (\Throwable $th) {
            $err = $th->getMessage();
            return (object)[
                "status" => "nok",
                "error" => $err
            ];
        }

        $response = $response->getBody()->getContents();

        $first = strpos($response, "\n") + 1;
        $last = strrpos($response, "\n") - 1;
        $response = substr($response, $first, strlen($response) - $first - $last);
        $response = mc_decrypt($response, $key);

        return (object)[
            "status" => "ok",
            "error" => null,
            "response" => json_decode($response)
        ];
    }

    /**
     * Process bridgingFinalProcess by no_sep
     * 
     * @param string $no_sep
     */
    public function bridgingFinalProcess($no_sep)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;

        // Data request
        $request = json_encode([
            "metadata" => ["method" => "claim_final"],
            "data" => ["nomor_sep" => $no_sep, "coder_nik" => $user->nik]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        // Enkripsi data sebelum dikirim
        $encryptedData = mc_encrypt($request, $key);

        $client = new Client();
        $url = env("EKLAIM_WS_URL");

        try {
            $response = $client->post($url, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json'
                ],
                'body' => $encryptedData
            ]);

            $responseBody = $response->getBody()->getContents();

            // Membersihkan response dari karakter tak diinginkan
            $first = strpos($responseBody, "\n") + 1;
            $last = strrpos($responseBody, "\n") - 1;
            $responseBody = substr($responseBody, $first, strlen($responseBody) - $first - $last);

            // Dekripsi response
            $decryptedResponse = mc_decrypt($responseBody, $key);

            return (object)[
                "status" => "ok",
                "error" => null,
                "response" => json_decode($decryptedResponse)
            ];
        } catch (RequestException $e) {
            $error = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            return (object)[
                "status" => "nok",
                "error" => $error
            ];
        } catch (\Throwable $th) {
            return (object)[
                "status" => "nok",
                "error" => $th->getMessage()
            ];
        }
    }

    /**
     * menampilkan list transaksi berdasar nomer SEP
     * termasuk jika SEP pasien dengan kunjungan raber
     * 
     * @param string $no_sep
     * @return array
     */
    public function allTransactionsBySep($no_sep)
    {
        try {
            $detailTransaksi = DB::connection('sqlsrv')
                ->table('BPJS_SEP AS sep')
                ->leftJoin('PASIEN_RUJUKAN AS pr', function ($join) use ($no_sep) {
                    $join->on('pr.FRPNOTRANSAKSI', '=', 'sep.FMNOTRANSAKSI')
                        ->orOn('pr.FRPNOTRANSAKSIKJ', '=', 'sep.FMNOTRANSAKSI');
                })
                ->leftJoin('DOKTER AS dr', 'pr.FRPDOKTER_ID', '=', 'dr.FMDDOKTER_ID')
                ->leftJoin('POLIKLINIK AS poli', 'pr.FRPUNIT', '=', 'poli.FMPKLINIK_ID')
                ->leftJoin('PASIEN AS p', 'pr.FRPPASIEN_ID', '=', 'p.KD_PASIEN')
                ->select('sep.FMNOSEP', 'sep.FMNO_KARTU', 'pr.*', 'dr.FMDDOKTERN', 'poli.FMPKLINIKN', 'p.NAMAPASIEN', 'p.TGL_LAHIR', 'p.JENIS_KELAMIN')
                ->where('sep.FMNOSEP', $no_sep)
                ->distinct()
                ->get();
        } catch (\Exception $e) {
            // Log the error if any exception occurs
            Log::error('Error get data allTransactionsBySep: ' . $e->getMessage());
            return false;
        }
        return $detailTransaksi;
    }

    /**
     * Get total detail tarif transaksi based on array of pasien rujukan->kode_reg
     * 
     * @param array [$pasien_rujukan,$pasien_rujukan,$pasien_rujukan, ...]
     * @return object
     */
    public function getTotalDetailTarifTransaksi($array_pasien_rujukan)
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

        $tarif_poli_eks = 0;

        foreach ($array_pasien_rujukan as $pasien_rujukan) {
            // mencari list semua transaksi selain kredit
            // ditandai dengan TRANSAKSIPASIEND.FDTJENISTRANSAKSI="DB"
            $transaksiPasien = DB::connection('sqlsrv')
                ->table('TRANSAKSIPASIEN AS a')
                ->leftJoin('TRANSAKSIPASIEND AS b', 'a.FTNO_TRANSAKSI', '=', 'b.FDTNO_TRANSAKSI')
                ->leftJoin('PRODUK AS p', 'p.FMPPRODUK_ID', '=', 'b.FDTKD_PRODUK')
                ->leftJoin('PRODUK_UNIT AS pu', 'p.FMPUNITPRODUK', '=', 'pu.FTUKODE')
                ->whereNull('b.FDTNO_FAKTUR')
                ->where('b.FDTJENISTRANSAKSI', 'DB') // ditandai dengan TRANSAKSIPASIEND.FDTJENISTRANSAKSI="DB"
                ->where('a.FTNO_TRANSAKSI', $pasien_rujukan->FRPNOTRANSAKSIKJ)
                ->select('a.FTNO_TRANSAKSI', 'b.FDTQTY', 'b.FDTHARGA', 'b.FDTKD_PRODUK', 'pu.FTUKD_EKLAIM')
                ->get();

            $tarif_poli_eks += $transaksiPasien->reduce(function ($carry, $transaksi) {
                return $carry + ($transaksi->FDTQTY * $transaksi->FDTHARGA);
            }, 0);

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
                ->where('FHFJNO_TRANSAKSI', '=', $pasien_rujukan->FRPNOTRANSAKSIKJ)
                ->where('FHFJKRONIS', '=', 0)
                ->select('FHFJBUKTI_ID', 'FHFJTOTAL')
                ->get();

            foreach ($fjinkotaData as $fjinkota) {
                $tarif['obat'] += (float)$fjinkota->FHFJTOTAL;
                $tarif_poli_eks += (float)$tarif['obat'];
            }
        }

        return (object)[
            "tarif_rs" => $tarif,
            "tarif_poli_eks" => $tarif_poli_eks,
        ];
    }

    /**
     * Get all diagnosa from all pasien_rujukan based on array of pasien rujukan->kode_reg (by no SEP)
     * 
     * @param array $array_pasien_rujukan
     * @return string Diagnosa dalam format "S71.0#A00.1"
     */
    public function getAllDiagnosa($array_pasien_rujukan)
    {
        $diagnoses_array = [];
        foreach ($array_pasien_rujukan as $pasien_rujukan) {
            $diagnosa = DB::connection('sqlsrv')
                ->table('MR_PENYAKIT')
                ->where('MRPNO_TRANSAKSI', '=', $pasien_rujukan->FRPNOTRANSAKSIKJ)
                ->pluck('MRPKD_PENYAKIT') // Mengambil hanya kolom MRPKD_PENYAKIT sebagai array
                ->toArray(); // Konversi ke array PHP

            $diagnoses_array = array_merge($diagnoses_array, $diagnosa); // Gabungkan hasil query ke array utama
        }

        return implode('#', array_unique($diagnoses_array)); // Gabungkan dengan pemisah "#" dan hilangkan duplikat
    }

    /**
     * Get all tindakan/procedures from all pasien_rujukan based on array of pasien rujukan->kode_reg (by no SEP)
     * 
     * @param array $array_pasien_rujukan
     * @return string Prosedur dalam format "00123#00456"
     */
    public function getAllProcedure($array_pasien_rujukan)
    {
        $tindakan_array = [];
        foreach ($array_pasien_rujukan as $pasien_rujukan) {
            $tindakan = DB::connection('sqlsrv')
                ->table('MR_TINDAKAN')
                ->where('MRTNOTRANSAKSI', '=', $pasien_rujukan->FRPNOTRANSAKSIKJ)
                ->pluck('MRTKD_TINDAKAN') // Mengambil hanya kolom MRTKD_TINDAKAN sebagai array
                ->toArray(); // Konversi ke array PHP

            $tindakan_array = array_merge($tindakan_array, $tindakan); // Gabungkan hasil query ke array utama
        }
        return implode('#', array_unique($tindakan_array)); // Gabungkan dengan pemisah "#" dan hilangkan duplikat
    }

    /**
     * Get sistole and diastole based on kode_reg
     * 
     * @param string $kode_reg
     * @return object Sistole dan diastole dalam format (object)['sistole' => value, 'diastole' => value]
     */
    public function getBloodPressure($kode_reg)
    {
        $vitalSign = DB::connection('sqlsrv')
            ->table('PKU.dbo.TAC_RJ_VITAL_SIGN')
            ->select('FS_TD as sistole', 'FS_TD2 as diastole')
            ->where('FS_KD_REG', $kode_reg)
            ->orderByDesc('FS_KD_REG') // TOP 1 digantikan dengan orderBy + first()
            ->first();

        return (object)[
            'sistole' => $vitalSign->sistole ?? 0, // Default 0 jika tidak ada data
            'diastole' => $vitalSign->diastole ?? 0 // Default 0 jika tidak ada data
        ];
    }
}
