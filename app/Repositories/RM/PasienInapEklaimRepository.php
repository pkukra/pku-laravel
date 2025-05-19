<?php

namespace App\Repositories\RM;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Repositories\RM\RMAuditTrail;
use Bpjs\Bridging\Vclaim\BridgeVclaim;

class PasienInapEklaimRepository
{
    protected $auditTrail;

    public function __construct()
    {
        $this->auditTrail = new RMAuditTrail();
    }

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
        $transaksi_utama = $this->getDetailTransactionBySep($no_sep);
        if (!$transaksi_utama) {
            return (object)[
                "status" => "nok",
                "error" => "Nomer SEP belum tersimpan, simpan sep terlebih dahulu."
            ];
        }

        $bridging = new BridgeVclaim();
        try {
            $endpoint = 'SEP/' . $no_sep;
            $vclaim_detail = json_decode($bridging->getRequest($endpoint));
        } catch (\Exception $e) {
            Log::error("Vclaim Err get SEP: " . $e->getMessage());
            return (object)[
                "status" => "nok",
                "error" => "Gagal terhubung ke vclaim, coba beberapa saat lagi."
            ];
        }

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
            // jika berhasil maka dilakukan join dengan tabel mr_kematian untuk hasil yang lain
            $discharge_status =  $transaksi_utama->DISCHARGE_STATUS;
        }

        $naik_kelas = null;
        if ($vclaim_detail->response->klsRawat->klsRawatNaik) {
            $klsRawat = $vclaim_detail->response->klsRawat;
            if ($klsRawat->klsRawatHak === "1") {
                // Jika hak kelas 1, maka naik kelas otomatis VIP
                $naik_kelas = "vip";
            } else {
                // Jika hak kelas bukan 1, tentukan naik kelas dari klsRawatNaik
                switch ($klsRawat->klsRawatNaik) {
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
                        break;
                }
            }
        }

        // perhitungan tanggal dan los
        $tgl_masuk = Carbon::parse($transaksi_utama->TGL_MASUK);
        $tgl_pulang = $transaksi_utama->PRWITGL_KELUAR
            ? Carbon::parse($transaksi_utama->PRWITGL_KELUAR)
            : now(); // Jika belum pulang, pakai waktu sekarang
        $los = $tgl_masuk->diffInDays($tgl_pulang) ?: 1; // Jika hasilnya 0, set minimal 1 hari

        $ploting_tarif = $this->getTotalDetailTarifTransaksi($transaksi_utama); /// listing dan ploting data dari tabel TRANSAKSIPASIENINAPD

        // mapping data
        $data = (object)[
            'nomor_sep' => $no_sep,
            'tgl_masuk' => $tgl_masuk->format('Y-m-d H:i:s'),
            'tgl_pulang' => $tgl_pulang->format('Y-m-d H:i:s'),
            'jenis_rawat' => $transaksi_utama->FMJENISRAWAT, // 1 ranap, 2 rajal, 3 igd
            'kelas_rawat' => $vclaim_detail->response->klsRawat->klsRawatHak, // kelas rawat BPJS 1,2,3. Tapi ini ambil dari vclaim sekalian saja agar akurat
            "upgrade_class_ind" => ($vclaim_detail->response->klsRawat->klsRawatNaik) ? 1 : 0,
            "upgrade_class_class" => $naik_kelas,
            "upgrade_class_los" => ($ploting_tarif->icu_los) ? $los - $ploting_tarif->icu_los : $los, // jika icu_los ada isinya, maka los minus icu_los
            'birth_weight' => 0,
            'discharge_status' => $discharge_status,
            'tarif_rs' => $ploting_tarif->tarif_rs,
            'tarif_poli_eks' => $ploting_tarif->tarif_poli_eks,
            'diagnosa' => $this->getAllDiagnosa($transaksi_utama),
            'diagnosa_inagrouper' => $this->getAllDiagnosa($transaksi_utama),
            'procedure' => $this->getAllProcedure($transaksi_utama),
            'procedure_inagrouper' => $this->getAllProcedure($transaksi_utama),
            'adl_sub_acute' => "",
            'adl_chronic' => "",
            'nama_dokter' => $transaksi_utama->FMDDOKTERN,
            'icu_indikator' => ($ploting_tarif->icu_los > 0) ? 1 : 0,
            'icu_los' => $ploting_tarif->icu_los,
            'ventilator_hour' => $ploting_tarif->ventilator_hours,
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

        $this->auditTrail->insert([
            "object_id" => $transaksi_utama->PRWINO_TRANSAKSI,
            "action_id" => 6,
            "user_email" => $user->email,
            "user_id" => $user->id,
            "created_at" => Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            "data" => $data,
        ]);

        if ($response->response->metadata->code != 200) {
            return $response;
        }

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

        try {
            DB::connection('sqlsrvsimrs')
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
        } catch (\Exception $e) {
            Log::error("bridgingDataProcess update TRANSAKSIPASIENINAP tarif err: " . $e->getMessage());
        }

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
                DB::connection('sqlsrvsimrs')
                    ->table('TRANSAKSIPASIENINAP')
                    ->where('FTNO_TRANSAKSI', $kode_reg)
                    ->update([
                        'FKUNCI_VALIDASI' => 1,
                    ]);
            } catch (\Exception $e) {
                Log::error('Final process TRANSAKSIPASIENINAP FKUNCI_VALIDASI err: ' . $e->getMessage());
            }
        }

        $this->auditTrail->insert([
            "object_id" => $kode_reg,
            "action_id" => 7,
            "user_email" => $user->email,
            "user_id" => $user->id,
            "created_at" => Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            "data" => [
                "nomor_sep" => $no_sep,
            ],
        ]);

        return $response;
    }

    /**
     * Process bridgingKirimKlaimProcess by no_sep
     * 
     * @param string $no_sep
     */
    public function bridgingKirimKlaimProcess($no_sep)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;

        // Data request
        $data = json_encode([
            "metadata" => ["method" => "send_claim_individual"],
            "data" => ["nomor_sep" => $no_sep]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $response = sendRequest($key, $data);
        return $response;
    }

    /**
     * Process bridgingCetakKlaim by no_sep
     * 
     * @param string $no_sep
     */
    public function bridgingCetakKlaim($no_sep)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;

        // Data request
        $data = json_encode([
            "metadata" => ["method" => "claim_print"],
            "data" => ["nomor_sep" => $no_sep]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $response = sendRequest($key, $data);
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
            $detailTransaksi = DB::connection('sqlsrvsimrs')
                ->table('BPJS_SEP AS sep')
                ->leftJoin('TRANSAKSIPASIENINAP AS TPI', 'TPI.FTNO_TRANSAKSI', '=', 'sep.FMNOTRANSAKSI')
                ->leftJoin('PASIEN AS p', 'TPI.FTKD_PASIEN', '=', 'p.KD_PASIEN')
                ->leftJoin('MR_KEMATIAN AS mati', 'sep.FMNOTRANSAKSI', '=', 'mati.MRKNO_TRANSAKSI')
                ->leftJoin('MR_KEADAAN_KELUAR_RS', 'mati.MRKKEADAAN_KELUAR', '=', 'MR_KEADAAN_KELUAR_RS.FMKKRSKODE')
                ->select(
                    'TPI.FTNO_TRANSAKSI',
                    'TPI.FTNO_URUT',
                    'sep.FMNOSEP',
                    'sep.FMNO_KARTU',
                    'sep.FMJENISRAWAT',
                    'sep.FMKODEKELAS',
                    'p.NAMAPASIEN',
                    'p.KD_PASIEN',
                    'p.TGL_LAHIR',
                    'p.JENIS_KELAMIN',
                    'MR_KEADAAN_KELUAR_RS.FMKKRSKODE_BPJS AS DISCHARGE_STATUS'
                )
                ->where('sep.FMNOSEP', $no_sep)
                ->first();
            if ($detailTransaksi) {
                $detail_pasien_rawat_inap = DB::connection('sqlsrvsimrs')
                    ->table('PASIENRAWATINAP AS PRI')
                    ->leftJoin('DOKTER AS dr', 'PRI.PRWIKD_DOKTER', '=', 'dr.FMDDOKTER_ID')
                    ->where('PRI.PRWINO_TRANSAKSI', $detailTransaksi->FTNO_TRANSAKSI)
                    ->where('PRI.PRWINO_URUT', $detailTransaksi->FTNO_URUT)
                    ->select(
                        'PRI.PRWINO_TRANSAKSI',
                        'PRI.CARA_MASUK',
                        DB::raw("FORMAT(PRI.PRWITGL_MASUK, 'yyyy-MM-dd') + ' ' + FORMAT(PRI.PRWIKPJAM_MASUK, 'HH:mm:ss') AS PRWI_TGLJAM_MASUK"),
                        DB::raw("FORMAT(PRI.PRWITGL_KELUAR, 'yyyy-MM-dd') + ' ' + FORMAT(PRI.PRWIJAM_KELUAR, 'HH:mm:ss') AS PRWI_TGLJAM_KELUAR"),
                        'dr.FMDDOKTERN',
                    )
                    ->first();
                if (!$detail_pasien_rawat_inap) {
                    // Log the error if data not found
                    Log::error('getDetailTransactionBySep detail_pasien_rawat_inap return null...');
                    return false;
                }
                $detailTransaksi->PRWINO_TRANSAKSI = $detail_pasien_rawat_inap->PRWINO_TRANSAKSI;
                $detailTransaksi->TGL_MASUK = $detail_pasien_rawat_inap->PRWI_TGLJAM_MASUK;
                $detailTransaksi->PRWITGL_KELUAR = $detail_pasien_rawat_inap->PRWI_TGLJAM_KELUAR;
                $detailTransaksi->FMDDOKTERN = $detail_pasien_rawat_inap->FMDDOKTERN;
                $detailTransaksi->CARA_MASUK = $detail_pasien_rawat_inap->CARA_MASUK;
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
        $transaksiPasien = DB::connection('sqlsrvsimrs')
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

        $icu_los = 0;
        $ventilator_hours = 0;
        foreach ($transaksiPasien as $transaksi) {
            // hitung icu los dulu
            if ($transaksi->FDTKDPRODUKN == "ADMICU101") {
                $icu_los += (is_numeric($transaksi->FDTQTY) ? (int) $transaksi->FDTQTY : 0);
            }

            // hitung icu los dulu
            if ($transaksi->FDTKDPRODUKN == "ADMICU106" || $transaksi->FDTKDPRODUKN == "SABICU319") {
                $ventilator_hours += (is_numeric($transaksi->FDTQTY) ? ((int) $transaksi->FDTQTY * 24) : 0);
            }

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

        // mencari retur obat lama, jika lihat di masa depan hapus saja. sudah digantikan dibawahnya XD
        // $pendukung = DB::connection('sqlsrvsimrs')
        //     ->table('TRANSAKSIPASIENINAPD')
        //     ->where('FDTNO_TRANSAKSI', trim($pasien_inap->PRWINO_TRANSAKSI))
        //     ->whereRaw("LEFT(FDTNO_FAKTUR, 3) = 'FRO'")
        //     ->select('FDTNO_TRANSAKSI', DB::raw('SUM(FDTKREDIT) as KREDIT'))
        //     ->groupBy('FDTNO_TRANSAKSI')
        //     ->first();

        // mencari retur obat jika ada maka dikurangi dari total obat. langsung dari tabel RETURJIN
        $obatRetur = (int) DB::connection('sqlsrvsimrs')
            ->table('RETURJIN')
            ->where('FHRJNO_TRANSAKSI', trim($pasien_inap->PRWINO_TRANSAKSI))
            ->sum('FHRJTOTAL');

        $tarif['obat'] = $tarif['obat'] - $obatRetur;

        return (object)[
            "tarif_rs" => $tarif,
            "tarif_poli_eks" => $tarif_poli_eks,
            "icu_los" => $icu_los,
            "ventilator_hours" => $ventilator_hours,
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
        $diagnosa = DB::connection('sqlsrvsimrs')
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
        $tindakan = DB::connection('sqlsrvsimrs')
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
        $vitalSign = DB::connection('sqlsrvsimrs')
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
