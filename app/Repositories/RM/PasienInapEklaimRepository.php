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
            // Jika hak kelas bukan 1, tentukan naik kelas dari klsRawatNaik
            switch ($klsRawat->klsRawatNaik) {
                case "8":
                    $naik_kelas = "vip";
                    break;
                case "3":
                    $naik_kelas = "kelas_1";
                    break;
                default:
                    $naik_kelas = null;
                    break;
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
            'diagnosa' => $this->getAllDiagnosa($transaksi_utama, true),
            'diagnosa_inagrouper' => $this->getAllDiagnosa($transaksi_utama, true),
            'procedure' => $this->getAllProcedure($transaksi_utama, true),
            'procedure_inagrouper' => $this->getAllProcedure($transaksi_utama, true),
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
                    'p.BERAT_LAHIR',
                    'p.SITB',
                    'p.NAMAPASIEN',
                    'p.KD_PASIEN',
                    'p.TGL_LAHIR',
                    'p.JENIS_KELAMIN',
                    'MR_KEADAAN_KELUAR_RS.FMKKRSKODE_BPJS AS DISCHARGE_STATUS',
                )
                ->where('sep.FMNOSEP', $no_sep)
                ->first();
            if ($detailTransaksi) {
                $detail_pasien_rawat_inap = DB::connection('sqlsrvsimrs')
                    ->table('PASIENRAWATINAP AS PRI')
                    ->leftJoin('DOKTER AS dr', 'PRI.PRWIKD_DOKTER', '=', 'dr.FMDDOKTER_ID')
                    ->leftJoin('ASAL_PASIEN', 'PRI.PRWIASALPASIEN', '=', 'ASAL_PASIEN.FAPKD_ASAL')
                    ->where('PRI.PRWINO_TRANSAKSI', $detailTransaksi->FTNO_TRANSAKSI)
                    ->where('PRI.PRWINO_URUT', $detailTransaksi->FTNO_URUT)
                    ->select(
                        'PRI.PRWINO_TRANSAKSI',
                        'ASAL_PASIEN.KODE AS CARA_MASUK',
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
     * @param boolean $sistemLama
     * @return string Diagnosa dalam format "S71.0#A00.1"
     */
    public function getAllDiagnosa($pasien_inap, $sistemLama = false)
    {
        $diagnoses_array = [];
        $query = DB::connection('sqlsrvsimrs')->table('MR_PENYAKIT');

        // Tentukan kondisi WHERE berdasarkan sistem lama atau tidak
        if ($sistemLama || !$pasien_inap->FMNOSEP) {
            $query->where('MRPNO_TRANSAKSI', '=', $pasien_inap->PRWINO_TRANSAKSI);
        } else {
            $query->where('NOSEP', '=', $pasien_inap->FMNOSEP);
        }
        $query->orderBy('MRPSTAT_DIAG', 'DESC');
        $query->orderBy('MRPURUT_MASUK', 'ASC');
        // Langsung ambil hasil pluck ke array
        $diagnosa = $query->pluck('MRPKD_PENYAKIT')->toArray();

        $diagnoses_array = array_merge($diagnoses_array, $diagnosa);

        return implode('#', array_unique($diagnoses_array));
    }


    /**
     * Get all tindakan/procedures from all pasien_inap based on array of pasien rujukan->kode_reg (by no SEP)
     * 
     * @param object $pasien_inap
     * @param boolean $sistemLama
     * @return string Prosedur dalam format "00123#00456"
     */
    public function getAllProcedure($pasien_inap, $sistemLama = false)
    {
        $tindakan_array = [];
        $query = DB::connection('sqlsrvsimrs')->table('MR_TINDAKAN');

        if ($sistemLama || !$pasien_inap->FMNOSEP) {
            $query->where('MRTNOTRANSAKSI', '=', $pasien_inap->PRWINO_TRANSAKSI);
        } else {
            $query->where('NOSEP', '=', $pasien_inap->FMNOSEP);
        }

        // Panggil pluck() lalu langsung toArray()
        $tindakan = $query->pluck('MRTKD_TINDAKAN')->toArray();

        $tindakan_array = array_merge($tindakan_array, $tindakan);

        // Cek jika kosong, return '#'
        if (empty($tindakan_array)) {
            return '#';
        }

        return implode('#', array_unique($tindakan_array));
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

    /**
     * Process bridgingDataIdrgProcess by no_sep
     * 
     * @param string $no_sep
     */
    public function bridgingDataIdrgProcess($no_sep)
    {
        $transaksi_utama = $this->getDetailTransactionBySep($no_sep);
        if (!$transaksi_utama) {
            return (object)[
                "status" => "nok",
                "error" => "Nomer SEP belum tersimpan, simpan sep terlebih dahulu."
            ];
        }

        if (!$transaksi_utama) {
            return (object)[
                "status" => "nok",
                "error" => "Transaction not found"
            ];
        }

        $bridging = new BridgeVclaim();
        $now = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
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

        if (!$vclaim_detail->response) {
            return (object)[
                "status" => "nok",
                "error" => "VCLAIM: SEP tidak ditemukan."
            ];
        }

        if ($vclaim_detail->response->peserta->noMr != $transaksi_utama->KD_PASIEN) {
            return (object)[
                "status" => "nok",
                "error" => "VCLAIM: SEP milik " . $vclaim_detail->response->peserta->nama
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
        $discharge_status =  $this->dischargeStatusEMR($transaksi_utama->PRWINO_TRANSAKSI);

        $naik_kelas = null;
        if ($vclaim_detail->response->klsRawat->klsRawatNaik) {
            $klsRawat = $vclaim_detail->response->klsRawat;
            // Jika hak kelas bukan 1, tentukan naik kelas dari klsRawatNaik
            switch ($klsRawat->klsRawatNaik) {
                case "8":
                    $naik_kelas = "vip";
                    break;
                case "3":
                    $naik_kelas = "kelas_1";
                    break;
                default:
                    $naik_kelas = null;
                    break;
            }
        }

        $is_pasien_tb = false;
        $diagnosa = $this->getAllDiagnosaIDRG($transaksi_utama);
        $procedure = $this->getAllProcedureIDRG($transaksi_utama);

        $diagnosaArray = explode("#", $diagnosa);
        foreach ($diagnosaArray as $d) {
            if (preg_match('/^A1[5-9]/', $d)) {
                $is_pasien_tb = true;
                break;
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
            'birth_weight' => ($transaksi_utama->BERAT_LAHIR) ? $transaksi_utama->BERAT_LAHIR : "",
            'discharge_status' => $discharge_status,
            'tarif_rs' => $ploting_tarif->tarif_rs,
            'tarif_poli_eks' => $ploting_tarif->tarif_poli_eks,
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
            'cara_masuk' => $transaksi_utama->CARA_MASUK ?: "emd",
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


        if ($response->response->metadata->code != 200) {
            return $response;
        }

        $this->idrgDiagnosaSet($no_sep, $diagnosa);
        $this->idrgProcedureSet($no_sep, $procedure);

        if ($is_pasien_tb && $transaksi_utama->SITB) {
            $requestData = json_encode((object)[
                'metadata' => (object)[
                    'method' => 'sitb_validate',
                ],
                'data' => (object)[
                    'nomor_sep' => $no_sep,
                    'nomor_register_sitb' => $transaksi_utama->SITB,
                ]
            ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

            $key = $user->eklaim_key;
            sendRequest($key, $requestData);
        }

        // grouping satge 1
        $requestData = json_encode((object)[
            'metadata' => (object)[
                'method' => 'grouper',
                "stage" => "1",
                "grouper" => "idrg"
            ],
            'data' => (object)[
                'nomor_sep' => $no_sep,
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $key = $user->eklaim_key;
        $grouping_1_idrg =  sendRequest($key, $requestData);

        $code_grouping_1_idrg = $grouping_1_idrg->response->metadata->code ?? null;
        if ($code_grouping_1_idrg != 200) {
            return $grouping_1_idrg;
        }

        try {
            DB::connection('sqlsrvsimrs')
                ->table('PASIEN_IDRG')
                ->updateOrInsert(
                    [
                        'no_sep' => $no_sep,
                        'pasien_id' => $transaksi_utama->KD_PASIEN
                    ],
                    [
                        "response_eklaim" => json_encode($grouping_1_idrg->response->response_idrg),
                        'is_final' => 0,
                        "updated_at" => $now,
                        "updated_by" => $user->email,
                    ]
                );

            $this->auditTrail->insert([
                "object_id" => $no_sep,
                "action_id" => 6,
                "user_email" => $user->email,
                "user_id" => $user->id,
                "created_at" => $now,
                "data" => $data,
            ]);
        } catch (\Exception $e) {
            Log::error("PasienInapEklaimRepository bridgingDataIdrgProcess err: " . $e->getMessage());
        }

        return $response;
    }

    /**
     * Process idrgDiagnosaSet
     * 
     * @param string $nomor_sep, $diagnosa ("B45.1#G02.1")
     */
    public function idrgDiagnosaSet($nomor_sep, $diagnosa)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;

        // Data request
        $data = json_encode([
            "metadata" => [
                "method" => "idrg_diagnosa_set",
                "nomor_sep" => $nomor_sep,
            ],
            "data" => [
                "diagnosa" => $diagnosa

            ]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return sendRequest($key, $data);
    }

    /**
     * Process idrgProcedureSet
     * 
     * @param string $nomor_sep, $procedure ("88.01#90.090+2#90.090")
     */
    public function idrgProcedureSet($nomor_sep, $procedure)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;

        // Data request
        $data = json_encode([
            "metadata" => [
                "method" => "idrg_procedure_set",
                "nomor_sep" => $nomor_sep,
            ],
            "data" => [
                "procedure" => $procedure

            ]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return sendRequest($key, $data);
    }

    /**
     * Get all diagnosa idrg from all pasien_rujukan based on array of pasien rujukan->kode_reg (by no SEP)
     * 
     * @param object $transaksi_utama
     * @return string Diagnosa dalam format "S71.0#A00.1"
     */
    public function getAllDiagnosaIDRG($transaksi_utama)
    {
        $diagnosa = DB::connection('sqlsrvsimrs')
            ->table('PASIEN_DIAGNOSA_IM')
            ->where('no_sep', '=', $transaksi_utama->FMNOSEP)
            ->orderBy('is_primary', 'desc') // Primary (1) di atas
            ->pluck('code') // Ambil kolom code sebagai array
            ->toArray();

        // Bersihkan spasi tiap kode sebelum gabung
        $diagnosa = array_map('trim', $diagnosa);

        // Hilangkan duplikat dan gabungkan dengan '#'
        return implode('#', ($diagnosa));
    }

    /**
     * Get all diagnosa idrg from all pasien_rujukan based on array of pasien rujukan->kode_reg (by no SEP)
     * 
     * @param object $transaksi_utama
     * @return string Diagnosa dalam format "S71.0#A00.1"
     */
    public function getAllProcedureIDRG($transaksi_utama)
    {
        $procedures = DB::connection('sqlsrvsimrs')
            ->table('PASIEN_TINDAKAN_IM')
            ->where('no_sep', '=', $transaksi_utama->FMNOSEP)
            ->orderBy('is_primary', 'desc') // Primary (1) di atas
            ->pluck('code') // Ambil kolom code sebagai array
            ->toArray();

        // Bersihkan spasi tiap kode sebelum gabung
        $procedures = array_map('trim', $procedures);

        // Hilangkan duplikat dan gabungkan dengan '#'
        return implode('#', ($procedures));
    }

    /**
     * Process bridgingFinalIDRG by no_sep
     * 
     * @param string $no_sep
     */
    public function bridgingFinalIDRG($no_sep)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;
        $transaksi_utama = $this->getDetailTransactionBySep($no_sep);
        if (!$transaksi_utama) {
            return (object)[
                "status" => "nok",
                "error" => "Nomer SEP belum tersimpan, simpan sep terlebih dahulu."
            ];
        }

        $now = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');

        DB::connection('sqlsrvsimrs')->beginTransaction();

        try {
            $affected = DB::connection('sqlsrvsimrs')
                ->table('PASIEN_IDRG')
                ->where('no_sep', $no_sep)
                ->where('pasien_id', $transaksi_utama->KD_PASIEN)
                ->update([
                    'is_final' => 1,
                    'updated_at' => $now,
                    'updated_by' => $user->email,
                ]);

            if ($affected == 0) {
                DB::connection('sqlsrvsimrs')->rollBack();
                return (object)[
                    "status" => "nok",
                    "error" => "data group idrg tidak ditemukan"
                ];
            }

            $this->auditTrail->insert([
                "object_id" => $no_sep,
                "action_id" => 19,
                "user_email" => $user->email,
                "user_id" => $user->id,
                "created_at" => $now,
                "data" => [
                    "nomor_sep" => $no_sep,
                ],
            ]);
        } catch (\Exception $e) {
            DB::connection('sqlsrvsimrs')->rollBack();
            Log::error('bridgingFinalIDRG err: ' . $e->getMessage());
            return (object)[
                "status" => "nok",
                "error" => "Lihat Log"
            ];
        }

        $data = json_encode([
            "metadata" => ["method" => "idrg_grouper_final"],
            "data" => ["nomor_sep" => $no_sep]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $response = sendRequest($key, $data);

        $isSuccess = $response->response->metadata->code == 200;

        if (!$isSuccess) {
            DB::connection('sqlsrvsimrs')->rollBack();
            return (object)[
                "status" => "nok",
                "error" => $response->response->metadata->message ?? "Gagal dari endpoint"
            ];
        }

        DB::connection('sqlsrvsimrs')->commit();
        return $response;
    }

    /**
     * Process bridgingEditUlangIDRG by no_sep
     * 
     * @param string $no_sep
     */
    public function bridgingEditUlangIDRG($no_sep)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;

        $transaksi_utama = $this->getDetailTransactionBySep($no_sep);
        if (!$transaksi_utama) {
            return (object)[
                "status" => "nok",
                "error" => "Nomer SEP belum tersimpan, simpan sep terlebih dahulu."
            ];
        }

        $now = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        $data = json_encode([
            "metadata" => ["method" => "idrg_grouper_reedit"],
            "data" => ["nomor_sep" => $no_sep]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $response = sendRequest($key, $data);
        if ($response->response->metadata->code == 200) {
            try {
                $affected = DB::connection('sqlsrvsimrs')
                    ->table('PASIEN_IDRG')
                    ->where('no_sep', $no_sep)
                    ->where('pasien_id', $transaksi_utama->KD_PASIEN)
                    ->update([
                        'is_final' => 0,
                        'updated_at' => $now,
                        'updated_by' => $user->email,
                    ]);

                DB::connection('sqlsrvsimrs')
                    ->table('PASIEN_INACBG')
                    ->where('no_sep', $no_sep)
                    ->where('pasien_id', $transaksi_utama->KD_PASIEN)
                    ->delete();

                if ($affected == 0) {
                    return (object)[
                        "status" => "nok",
                        "error" => "data group idrg tidak ditemukan"
                    ];
                }

                $this->auditTrail->insert([
                    "object_id" => $no_sep,
                    "action_id" => 20,
                    "user_email" => $user->email,
                    "user_id" => $user->id,
                    "created_at" => $now,
                    "data" => [
                        "nomor_sep" => $no_sep,
                    ],
                ]);
            } catch (\Exception $e) {
                Log::error('bridgingFinalIDRG err: ' . $e->getMessage());
                return (object)[
                    "status" => "nok",
                    "error" => "Lihat Log"
                ];
            }
        }
        return $response;
    }

    /**
     * Process bridgingImportIdrgToIncbg by no_sep
     * 
     * @param string $no_sep
     */
    public function bridgingImportIdrgToIncbg($no_sep)
    {
        $transaksi_utama = $this->getDetailTransactionBySep($no_sep);
        if (!$transaksi_utama) {
            return;
        }

        $now = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        $user = Auth::user();
        $requestData = json_encode((object)[
            'metadata' => (object)[
                'method' => 'idrg_to_inacbg_import',
            ],
            'data' => [
                'nomor_sep' => $no_sep,
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $key = $user->eklaim_key;
        $response = sendRequest($key, $requestData);
        DB::connection('sqlsrvsimrs')->beginTransaction();

        if ($response->status != "ok" || $response->response->metadata->code != 200) {
            return [
                'status' => 'nok',
                'message' => $response->response->metadata->message ?? 'Terjadi kesalahan pada server e-Klaim.',
            ];
        }

        $diagnosa = [];
        $procedure = [];

        if (!empty($response->response->data->diagnosa->expanded)) {
            foreach ($response->response->data->diagnosa->expanded as $index => $item) {
                $isError = isset($item->metadata->error_no);
                $data_to_save = [
                    'MRPKD_PENYAKIT' => $item->code,
                    'MRPNO_TRANSAKSI' => null,
                    'MRPKD_PASIEN' => $transaksi_utama->KD_PASIEN,
                    'MRPKD_UNIT' => null,
                    'MRPTGL_MASUK' => $transaksi_utama->TGL_MASUK,
                    'MRPURUT_MASUK' => $index + 1,
                    'MRPJENIS' => 'RI',
                    'MRPSTAT_DIAG' => ($index == 0) ? 5 : 1,
                    'MRPKASUS' => null,
                    'USER_ID' => $user->id,
                    'UPDATE_DT' => $now,
                    'NOSEP' => $no_sep,
                    'IS_ERROR' => $isError,
                    'ERROR_MESSAGE' => $isError ? $item->metadata->message : null,
                ];
                $diagnosa[] = $data_to_save;
            }
        }

        if (!empty($response->response->data->procedure->expanded)) {
            foreach ($response->response->data->procedure->expanded as $index => $item) {
                $isError = isset($item->metadata->error_no);
                $data_to_save = [
                    'MRTKD_TINDAKAN' => $item->code,
                    'MRTNOTRANSAKSI' => null,
                    'MRTKD_PASIEN' => $transaksi_utama->KD_PASIEN,
                    'MRTKD_UNIT' => null,
                    'MRTTGL_MASUK' => $now,
                    'MRTURUT_MASUK' => $index + 1,
                    'MRTTGL_TINDAKAN' => $transaksi_utama->TGL_MASUK,
                    'NOSEP' => $no_sep,
                    'IS_ERROR' => $isError,
                    'ERROR_MESSAGE' => $isError ? $item->metadata->message : null,
                ];

                $procedure[] = $data_to_save;
            }
        }

        try {

            DB::connection('sqlsrvsimrs')->table('MR_PENYAKIT')
                ->where('NOSEP', $no_sep)
                ->delete();

            DB::connection('sqlsrvsimrs')->table('MR_TINDAKAN')
                ->where('NOSEP', $no_sep)
                ->delete();

            DB::connection('sqlsrvsimrs')->table('MR_PENYAKIT')->insert($diagnosa);
            DB::connection('sqlsrvsimrs')->table('MR_TINDAKAN')->insert($procedure);

            DB::connection('sqlsrvsimrs')
                ->table('PASIEN_INACBG')
                ->where('no_sep', $no_sep)
                ->delete();
        } catch (\Exception $e) {
            DB::connection('sqlsrvsimrs')->rollBack();
            Log::error("bridgingImportIdrgToIncbg: " . $e->getMessage());
            return [
                'status' => 'nok',
                'message' => 'Gagal menyimpan data diagnosa: ' . $e->getMessage(),
            ];
        }

        DB::connection('sqlsrvsimrs')->commit();
        return [
            'status' => 'ok',
            'message' => $response->response->metadata,
        ];
    }

    public function bridgingGroupingInaStageSatu($no_sep)
    {
        $transaksi_utama = $this->getDetailTransactionBySep($no_sep);
        if (!$transaksi_utama) {
            return false;
        }

        $user = Auth::user();
        $key = $user->eklaim_key;
        $now = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        DB::connection('sqlsrvsimrs')->beginTransaction();

        $data = json_encode([
            "metadata" => [
                "method" => "inacbg_diagnosa_set",
                "nomor_sep" => $no_sep,
            ],
            "data" => ["diagnosa" => $this->getAllDiagnosa($transaksi_utama)]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        sendRequest($key, $data);

        $data_proc = json_encode([
            "metadata" => [
                "method" => "inacbg_procedure_set",
                "nomor_sep" => $no_sep,
            ],
            "data" => ["procedure" => $this->getAllProcedure($transaksi_utama)]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        sendRequest($key, $data_proc);

        $data = json_encode([
            "metadata" => [
                "method" => "grouper",
                "grouper" => "inacbg",
                "stage" => 1,
            ],
            "data" => ["nomor_sep" => $no_sep]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $grouping_1_inacbg = sendRequest($key, $data);

        $cbg_code = $grouping_1_inacbg->response->response_inacbg->cbg->code;
        $tarif_inacbg = $grouping_1_inacbg->response->response_inacbg->tariff ?? 0;
        $special_cmg_option = null;
        if (isset($grouping_1_inacbg->response->special_cmg_option) && !empty($grouping_1_inacbg->response->special_cmg_option)) {
            $special_cmg_option = json_encode($grouping_1_inacbg->response->special_cmg_option);
        }
        $tarif_inacbg_1 = 0;
        $tarif_inacbg_2 = 0;
        $tarif_inacbg_3 = 0;
        // mapping tari response dari eklaim
        if (!empty($grouper->response->tarif_alt)) {
            foreach ($grouping_1_inacbg->response->tarif_alt as $tarif) {
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
                    'FKUNCI_VALIDASI2' => DB::raw('FKUNCI_VALIDASI2 + 1')
                ]);

            DB::connection('sqlsrvsimrs')
                ->table('PASIEN_INACBG')
                ->updateOrInsert(
                    [
                        'no_sep' => $no_sep,
                        'pasien_id' => $transaksi_utama->KD_PASIEN
                    ],
                    [
                        "response_inacbg" => json_encode($grouping_1_inacbg->response->response_inacbg),
                        "special_cmg_option" => $special_cmg_option,
                        'is_final' => 0,
                        "updated_at" => $now,
                        "updated_by" => $user->email,
                    ]
                );

            $this->auditTrail->insert([
                "object_id" => $no_sep,
                "action_id" => 21,
                "user_email" => $user->email,
                "user_id" => $user->id,
                "created_at" => $now,
                "data" => $data,
            ]);
        } catch (\Exception $e) {
            DB::connection('sqlsrvsimrs')->rollBack();
            Log::error("PasienRujukanEklaimRepository bridgingGroupingInaStageSatu err: " . $e->getMessage());
        }

        DB::connection('sqlsrvsimrs')->commit();
        return $grouping_1_inacbg;
    }

    /**
     * 
     * @param string $no_sep
     */
    public function bridgingGroupingInaStageDua($no_sep, $special_cmg)
    {
        $transaksi_utama = $this->getDetailTransactionBySep($no_sep);
        if (!$transaksi_utama) {
            return false;
        }

        $user = Auth::user();
        $key = $user->eklaim_key;
        $now = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        DB::connection('sqlsrvsimrs')->beginTransaction();

        $data = json_encode([
            "metadata" => [
                "method" => "grouper",
                "grouper" => "inacbg",
                "stage" => 2,
            ],
            "data" => [
                "nomor_sep" => $no_sep,
                "special_cmg" => $special_cmg
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $grouping_2_inacbg = sendRequest($key, $data);

        $cbg_code = $grouping_2_inacbg->response->response_inacbg->cbg->code;
        $tarif_inacbg = $grouping_2_inacbg->response->response_inacbg->tariff ?? 0;
        $special_cmg_option = null;
        if (isset($grouping_2_inacbg->response->special_cmg_option) && !empty($grouping_2_inacbg->response->special_cmg_option)) {
            $special_cmg_option = json_encode($grouping_2_inacbg->response->special_cmg_option);
        }

        try {
            DB::connection('sqlsrvsimrs')
                ->table('TRANSAKSIPASIENINAP')
                ->where('FTNO_TRANSAKSI', $transaksi_utama->PRWINO_TRANSAKSI)
                ->update([
                    'FTKODEINACBG' => $cbg_code,
                    'FTTARIPINACBG' => $tarif_inacbg,
                    'FKUNCI_VALIDASI2' => DB::raw('FKUNCI_VALIDASI2 + 1') // Incremen FKUNCI_VALIDASI2
                ]);

            DB::connection('sqlsrvsimrs')
                ->table('PASIEN_INACBG')
                ->updateOrInsert(
                    [
                        'no_sep' => $no_sep,
                        'pasien_id' => $transaksi_utama->KD_PASIEN
                    ],
                    [
                        "response_inacbg" => json_encode($grouping_2_inacbg->response->response_inacbg),
                        "special_cmg" => json_encode($grouping_2_inacbg->response),
                        "special_cmg_option" => $special_cmg_option,
                        'is_final' => 0,
                        "updated_at" => $now,
                        "updated_by" => $user->email,
                    ]
                );

            $this->auditTrail->insert([
                "object_id" => $no_sep,
                "action_id" => 22,
                "user_email" => $user->email,
                "user_id" => $user->id,
                "created_at" => $now,
                "data" => $data,
            ]);
        } catch (\Exception $e) {
            DB::connection('sqlsrvsimrs')->rollBack();
            Log::error("PasienInapEklaimRepository bridgingGroupingInaStageDua err: " . $e->getMessage());
        }

        DB::connection('sqlsrvsimrs')->commit();
        return $grouping_2_inacbg;
    }

    /**
     * Process bridgingFinalProcess by no_sep
     * 
     * @param string $no_sep
     */
    public function bridgingFinalInacbg($no_sep)
    {
        $transaksi_utama = $this->getDetailTransactionBySep($no_sep);
        if (!$transaksi_utama) {
            return (object)[
                "status" => "nok",
                "error" => null,
                "response" => "Data tidak ditemukan di database",
            ];
        }
        $user = Auth::user();
        $key = $user->eklaim_key;
        $now = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');

        // Data request
        $data = json_encode([
            "metadata" => ["method" => "inacbg_grouper_final"],
            "data" => ["nomor_sep" => $no_sep]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $response = sendRequest($key, $data);
        if ($response->response->metadata->code != 200) {
            return (object)[
                "status" => "nok",
                "error" => null,
                "response" => $response->response->metadata->message,
            ];
        }

        try {
            DB::connection('sqlsrvsimrs')
                ->table('PASIEN_INACBG')
                ->where('no_sep', $no_sep)
                ->where('pasien_id', $transaksi_utama->KD_PASIEN)
                ->update([
                    'is_final' => 1,
                    'updated_at' => $now,
                    'updated_by' => $user->email,
                ]);

            $this->auditTrail->insert([
                "object_id" => $no_sep,
                "action_id" => 7,
                "user_email" => $user->email,
                "user_id" => $user->id,
                "created_at" => $now,
                "data" => [
                    "nomor_sep" => $no_sep,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('PasienInapEklaimRepository bridgingFinalInacbg: ' . $e->getMessage());
            return (object)[
                "status" => "nok",
                "error" => null,
                "response" => "Lihat Log",
            ];
        }

        return $response;
    }

    /**
     * Process bridgingEditUlangINACBG by no_sep
     * 
     * @param string $no_sep
     */
    public function bridgingEditUlangINACBG($no_sep)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;

        $transaksi_utama = $this->getDetailTransactionBySep($no_sep);
        if (!$transaksi_utama) {
            return (object)[
                "status" => "nok",
                "error" => null,
                "response" => "Data tidak ditemukan di database",
            ];
        }
        $now = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        // Data request
        $data = json_encode([
            "metadata" => ["method" => "inacbg_grouper_reedit"],
            "data" => ["nomor_sep" => $no_sep]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $response = sendRequest($key, $data);
        if ($response->response->metadata->code == 200) {
            try {
                $affected = DB::connection('sqlsrvsimrs')
                    ->table('PASIEN_INACBG')
                    ->where('no_sep', $no_sep)
                    ->where('pasien_id', $transaksi_utama->KD_PASIEN)
                    ->update([
                        'is_final' => 0,
                        'updated_at' => $now,
                        'updated_by' => $user->email,
                    ]);

                if ($affected == 0) {
                    return (object)[
                        "status" => "nok",
                        "error" => "data group inacbg tidak ditemukan"
                    ];
                }

                $this->auditTrail->insert([
                    "object_id" => $no_sep,
                    "action_id" => 23,
                    "user_email" => $user->email,
                    "user_id" => $user->id,
                    "created_at" => $now,
                    "data" => [
                        "nomor_sep" => $no_sep,
                    ],
                ]);
            } catch (\Exception $e) {
                Log::error('PasienInapEklaimRepository bridgingEditUlangINACBG err: ' . $e->getMessage());
                return (object)[
                    "status" => "nok",
                    "error" => "Lihat Log"
                ];
            }
        }
        return $response;
    }

    /**
     * Process bridgingFinalKlaim by no_sep
     * 
     * @param string $no_sep
     */
    public function bridgingFinalKlaim($no_sep)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;
        $now = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        DB::connection('sqlsrvsimrs')->beginTransaction();

        $transaksi_utama = $this->getDetailTransactionBySep($no_sep);
        if (!$transaksi_utama) {
            return (object)[
                "status" => "nok",
                "error" => null,
                "response" => "Data tidak ditemukan di database",
            ];
        }

        // Data request
        $data = json_encode([
            "metadata" => ["method" => "claim_final"],
            "data" => ["nomor_sep" => $no_sep, "coder_nik" => $user->nik]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $response = sendRequest($key, $data);
        if ($response->response->metadata->code != 200) {
            return (object)[
                "status" => "nok",
                "error" => null,
                "response" => "Data tidak ditemukan di database",
            ];
        }

        try {
            DB::connection('sqlsrvsimrs')
                ->table('PASIEN_INACBG')
                ->where('no_sep', $no_sep)
                ->where('pasien_id', $transaksi_utama->KD_PASIEN)
                ->update([
                    'is_final_claim' => 1,
                    'updated_at' => $now,
                    'updated_by' => $user->email,
                ]);

            DB::connection('sqlsrvsimrs')
                ->table('TRANSAKSIPASIENINAP')
                ->where('FTNO_TRANSAKSI', $transaksi_utama->PRWINO_TRANSAKSI)
                ->update([
                    'FKUNCI_VALIDASI' => 1,
                ]);

            $this->auditTrail->insert([
                "object_id" => $no_sep,
                "action_id" => 7,
                "user_email" => $user->email,
                "user_id" => $user->id,
                "created_at" => $now,
                "data" => [
                    "nomor_sep" => $no_sep,
                ],
            ]);
        } catch (\Exception $e) {
            DB::connection('sqlsrvsimrs')->rollBack();
            Log::error('Final Klaim inap bridgingFinalKlaim: ' . $e->getMessage());
        }

        DB::connection('sqlsrvsimrs')->commit();
        return $response;
    }

    public function bridgingReeditKlaim($no_sep)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;
        $now = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        DB::connection('sqlsrvsimrs')->beginTransaction();

        $transaksi_utama = $this->getDetailTransactionBySep($no_sep);
        if (!$transaksi_utama) {
            return (object)[
                "status" => "nok",
                "error" => null,
                "response" => "Data tidak ditemukan di database",
            ];
        }

        // Data request
        $data = json_encode([
            "metadata" => ["method" => "reedit_claim"],
            "data" => [
                "nomor_sep" => $no_sep,
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $responseFinal = sendRequest($key, $data);
        if ($responseFinal->response->metadata->code == 200) {
            try {
                DB::connection('sqlsrvsimrs')
                    ->table('PASIEN_INACBG')
                    ->where('no_sep', $no_sep)
                    ->where('pasien_id', $transaksi_utama->KD_PASIEN)
                    ->update([
                        'is_final_claim' => null,
                        'updated_at' => $now,
                        'updated_by' => $user->email,
                    ]);

                DB::connection('sqlsrvsimrs')
                    ->table('TRANSAKSIPASIENINAP')
                    ->where('FTNO_TRANSAKSI', $transaksi_utama->PRWINO_TRANSAKSI)
                    ->update([
                        'FKUNCI_VALIDASI' => 0,
                    ]);

                $this->auditTrail->insert([
                    "object_id" => $no_sep,
                    "action_id" => 25,
                    "user_email" => $user->email,
                    "user_id" => $user->id,
                    "created_at" => $now,
                    "data" => [
                        "nomor_sep" => $no_sep,
                    ],
                ]);
            } catch (\Exception $e) {
                DB::connection('sqlsrvsimrs')->rollBack();
                Log::error('Inap bridgingReeditKlaim Reedit Kalim err: ' . $e->getMessage());
                return (object)[
                    "status" => "nok",
                    "error" => "Lihat Log"
                ];
            }
        }
        DB::connection('sqlsrvsimrs')->commit();
        return $responseFinal;
    }

    /**
     * Process bridgingDeleteKlaim by no_sep
     * 
     * @param string $no_sep
     */
    public function bridgingDeleteKlaim($no_sep)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;

        $data = json_encode([
            "metadata" => ["method" => "delete_claim"],
            "data" => [
                "nomor_sep" => $no_sep,
                "coder_nik" => $user->nik
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $response = sendRequest($key, $data);
        return $response;
    }

    /**
     * Process dischargeStatusEMR
     */
    public function dischargeStatusEMR($kode_reg)
    {
        $cara_pulang = DB::connection('sqlsrvemr')
            ->table('TAB_PX_PULANG_RESUME')
            ->leftJoin('DEV_CARA_PULANG_RANAP AS master', 'master.id', '=', 'TAB_PX_PULANG_RESUME.FS_CARA_PULANG')
            ->select('master.code_bpjs')
            ->where('FS_KD_REG', $kode_reg)
            ->orderByDesc('mdd')
            ->orderByDesc('mdd_time')
            ->first();

        if ($cara_pulang) {
            $cara_pulang = $cara_pulang->code_bpjs ?? 1;
            return $cara_pulang;
        }
        return 1;
    }
}
