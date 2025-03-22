<?php

namespace App\Repositories\RM;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Bpjs\Bridging\Vclaim\BridgeVclaim;

class PasienInapEklaimRepository
{
    /**
     * Process new claim by nomor kartu
     *
     * @param string $nomor_kartu
     * @param string $no_sep
     * @param string $nomor_rm
     * @param string $nama_pasien
     * @param string $tgl_lahir
     * @param string $jns_kelamin
     */
    public function bridgingNewClaimProcess($nomor_kartu, $no_sep, $nomor_rm, $nama_pasien, $tgl_lahir, $jns_kelamin)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;

        // Format tanggal lahir
        $formattedBirthDate = date("Y-m-d H:i:s", strtotime($tgl_lahir));

        // Data request
        $data = json_encode([
            "metadata" => ["method" => "new_claim"],
            "data" => [
                "nomor_kartu" => $nomor_kartu,
                "nomor_sep" => $no_sep,
                "nomor_rm" => $nomor_rm,
                "nama_pasien" => $nama_pasien,
                "tgl_lahir" => $formattedBirthDate,
                "gender" => $jns_kelamin
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return sendRequest($key, $data);
    }

    /**
     * Process grouper stage 1 by nomor SEP
     *
     * @param string $no_sep
     * @return object
     */
    public function bridgingGrouperStage1($no_sep)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;

        // Data request
        $data = json_encode([
            "metadata" => [
                "method" => "grouper",
                "stage" => "1"
            ],
            "data" => [
                "nomor_sep" => $no_sep
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return sendRequest($key, $data);
    }


    /**
     * Process bridgingDataProcess by no_sep
     * 
     * @param string $no_sep
     */
    public function bridgingDataProcess($no_sep)
    {

        try {
            $bridging = new BridgeVclaim();
            $endpoint = 'SEP/' . $no_sep;
            $vclaim_detail = json_decode($bridging->getRequest($endpoint));
        } catch (\Exception $e) {
            Log::error("Vclaim Err get SEP: " . $e->getMessage());
            return (object)[
                "status" => "nok",
                "error" => "Gagal terhubung ke vclaim, coba beberapa saat lagi."
            ];
        }

        $transaksi_utama = $this->getDetailTransactionBySep($no_sep);
        if (!$transaksi_utama) {
            return (object)[
                "status" => "nok",
                "error" => "Transaction not found"
            ];
        }

        // update/reedit claim
        $this->bridgingReEditClaim($no_sep);

        // buat new claim dulu
        $this->bridgingNewClaimProcess(
            $transaksi_utama->FMNO_KARTU,
            $transaksi_utama->FMNOSEP,
            $transaksi_utama->KD_PASIEN,
            $transaksi_utama->NAMAPASIEN,
            $transaksi_utama->TGL_LAHIR,
            $transaksi_utama->JENIS_KELAMIN,
        );

        // update patient
        $this->bridgingUpdatePatien(
            $transaksi_utama->KD_PASIEN,
            $transaksi_utama->FMNO_KARTU,
            $transaksi_utama->NAMAPASIEN,
            $transaksi_utama->TGL_LAHIR,
            $transaksi_utama->JENIS_KELAMIN,
        );

        $user = Auth::user();
        $bloodPresure = $this->getBloodPressure($transaksi_utama->PRWINO_TRANSAKSI);
        // defaultnya atas persetujuan dokter
        $discharge_status =  1;
        if ($transaksi_utama->DISCHARGE_STATUS) {
            // jika berhasil makan dilakukan join dengan tabel mr_kematian untuk hasil yang lain
            $discharge_status =  $transaksi_utama->DISCHARGE_STATUS;
        }

        switch ($vclaim_detail->response->klsRawat) {
            case "1":
                $naik_kelas = "vvip";
                break;
            case "2":
                $naik_kelas = "vip";
                break;
            case "3":
                $naik_kelas = "kelas_1";
                break;
            case "4":
                $naik_kelas = "kelas_2";
                break;
            default:
                $naik_kelas = null;
        }

        $tgl_masuk = Carbon::parse($transaksi_utama->TGL_MASUK);
        $tgl_pulang = $transaksi_utama->PRWITGL_KELUAR
            ? Carbon::parse($transaksi_utama->PRWITGL_KELUAR)
            : now(); // Jika belum pulang, pakai waktu sekarang
        $los = $tgl_masuk->diffInDays($tgl_pulang) ?: 1; // Jika hasilnya 0, set minimal 1 hari

        // mapping data
        $data = (object)[
            'nomor_sep' => $no_sep,
            'tgl_masuk' => $tgl_masuk->format('Y-m-d H:i:s'),
            'tgl_pulang' => $tgl_pulang->format('Y-m-d H:i:s'),
            'jenis_rawat' => $transaksi_utama->FMJENISRAWAT, // 1 ranap, 2 rajal, 3 igd
            'kelas_rawat' => $vclaim_detail->response->klsRawat->klsRawatHak, // kelas rawat BPJS 1,2,3. Tapi ini ambil dari vclaim sekalian saja agar akurat

            "upgrade_class_ind" => ($vclaim_detail->response->klsRawat->klsRawatNaik) ? 1 : 0,
            "upgrade_class_class" => $naik_kelas,
            "upgrade_class_los" =>  $los,

            'birth_weight' => 0,
            'discharge_status' => $discharge_status,
            'tarif_rs' => $this->getTotalDetailTarifTransaksi($transaksi_utama)->tarif_rs,
            'tarif_poli_eks' => $this->getTotalDetailTarifTransaksi($transaksi_utama)->tarif_poli_eks,
            'diagnosa' => $this->getAllDiagnosa($transaksi_utama),
            'diagnosa_inagrouper' => $this->getAllDiagnosa($transaksi_utama),
            'procedure' => $this->getAllProcedure($transaksi_utama),
            'procedure_inagrouper' => $this->getAllProcedure($transaksi_utama),
            'adl_sub_acute' => "",
            'adl_chronic' => "",
            'nama_dokter' => $transaksi_utama->FMDDOKTERN,
            'icu_indikator' => "",
            'icu_los' => "",
            'ventilator_hour' => "",
            'kode_tarif' => "CS",
            'payor_id' => "3",
            'payor_cd' => "JKN",
            'coder_nik' => $user->nik,
            'sistole' => $bloodPresure->sistole ?? 0,
            'diastole' => $bloodPresure->diastole ?? 0,
            'cara_masuk' => $transaksi_utama->CARA_MASUK,
        ];

        $requestData = json_encode((object)[
            'metadata' => (object)[
                'method' => 'set_claim_data',
                'nomor_sep' => $no_sep,
            ],
            'data' => $data
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $key = $user->eklaim_key;
        $response =  sendRequest($key, $requestData);

        $grouper = $this->bridgingGroupStage1Process($no_sep);
        $cbg_code = $grouper->response->response->cbg->code ?? null;
        $tarif_inacbg = $grouper->response->response->cbg->tariff ?? 0;
        $tarif_inacbg_1 = 0;
        $tarif_inacbg_2 = 0;
        $tarif_inacbg_3 = 0;
        // mapping tari response dari eklaim
        if (!empty($grouper->response->tarif_alt)) {
            foreach ($grouper->response->tarif_alt as $tarif) {
                switch ($tarif->kelas) {
                    case 'kelas_1':
                        $tarif_inacbg_1 = (float)$tarif->tarif_inacbg;
                        break;
                    case 'kelas_2':
                        $tarif_inacbg_2 = (float)$tarif->tarif_inacbg;
                        break;
                    case 'kelas_3':
                        $tarif_inacbg_3 = (float)$tarif->tarif_inacbg;
                        break;
                }
            }
        }

        $special_cmg = implode('#', array_column($grouper->response->special_cmg_option ?? [], 'code'));
        if (!empty($specialCmg)) {
            // jika mempunyai specialCmg maka dilakukan grouping stage 2
            $grouper_statge_2 = $this->bridgingGroupStage2Process($no_sep, $special_cmg);
            $cbg_code = $grouper_statge_2->response->response->cbg->code ?? null;
            $tarif_inacbg = $grouper_statge_2->response->response->cbg->tariff ?? 0;

            // mapping tari response dari eklaim
            if (!empty($grouper_statge_2->response->tarif_alt)) {
                foreach ($grouper_statge_2->response->tarif_alt as $tarif) {
                    switch ($tarif->kelas) {
                        case 'kelas_1':
                            $tarif_inacbg_1 = (float)$tarif->tarif_inacbg;
                            break;
                        case 'kelas_2':
                            $tarif_inacbg_2 = (float)$tarif->tarif_inacbg;
                            break;
                        case 'kelas_3':
                            $tarif_inacbg_3 = (float)$tarif->tarif_inacbg;
                            break;
                    }
                }
            }
        }

        DB::connection('sqlsrv')
            ->table('TRANSAKSIPASIENINAP')
            ->where('FTNO_TRANSAKSI', $transaksi_utama->PRWINO_TRANSAKSI)
            ->update([
                'FTKODEINACBG' => $cbg_code,
                'FTTARIPINACBG' => $tarif_inacbg,
                'FTTARIPINACBG1' => $tarif_inacbg_1,
                'FTTARIPINACBG2' => $tarif_inacbg_2,
                'FTTARIPINACBG3' => $tarif_inacbg_3,
                'FKUNCI_VALIDASI2' => DB::raw('FKUNCI_VALIDASI2 + 1') // Incremen FKUNCI_VALIDASI2
            ]);

        return $response;
    }

    /**
     * Process bridgingGroupStage1Process by no_sep
     * 
     * @param string $no_sep
     */
    public function bridgingGroupStage1Process($no_sep)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;

        // Data request
        $data = json_encode([
            "metadata" => [
                "method" => "grouper",
                "stage" => "1",
            ],
            "data" => [
                "nomor_sep" => $no_sep,
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return sendRequest($key, $data);
    }

    /**
     * Process bridgingGroupStage2Process by no_sep
     * 
     * @param string $no_sep, $special_cmg
     */
    public function bridgingGroupStage2Process($no_sep, $special_cmg)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;

        // Data request
        $data = json_encode([
            "metadata" => [
                "method" => "grouper",
                "stage" => "2",
            ],
            "data" => [
                "nomor_sep" => $no_sep,
                "special_cmg" => $special_cmg
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return sendRequest($key, $data);
    }

    /**
     * Process bridgingFinalProcess by no_sep
     * 
     * @param string $no_sep
     */
    public function bridgingFinalProcess($kode_reg, $no_sep)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;

        // Data request
        $data = json_encode([
            "metadata" => ["method" => "claim_final"],
            "data" => ["nomor_sep" => $no_sep, "coder_nik" => $user->nik]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $response = sendRequest($key, $data);
        if ($response->response->metadata->code == 200) {
            try {
                DB::connection('sqlsrv')
                    ->table('TRANSAKSIPASIENINAP')
                    ->where('FTNO_TRANSAKSI', $kode_reg)
                    ->update([
                        'FKUNCI_VALIDASI' => 1,
                    ]);
            } catch (\Exception $e) {
                Log::error('Final process TRANSAKSIPASIENINAP FKUNCI_VALIDASI err: ' . $e->getMessage());
            }
        }
        return $response;
    }

    /**
     * Process bridgingUpdatePatien
     * 
     * @param string $nomor_rm, $nomor_kartu, $nama_pasien, $tgl_lahir, $gender
     */
    public function bridgingUpdatePatien($nomor_rm, $nomor_kartu, $nama_pasien, $tgl_lahir, $gender)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;
        $formattedBirthDate = date("Y-m-d H:i:s", strtotime($tgl_lahir));

        // Data request
        $data = json_encode([
            "metadata" => [
                "method" => "update_patient",
                "nomor_rm" => $nomor_rm,
            ],
            "data" => [
                "nomor_kartu" => $nomor_kartu,
                "nomor_rm" => $nomor_rm,
                "nama_pasien" => $nama_pasien,
                "tgl_lahir" => $formattedBirthDate,
                "gender" => $gender,
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return sendRequest($key, $data);
    }

    /**
     * Process bridgingReEditClaim
     * 
     * @param string $nomor_rm, $nomor_kartu, $nama_pasien, $tgl_lahir, $gender
     */
    public function bridgingReEditClaim($no_sep)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;

        // Data request
        $data = json_encode([
            "metadata" => [
                "method" => "reedit_claim",
            ],
            "data" => [
                "nomor_sep" => $no_sep,
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return sendRequest($key, $data);
    }

    /**
     * menampilkan list transaksi berdasar nomer SEP
     * termasuk jika SEP pasien dengan kunjungan raber
     * 
     * @param string $no_sep
     * @return object
     */
    public function getDetailTransactionBySep($no_sep)
    {
        try {
            $detailTransaksi = DB::connection('sqlsrv')
                ->table('BPJS_SEP AS sep')
                ->leftJoin('TRANSAKSIPASIENINAP AS TPI', 'TPI.FTNO_TRANSAKSI', '=', 'sep.FMNOTRANSAKSI')
                ->join('PASIENRAWATINAP AS PRI', function ($join) {
                    $join->on(DB::raw('CAST(PRI.PRWINO_TRANSAKSI AS NVARCHAR)'), '=', 'TPI.FTNO_TRANSAKSI')
                        ->whereRaw('CAST(PRI.PRWINO_URUT AS NVARCHAR) = CAST(TPI.FTNO_URUT AS NVARCHAR)');
                })
                ->leftJoin('DOKTER AS dr', 'PRI.PRWIKD_DOKTER', '=', 'dr.FMDDOKTER_ID')
                ->leftJoin('PASIEN AS p', 'PRI.PRWIKD_PASIEN', '=', 'p.KD_PASIEN')
                ->leftJoin('MR_KEMATIAN AS mati', 'sep.FMNOTRANSAKSI', '=', 'mati.MRKNO_TRANSAKSI')
                ->leftJoin('MR_KEADAAN_KELUAR_RS', 'mati.MRKKEADAAN_KELUAR', '=', 'MR_KEADAAN_KELUAR_RS.FMKKRSKODE')
                ->select(
                    'sep.FMNOSEP',
                    'sep.FMNO_KARTU',
                    'sep.FMJENISRAWAT',
                    'sep.FMKODEKELAS',
                    'PRI.PRWINO_TRANSAKSI',
                    'PRI.PRWITGL_KELUAR',
                    'PRI.CARA_MASUK',
                    'dr.FMDDOKTERN',
                    'p.NAMAPASIEN',
                    'p.KD_PASIEN',
                    'p.TGL_LAHIR',
                    'p.JENIS_KELAMIN',
                    'MR_KEADAAN_KELUAR_RS.FMKKRSKODE_BPJS AS DISCHARGE_STATUS'
                )
                ->where('sep.FMNOSEP', $no_sep)
                ->orderBy('PRI.PRWITGL_MASUK', "ASC")
                ->first();

            if ($detailTransaksi) {
                $detailTransaksi->TGL_MASUK = DB::connection('sqlsrv')
                    ->table('PASIENRAWATINAP')
                    ->where('PRWINO_TRANSAKSI', $detailTransaksi->PRWINO_TRANSAKSI)
                    ->select('PRWITGL_MASUK')
                    ->orderBy('PRWITGL_MASUK', 'ASC')->first()->PRWITGL_MASUK;
            }
        } catch (\Exception $e) {
            // Log the error if any exception occurs
            Log::error('Error get data getDetailTransactionBySep: ' . $e->getMessage());
            return false;
        }
        return $detailTransaksi;
    }

    /**
     * Get total detail tarif transaksi based on array of pasien rujukan->kode_reg
     * 
     * @param object $pasien_inap
     * @return object
     */
    public function getTotalDetailTarifTransaksi($pasien_inap)
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

        // mencari list semua transaksi selain kredit
        // ditandai dengan TRANSAKSIPASIEND.FDTJENISTRANSAKSI="DB"
        $transaksiPasien = DB::connection('sqlsrv')
            ->table('TRANSAKSIPASIENINAPD AS a')
            ->leftJoin('PRODUK AS p', 'p.FMPPRODUK_ID', '=', 'a.FDTKD_PRODUK')
            ->leftJoin('PRODUK_UNIT AS pu', 'p.FMPUNITPRODUK', '=', 'pu.FTUKODE')
            ->where('a.FDTJENISTRANSAKSI', 'DB') // ditandai dengan TRANSAKSIPASIEND.FDTJENISTRANSAKSI="DB"
            ->where('a.FDTNO_TRANSAKSI', $pasien_inap->PRWINO_TRANSAKSI)
            ->select('a.FDTNO_TRANSAKSI', 'a.FDTKDPRODUKN', 'a.FDTQTY', 'a.FDTHARGA', 'a.FDTKD_PRODUK', 'pu.FTUKD_EKLAIM', 'pu.FTUNAMA')
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

        $pendukung = DB::connection('sqlsrv')
            ->table('TRANSAKSIPASIENINAPD')
            ->where('FDTNO_TRANSAKSI', trim($pasien_inap->PRWINO_TRANSAKSI))
            ->whereRaw("LEFT(FDTNO_FAKTUR, 3) = 'FRO'")
            ->select('FDTNO_TRANSAKSI', DB::raw('SUM(FDTKREDIT) as KREDIT'))
            ->groupBy('FDTNO_TRANSAKSI')
            ->first();

        if ($pendukung) {
            $tarif['obat'] = $tarif['obat'] - $pendukung->KREDIT;
        }

        return (object)[
            "tarif_rs" => $tarif,
            "tarif_poli_eks" => $tarif_poli_eks,
        ];
    }

    /**
     * Get all diagnosa from all pasien_inap based on array of pasien rujukan->kode_reg (by no SEP)
     * 
     * @param object $pasien_inap
     * @return string Diagnosa dalam format "S71.0#A00.1"
     */
    public function getAllDiagnosa($pasien_inap)
    {
        $diagnoses_array = [];
        $diagnosa = DB::connection('sqlsrv')
            ->table('MR_PENYAKIT')
            ->where('MRPNO_TRANSAKSI', '=', $pasien_inap->PRWINO_TRANSAKSI)
            ->pluck('MRPKD_PENYAKIT') // Mengambil hanya kolom MRPKD_PENYAKIT sebagai array
            ->toArray(); // Konversi ke array PHP

        $diagnoses_array = array_merge($diagnoses_array, $diagnosa); // Gabungkan hasil query ke array utama

        return implode('#', array_unique($diagnoses_array)); // Gabungkan dengan pemisah "#" dan hilangkan duplikat
    }

    /**
     * Get all tindakan/procedures from all pasien_inap based on array of pasien rujukan->kode_reg (by no SEP)
     * 
     * @param object $pasien_inap
     * @return string Prosedur dalam format "00123#00456"
     */
    public function getAllProcedure($pasien_inap)
    {
        $tindakan_array = [];
        $tindakan = DB::connection('sqlsrv')
            ->table('MR_TINDAKAN')
            ->where('MRTNOTRANSAKSI', '=', $pasien_inap->PRWINO_TRANSAKSI)
            ->pluck('MRTKD_TINDAKAN') // Mengambil hanya kolom MRTKD_TINDAKAN sebagai array
            ->toArray(); // Konversi ke array PHP

        $tindakan_array = array_merge($tindakan_array, $tindakan); // Gabungkan hasil query ke array utama
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
            ->table('PKU.dbo.TAB_PX_PULANG_RESUME')
            ->select('FS_TD')
            ->where('FS_KD_REG', $kode_reg)
            ->orderByDesc('FS_KD_REG') // Mengambil data terbaru
            ->first();

        // Inisialisasi default
        $sistole = 0;
        $diastole = 0;

        if (!empty($vitalSign->FS_TD)) {
            // Memisahkan dengan "/"
            $parts = explode('/', $vitalSign->FS_TD);

            // Memastikan formatnya benar (harus ada dua bagian dan angka)
            if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                $sistole = (int) $parts[0];
                $diastole = (int) $parts[1];
            }
        }

        return (object)[
            'sistole' => $sistole,
            'diastole' => $diastole
        ];
    }
}
