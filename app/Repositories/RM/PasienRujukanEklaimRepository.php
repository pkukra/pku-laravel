<?php

namespace App\Repositories\RM;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Repositories\RM\RMAuditTrail;

class PasienRujukanEklaimRepository
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
        $semua_transaksi = $this->allTransactionsBySep($no_sep);
        if (!$semua_transaksi || count($semua_transaksi) < 1) {
            return false;
        }

        // menentukan dokter mana yang menjadi dpjp utama
        // jika array hanya 1, maka otomatis index 0 menjadi dpjp uatama
        // jika array lebih dari 1 maka dipilih yang RUBBER adalah false(0) yang menjadi dpjp utama
        // berarti yang bukan dokter RaBer (Rawat Bersama)
        $transaksi_utama = $semua_transaksi[0];
        foreach ($semua_transaksi as $transaksi) {
            if ($transaksi->RUBBER == 0) {
                $transaksi_utama = $transaksi;
                break;
            }
        }

        // update/reedit claim
        $this->bridgingReEditClaim($no_sep);

        // buat new claim dulu
        $this->bridgingNewClaimProcess(
            $transaksi_utama->FMNO_KARTU,
            $transaksi_utama->FMNOSEP,
            $transaksi_utama->FRPPASIEN_ID,
            $transaksi_utama->NAMAPASIEN,
            $transaksi_utama->TGL_LAHIR,
            $transaksi_utama->JENIS_KELAMIN,
        );

        // update patient
        $this->bridgingUpdatePatien(
            $transaksi_utama->FRPPASIEN_ID,
            $transaksi_utama->FMNO_KARTU,
            $transaksi_utama->NAMAPASIEN,
            $transaksi_utama->TGL_LAHIR,
            $transaksi_utama->JENIS_KELAMIN,
        );

        $user = Auth::user();
        $bloodPresure = $this->getBloodPressure($semua_transaksi);
        // defaultnya atas persetujuan dokter
        $discharge_status =  1;
        if ($transaksi_utama->DISCHARGE_SRARTUS) {
            // jika berhasil di join dengan tabel mr_kematian untuk hasil yang lain
            $discharge_status =  $transaksi_utama->DISCHARGE_SRARTUS;
        }

        // mapping data
        $data = (object)[
            'nomor_sep' => $no_sep,
            'tgl_masuk' => Carbon::parse($transaksi_utama->FRPTGL)->format('Y-m-d H:i:s'),
            'tgl_pulang' => Carbon::parse($transaksi_utama->FRPTGL)->format('Y-m-d H:i:s'),
            'jenis_rawat' => $transaksi_utama->FMJENISRAWAT, // 1 ranap, 2 rajal, 3 igd
            'kelas_rawat' => 3, // regular 3, eksekutif 1. pilih 3 karena PKU belum ada kelas eksekutif BPJS
            'birth_weight' => 0,
            'discharge_status' => $discharge_status,
            'tarif_rs' => $this->getTotalDetailTarifTransaksi($semua_transaksi)->tarif_rs,
            'tarif_poli_eks' => $this->getTotalDetailTarifTransaksi($semua_transaksi)->tarif_poli_eks,
            'diagnosa' => $this->getAllDiagnosa($semua_transaksi),
            'diagnosa_inagrouper' => $this->getAllDiagnosa($semua_transaksi),
            'procedure' => $this->getAllProcedure($semua_transaksi),
            'procedure_inagrouper' => $this->getAllProcedure($semua_transaksi),
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
            'sistole' => $bloodPresure->sistole,
            'diastole' => $bloodPresure->diastole,
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

        $special_cmg = implode('#', array_column($grouper->response->special_cmg_option ?? [], 'code'));
        if (!empty($specialCmg)) {
            $grouper2 = $this->bridgingGroupStage2Process($no_sep, $special_cmg);
            $cbg_code = $grouper2->response->response->cbg->code ?? null;
            $tarif_inacbg = $grouper2->response->response->cbg->tariff ?? 0;
        }

        try {
            foreach ($semua_transaksi as $transaksi) {
                DB::connection('sqlsrvsimrs')
                    ->table('TRANSAKSIPASIEN')
                    ->where('FTNO_TRANSAKSI', $transaksi->FRPNOTRANSAKSIKJ)
                    ->update([
                        'FTKODEINACBG' => $cbg_code,
                        'FTTARIPINACBG' => (float) $tarif_inacbg,
                        'FKUNCI_VALIDASI2' => DB::raw('FKUNCI_VALIDASI2 + 1')
                    ]);

                $this->auditTrail->insert([
                    "object_id" => $transaksi->FRPNOTRANSAKSIKJ,
                    "action_id" => 6,
                    "user_email" => $user->email,
                    "user_id" => $user->id,
                    "created_at" => Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    "data" => $data,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("PasienRujukanEklaimRepository bridgingDataProcess err: " . $e->getMessage());
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
    public function bridgingFinalProcess($no_sep)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;

        $semua_transaksi = $this->allTransactionsBySep($no_sep);
        if (!$semua_transaksi || count($semua_transaksi) < 1) {
            return (object)[
                "status" => "nok",
                "error" => null,
                "response" => "Data tidak ditemukan di database",
            ];
        }

        // menentukan dokter mana yang menjadi dpjp utama
        // jika array hanya 1, maka otomatis index 0 menjadi dpjp uatama
        // jika array lebih dari 1 maka dipilih yang RUBBER adalah false(0) yang menjadi dpjp utama
        // berarti yang bukan dokter RaBer (Rawat Bersama)
        $transaksi_utama = $semua_transaksi[0];
        foreach ($semua_transaksi as $transaksi) {
            if ($transaksi->RUBBER == 0) {
                $transaksi_utama = $transaksi;
                break;
            }
        }

        // Data request
        $data = json_encode([
            "metadata" => ["method" => "claim_final"],
            "data" => ["nomor_sep" => $no_sep, "coder_nik" => $user->nik]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $response = sendRequest($key, $data);
        if ($response->response->metadata->code == 200) {
            try {
                $this->auditTrail->insert([
                    "object_id" => $transaksi_utama->FRPNOTRANSAKSIKJ,
                    "action_id" => 7,
                    "user_email" => $user->email,
                    "user_id" => $user->id,
                    "created_at" => Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    "data" => [
                        "nomor_sep" => $no_sep,
                    ],
                ]);

                DB::connection('sqlsrvsimrs')
                    ->table('TRANSAKSIPASIEN')
                    ->where('FTNO_TRANSAKSI', $transaksi_utama->FRPNOTRANSAKSIKJ)
                    ->update([
                        'FKUNCI_VALIDASI' => 1,
                    ]);

                DB::connection('sqlsrvsimrs')
                    ->table('PASIEN_RUJUKAN')
                    ->where('FRPNOTRANSAKSI', $transaksi_utama->FRPNOTRANSAKSI)
                    ->update([
                        'IS_INACBG_FINAL' => 1,
                    ]);
            } catch (\Exception $e) {
                Log::error('Final process PASIEN_RUJUKAN IS_INACBG_FINAL err: ' . $e->getMessage());
                return (object)[
                    "status" => "nok",
                    "error" => "Lihat Log"
                ];
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
     * @return array
     */
    public function allTransactionsBySep($no_sep)
    {
        try {
            $detailTransaksi = DB::connection('sqlsrvsimrs')
                ->table('BPJS_SEP AS sep')
                ->leftJoin('PASIEN_RUJUKAN AS pr', function ($join) use ($no_sep) {
                    $join->on('pr.FRPNOTRANSAKSI', '=', 'sep.FMNOTRANSAKSI')
                        ->orOn('pr.FRPNOTRANSAKSIKJ', '=', 'sep.FMNOTRANSAKSI');
                })
                ->leftJoin('DOKTER AS dr', 'pr.FRPDOKTER_ID', '=', 'dr.FMDDOKTER_ID')
                ->leftJoin('POLIKLINIK AS poli', 'pr.FRPUNIT', '=', 'poli.FMPKLINIK_ID')
                ->leftJoin('PASIEN AS p', 'pr.FRPPASIEN_ID', '=', 'p.KD_PASIEN')
                // ->leftJoin('MR_KEMATIAN AS mati', 'sep.FMNOTRANSAKSI', '=', 'mati.MRKNO_TRANSAKSI')

                ->leftJoin('MR_KEMATIAN AS mati', function ($join) use ($no_sep) {
                    $join->on('pr.FRPNOTRANSAKSI', '=', 'mati.MRKNO_TRANSAKSI')
                        ->orOn('pr.FRPNOTRANSAKSIKJ', '=', 'mati.MRKNO_TRANSAKSI');
                })

                ->leftJoin('MR_KEADAAN_KELUAR_RS', 'mati.MRKKEADAAN_KELUAR', '=', 'MR_KEADAAN_KELUAR_RS.FMKKRSKODE')
                ->select(
                    'sep.FMNOSEP',
                    'sep.FMNO_KARTU',
                    'sep.FMJENISRAWAT',
                    'sep.FMKODEKELAS',
                    'pr.*',
                    'dr.FMDDOKTERN',
                    'poli.FMPKLINIKN',
                    'p.NAMAPASIEN',
                    'p.TGL_LAHIR',
                    'p.JENIS_KELAMIN',
                    'MR_KEADAAN_KELUAR_RS.FMKKRSKODE_BPJS AS DISCHARGE_SRARTUS',
                    'mati.MRKKEADAAN_KELUAR'
                )
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
            // ditandai dengan TRANSAKSIPASIEND.FDTKD_PRODUK!=2

            $transaksiPasien = DB::connection('sqlsrvsimrs')
                ->table('PKUKRASIMRS.dbo.TRANSAKSIPASIEND as A')
                ->leftJoin('PKUKRASIMRS.dbo.PRODUK as P', 'P.FMPPRODUK_ID', '=', 'A.FDTKD_PRODUK')
                ->leftJoin('PKUKRASIMRS.dbo.PRODUK_UNIT as PU', 'P.FMPUNITPRODUK', '=', 'PU.FTUKODE')
                ->select([
                    'A.FDTKDPRODUKN',
                    'A.FDTKD_PRODUK',
                    'A.FDTQTY',
                    'A.FDTHARGA',
                    'PU.FTUKD_EKLAIM'
                ])
                ->where('A.FDTNO_TRANSAKSI', $pasien_rujukan->FRPNOTRANSAKSIKJ)
                ->where('A.FDTKD_PRODUK', '!=', 'ADL002') // selain obat, karena obat cari di FJINKOTA saja
                ->where('A.FDTKD_PRODUK', '!=', '2') // selain kredit
                ->get();

            $tarif_poli_eks = $transaksiPasien->reduce(function ($carry, $transaksi) {
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

            $fjinkotaData = DB::connection('sqlsrvsimrs')
                ->table('FJINKOTA')
                ->where('FHFJNO_TRANSAKSI', '=', $pasien_rujukan->FRPNOTRANSAKSIKJ)
                ->where('FHFJKRONIS', '=', 0)
                ->select('FHFJBUKTI_ID', 'FHFJTOTAL')
                ->get();

            foreach ($fjinkotaData as $fjinkota) {
                $tarif['obat'] = (float)$fjinkota->FHFJTOTAL;
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
        $nonRaber = [];
        $raber = [];

        // Pisahkan berdasarkan nilai raber
        foreach ($array_pasien_rujukan as $item) {
            if ($item->RUBBER == 0) { // cari pasien non raber, yang dpjp utama adalah trx utama
                $nonRaber[] = $item;
            } else {
                $raber[] = $item;
            }
        }

        // Gabungkan kembali: non-raber dulu, baru raber
        $ordered_pasien_rujukan = array_merge($nonRaber, $raber);

        $diagnoses_array = [];
        foreach ($ordered_pasien_rujukan as $pasien_rujukan) {
            $diagnosa = DB::connection('sqlsrvsimrs')
                ->table('MR_PENYAKIT')
                ->where('MRPNO_TRANSAKSI', '=', $pasien_rujukan->FRPNOTRANSAKSIKJ)
                ->pluck('MRPKD_PENYAKIT')
                ->toArray();

            $diagnoses_array = array_merge($diagnoses_array, $diagnosa);
        }

        return implode('#', array_unique($diagnoses_array));
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
            $tindakan = DB::connection('sqlsrvsimrs')
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
    public function getBloodPressure($semua_transaksi)
    {
        foreach ($semua_transaksi as $transaksi) {
            if ($transaksi->RUBBER == 0) {
                $vitalSign = DB::connection('sqlsrvsimrs')
                    ->table('PKU.dbo.TAC_RJ_VITAL_SIGN')
                    ->select('FS_TD as sistole', 'FS_TD2 as diastole')
                    ->where('FS_KD_REG', $transaksi->FRPNOTRANSAKSI)
                    ->orderByDesc('FS_KD_REG')
                    ->first();

                return (object)[
                    'sistole' => $vitalSign->sistole ?? 0,
                    'diastole' => $vitalSign->diastole ?? 0
                ];
            }
        }

        // Fallback jika tidak ada transaksi dengan RUBBER == 0
        return (object)[
            'sistole' => 0,
            'diastole' => 0
        ];
    }
}
