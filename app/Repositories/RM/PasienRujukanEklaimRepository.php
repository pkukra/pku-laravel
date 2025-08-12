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
     * Process bridgingImportIdrgToIncbg by no_sep
     * 
     * @param string $no_sep
     */
    public function bridgingImportIdrgToIncbg($no_sep)
    {
        $semua_transaksi = $this->allTransactionsBySep($no_sep);
        $transaksi_utama = $semua_transaksi[0];
        foreach ($semua_transaksi as $transaksi) {
            if ($transaksi->RUBBER == 0) {
                $transaksi_utama = $transaksi;
                break;
            }
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
                    'MRPKD_PASIEN' => $transaksi_utama->FRPPASIEN_ID,
                    'MRPKD_UNIT' => null,
                    'MRPTGL_MASUK' => $transaksi_utama->FRPTGL,
                    'MRPURUT_MASUK' => $index + 1,
                    'MRPJENIS' => 'RJ',
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
                    'MRTKD_PASIEN' => $transaksi_utama->FRPPASIEN_ID,
                    'MRTKD_UNIT' => null,
                    'MRTTGL_MASUK' => $now,
                    'MRTURUT_MASUK' => $index + 1,
                    'MRTTGL_TINDAKAN' => $transaksi_utama->FRPTGL,
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
        $bloodPresure = $this->getBloodPressure($transaksi_utama->FRPNOTRANSAKSI);
        // defaultnya atas persetujuan dokter
        $discharge_status =  1;
        if ($transaksi_utama->DISCHARGE_SRARTUS) {
            // jika berhasil di join dengan tabel mr_kematian untuk hasil yang lain
            $discharge_status =  $transaksi_utama->DISCHARGE_SRARTUS;
        }

        $is_pasien_tb = false;
        $diagnosa = $this->getAllDiagnosa($semua_transaksi, true);
        $procedure = $this->getAllProcedure($semua_transaksi, true);

        $diagnosaArray = explode("#", $diagnosa);
        foreach ($diagnosaArray as $d) {
            if (preg_match('/^A1[5-9]/', $d)) {
                $is_pasien_tb = true;
                break;
            }
        }

        // mapping data
        $data = (object)[
            'nomor_sep' => $no_sep,
            'tgl_masuk' => Carbon::parse($transaksi_utama->FRPTGL)->format('Y-m-d H:i:s'),
            'tgl_pulang' => Carbon::parse($transaksi_utama->FRPTGL)->format('Y-m-d H:i:s'),
            'jenis_rawat' => $transaksi_utama->FMJENISRAWAT, // 1 ranap, 2 rajal, 3 igd
            'kelas_rawat' => 3, // kelas rawat BPJS 1,2,3
            'birth_weight' => ($transaksi_utama->BERAT_LAHIR) ? $transaksi_utama->BERAT_LAHIR : "",
            'discharge_status' => $discharge_status,
            'tarif_rs' => $this->getTotalDetailTarifTransaksi($semua_transaksi)->tarif_rs,
            'tarif_poli_eks' => $this->getTotalDetailTarifTransaksi($semua_transaksi)->tarif_poli_eks,

            'diagnosa' => $diagnosa,
            'diagnosa_inagrouper' => $diagnosa,
            'procedure' => $procedure,
            'procedure_inagrouper' => $procedure,

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
            DB::connection('sqlsrvsimrs')
                ->table('TRANSAKSIPASIEN')
                ->where('FTNO_TRANSAKSI', $transaksi_utama->FRPNOTRANSAKSIKJ)
                ->update([
                    'FTKODEINACBG' => $cbg_code,
                    'FTTARIPINACBG' => (float) $tarif_inacbg,
                    'FKUNCI_VALIDASI2' => DB::raw('FKUNCI_VALIDASI2 + 1')
                ]);

            $this->auditTrail->insert([
                "object_id" => $transaksi_utama->FRPNOTRANSAKSIKJ,
                "action_id" => 6,
                "user_email" => $user->email,
                "user_id" => $user->id,
                "created_at" => Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                "data" => $data,
            ]);
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
        $detailTransaksiFinalArray = [];

        try {
            $detailTransaksiArray = DB::connection('sqlsrvsimrs')
                ->table('BPJS_SEP AS sep')
                ->leftJoin('PASIEN_RUJUKAN AS pr', function ($join) {
                    $join->on('pr.FRPNOTRANSAKSI', '=', 'sep.FMNOTRANSAKSI')
                        ->orOn('pr.FRPNOTRANSAKSIKJ', '=', 'sep.FMNOTRANSAKSI');
                })
                ->leftJoin('DOKTER AS dr', 'pr.FRPDOKTER_ID', '=', 'dr.FMDDOKTER_ID')
                ->leftJoin('POLIKLINIK AS poli', 'pr.FRPUNIT', '=', 'poli.FMPKLINIK_ID')
                ->leftJoin('PASIEN AS p', 'pr.FRPPASIEN_ID', '=', 'p.KD_PASIEN')
                ->leftJoin('MR_KEMATIAN AS mati', function ($join) {
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
                    'p.SITB',
                    'p.BERAT_LAHIR',
                    'p.JENIS_KELAMIN',
                    'MR_KEADAAN_KELUAR_RS.FMKKRSKODE_BPJS AS DISCHARGE_SRARTUS',
                    'mati.MRKKEADAAN_KELUAR'
                )
                ->where('sep.FMNOSEP', $no_sep)
                ->get();

            // Filter hanya data yang punya transaksi (tidak null di kolom kunci)
            $filteredData = $detailTransaksiArray->filter(function ($row) {
                return !is_null($row->FRPNOTRANSAKSI) || !is_null($row->FRPNOTRANSAKSIKJ);
            });

            // Hilangkan duplikat berdasarkan FRPNOTRANSAKSI
            $existingKeys = [];
            foreach ($filteredData as $row) {
                $key = $row->FRPNOTRANSAKSI;

                if (!in_array($key, $existingKeys)) {
                    $detailTransaksiFinalArray[] = $row;
                    $existingKeys[] = $key;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error get data allTransactionsBySep: ' . $e->getMessage());
            return false;
        }

        return $detailTransaksiFinalArray;
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

        $noTransaksiArr = collect($array_pasien_rujukan)->pluck('FRPNOTRANSAKSIKJ')->unique()->toArray();

        // Ambil seluruh transaksi sekaligus
        $transaksiPasienAll = DB::connection('sqlsrvsimrs')
            ->table('TRANSAKSIPASIEN AS a')
            ->leftJoin('TRANSAKSIPASIEND AS b', 'a.FTNO_TRANSAKSI', '=', 'b.FDTNO_TRANSAKSI')
            ->leftJoin('PRODUK AS p', 'p.FMPPRODUK_ID', '=', 'b.FDTKD_PRODUK')
            ->leftJoin('PRODUK_UNIT AS pu', 'p.FMPUNITPRODUK', '=', 'pu.FTUKODE')
            ->where('b.FDTJENISTRANSAKSI', 'DB')
            ->whereNot('b.FDTKD_PRODUK', 'ADL002') // sendirikan obat, nanti dicari sendiri
            ->whereIn('a.FTNO_TRANSAKSI', $noTransaksiArr)
            ->select('a.FTNO_TRANSAKSI', 'b.FDTQTY', 'b.FDTHARGA', 'b.FDTKD_PRODUK', 'pu.FTUKD_EKLAIM')
            ->get()
            ->groupBy('FTNO_TRANSAKSI');

        // Ambil data FJINKOTA sekaligus
        $fjinkotaDataAll = DB::connection('sqlsrvsimrs')
            ->table('FJINKOTA')
            ->whereIn('FHFJNO_TRANSAKSI', $noTransaksiArr)
            ->where('FHFJKRONIS', 0)
            ->select('FHFJNO_TRANSAKSI', 'FHFJTOTAL')
            ->get()
            ->groupBy('FHFJNO_TRANSAKSI');

        foreach ($noTransaksiArr as $noTransaksi) {
            $transaksiPasien = $transaksiPasienAll->get($noTransaksi, collect());

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

            $fjinkotaData = $fjinkotaDataAll->get($noTransaksi, collect());
            foreach ($fjinkotaData as $fjinkota) {
                $tarif['obat'] += (float)$fjinkota->FHFJTOTAL;
                $tarif_poli_eks += (float)$fjinkota->FHFJTOTAL;
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
     * @param boolean $sistemLama
     * @return string Diagnosa dalam format "S71.0#A00.1"
     */
    public function getAllDiagnosa($pasienRujukanList, $sistemLama = false)
    {
        $allDiagnosa = [];

        foreach ($pasienRujukanList as $pasien) {
            $query = DB::connection('sqlsrvsimrs')->table('MR_PENYAKIT');

            // Tentukan kondisi WHERE berdasarkan sistem lama atau tidak
            if ($sistemLama || empty($pasien->FMNOSEP)) {
                $query->where('MRPNO_TRANSAKSI', $pasien->FRPNOTRANSAKSIKJ);
            } else {
                $query->where('NOSEP', $pasien->FMNOSEP);
            }

            // Ambil data diagnosa, urutkan berdasarkan status
            $diagnosa = $query->orderBy('MRPSTAT_DIAG', 'DESC')
                ->pluck('MRPKD_PENYAKIT')
                ->toArray();

            $allDiagnosa = array_merge($allDiagnosa, $diagnosa);
        }

        // Hilangkan duplikat dan gabungkan dengan tanda #
        return implode('#', array_unique($allDiagnosa));
    }

    /**
     * Get all tindakan/procedures from all pasien_rujukan based on array of pasien rujukan->kode_reg (by no SEP)
     * 
     * @param array $array_pasien_rujukan, boolean $sistemLama
     * @return string Prosedur dalam format "00123#00456"
     */
    public function getAllProcedure($pasienRujukanList, $sistemLama = false)
    {
        $allTindakan = [];

        foreach ($pasienRujukanList as $pasien) {
            $query = DB::connection('sqlsrvsimrs')->table('MR_TINDAKAN');

            // Tentukan kondisi WHERE berdasarkan sistem lama atau tidak
            if ($sistemLama || empty($pasien->FMNOSEP)) {
                $query->where('MRTNOTRANSAKSI', $pasien->FRPNOTRANSAKSIKJ);
            } else {
                $query->where('NOSEP', $pasien->FMNOSEP);
            }

            // Ambil kode tindakan
            $tindakan = $query->pluck('MRTKD_TINDAKAN')->toArray();
            $allTindakan = array_merge($allTindakan, $tindakan);
        }

        // Jika kosong, langsung return "#"
        if (empty($allTindakan)) {
            return "#";
        }

        // Hilangkan duplikat dan gabungkan dengan tanda #
        return implode('#', array_unique($allTindakan));
    }



    /**
     * Get all diagnosa idrg from all pasien_rujukan based on array of pasien rujukan->kode_reg (by no SEP)
     * 
     * @param array $no_sep
     * @return string Diagnosa dalam format "S71.0#A00.1"
     */
    public function getAllDiagnosaIDRG($no_sep)
    {
        $diagnoses_final_arr = [];
        $diagnosa = DB::connection('sqlsrvsimrs')
            ->table('PASIEN_DIAGNOSA_IM')
            ->where('no_sep', '=', $no_sep)
            ->pluck('code') // Ambil kolom code sebagai array
            ->toArray();

        // Bersihkan spasi tiap kode sebelum gabung
        $diagnosa = array_map('trim', $diagnosa);

        $diagnoses_final_arr = array_merge($diagnoses_final_arr, $diagnosa);

        // Hilangkan duplikat dan gabungkan dengan '#'
        return implode('#', array_unique($diagnoses_final_arr));
    }

    /**
     * Get all procedure idrg from all pasien_rujukan based on array of pasien rujukan->kode_reg (by no SEP)
     * 
     * @param array $no_sep
     * @return string Procedure dalam format "S71.0#A00.1"
     */
    public function getAllProcedureIDRG($no_sep)
    {
        $procedures_final_arr = [];
        $procedures = DB::connection('sqlsrvsimrs')
            ->table('PASIEN_TINDAKAN_IM')
            ->where('no_sep', '=', $no_sep)
            ->select('code', 'multiplicity')
            ->get();

        foreach ($procedures as $item) {
            $code = trim($item->code);
            $multiplicity = trim($item->multiplicity);

            if ($multiplicity > 1) {
                $procedures_final_arr[] = $code . '+' . $multiplicity;
            } else {
                $procedures_final_arr[] = $code;
            }
        }
        return implode('#', $procedures_final_arr);
    }

    /**
     * Get sistole and diastole based on kode_reg
     * 
     * @param string $kode_reg
     * @return object Sistole dan diastole dalam format (object)['sistole' => value, 'diastole' => value]
     */
    public function getBloodPressure($kode_reg)
    {
        $vitalSign = DB::connection('sqlsrvemr')
            ->table('TAC_RJ_VITAL_SIGN')
            ->select('FS_TD as sistole', 'FS_TD2 as diastole')
            ->where('FS_KD_REG', $kode_reg)
            ->orderByDesc('FS_KD_REG') // TOP 1 digantikan dengan orderBy + first()
            ->first();

        return (object)[
            'sistole' => $vitalSign->sistole ?? 0, // Default 0 jika tidak ada data
            'diastole' => $vitalSign->diastole ?? 0 // Default 0 jika tidak ada data
        ];
    }

    /**
     * Process bridgingDataProcess by no_sep
     * 
     * @param string $no_sep
     */
    public function bridgingDataIDRG($no_sep)
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
        $update_claim = $this->bridgingReEditClaim($no_sep);
        if ($update_claim->status != 'ok') {
            return $update_claim;
        }

        // buat new claim dulu
        $new_claim = $this->bridgingNewClaimProcess(
            $transaksi_utama->FMNO_KARTU,
            $transaksi_utama->FMNOSEP,
            $transaksi_utama->FRPPASIEN_ID,
            $transaksi_utama->NAMAPASIEN,
            $transaksi_utama->TGL_LAHIR,
            $transaksi_utama->JENIS_KELAMIN,
        );
        if ($new_claim->status != 'ok') {
            return $new_claim;
        }

        $bloodPresure = $this->getBloodPressure($transaksi_utama->FRPNOTRANSAKSI);
        $discharge_status = $this->dischargeStatusEMR($transaksi_utama->FRPNOTRANSAKSI);

        $is_pasien_tb = false;
        $diagnosa = $this->getAllDiagnosaIDRG($transaksi_utama->FMNOSEP);
        $procedure = $this->getAllProcedureIDRG($transaksi_utama->FMNOSEP);

        $diagnosaArray = explode("#", $diagnosa);
        foreach ($diagnosaArray as $d) {
            if (preg_match('/^A1[5-9]/', $d)) {
                $is_pasien_tb = true;
                break;
            }
        }

        $user = Auth::user();
        // mapping data
        $data = (object)[
            'nomor_sep' => $no_sep,
            'tgl_masuk' => Carbon::parse($transaksi_utama->FRPTGL)->format('Y-m-d H:i:s'),
            'tgl_pulang' => Carbon::parse($transaksi_utama->FRPTGL)->format('Y-m-d H:i:s'),
            'jenis_rawat' => $transaksi_utama->FMJENISRAWAT, // 1 ranap, 2 rajal, 3 igd
            'kelas_rawat' => 3, // kelas rawat BPJS 3 regular. 1 eksekusi
            'birth_weight' => ($transaksi_utama->BERAT_LAHIR) ? $transaksi_utama->BERAT_LAHIR : "",
            'discharge_status' => $discharge_status,
            'tarif_rs' => $this->getTotalDetailTarifTransaksi($semua_transaksi)->tarif_rs,
            'tarif_poli_eks' => $this->getTotalDetailTarifTransaksi($semua_transaksi)->tarif_poli_eks,
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

        foreach ($semua_transaksi as $transaksi) {
            $dializer = DB::connection('sqlsrvemr')
                ->table('TAC_HD_DIALISER')
                ->select('FS_TIPE_DIALISER')
                ->where('FS_KD_REG', $transaksi->FRPNOTRANSAKSI)
                ->first();

            if ($dializer) {
                $data->dializer_single_use = ($dializer->FS_TIPE_DIALISER == 1) ? 1 : 0;
                break;
            }
        }

        $requestData = json_encode((object)[
            'metadata' => (object)[
                'method' => 'set_claim_data',
                'nomor_sep' => $no_sep,
            ],
            'data' => $data
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $key = $user->eklaim_key;
        $response =  sendRequest($key, $requestData);

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
        $now = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');

        $code_grouping_1_idrg = $grouping_1_idrg->response->metadata->code ?? null;
        if ($code_grouping_1_idrg != 200) {
            return $grouping_1_idrg;
        }

        Log::info('grouping_1_idrg Rujukan ');
        Log::info(json_encode($grouping_1_idrg));

        try {
            DB::connection('sqlsrvsimrs')
                ->table('PASIEN_IDRG')
                ->updateOrInsert(
                    [
                        'no_sep' => $no_sep,
                        'pasien_id' => $transaksi_utama->FRPPASIEN_ID
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
                "action_id" => 18,
                "user_email" => $user->email,
                "user_id" => $user->id,
                "created_at" => $now,
                "data" => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('bridgingDataIDRG: ' . $e->getMessage());
            return false;
        }

        return $response;
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
        $now = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        // Data request
        $data = json_encode([
            "metadata" => ["method" => "idrg_grouper_final"],
            "data" => ["nomor_sep" => $no_sep]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $response = sendRequest($key, $data);
        if ($response->response->metadata->code == 200) {
            try {
                $affected = DB::connection('sqlsrvsimrs')
                    ->table('PASIEN_IDRG')
                    ->where('no_sep', $no_sep)
                    ->where('pasien_id', $transaksi_utama->FRPPASIEN_ID)
                    ->update([
                        'is_final' => 1,
                        'updated_at' => $now,
                        'updated_by' => $user->email,
                    ]);

                if ($affected == 0) {
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
     * Process bridgingEditUlangIDRG by no_sep
     * 
     * @param string $no_sep
     */
    public function bridgingEditUlangIDRG($no_sep)
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
        $now = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        // Data request
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
                    ->where('pasien_id', $transaksi_utama->FRPPASIEN_ID)
                    ->update([
                        'is_final' => 0,
                        'updated_at' => $now,
                        'updated_by' => $user->email,
                    ]);

                DB::connection('sqlsrvsimrs')
                    ->table('PASIEN_INACBG')
                    ->where('no_sep', $no_sep)
                    ->where('pasien_id', $transaksi_utama->FRPPASIEN_ID)
                    ->delete();

                if ($affected == 0) {
                    return (object)[
                        "status" => "nok",
                        "error" => "data group idrg tidak ditemukan"
                    ];
                }

                $this->auditTrail->insert([
                    "object_id" => $transaksi_utama->FRPNOTRANSAKSIKJ,
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
     * 
     * @param string $no_sep
     */
    public function bridgingGroupingInaStageSatu($no_sep)
    {
        $semua_transaksi = $this->allTransactionsBySep($no_sep);
        if (!$semua_transaksi || count($semua_transaksi) < 1) {
            return false;
        }

        $transaksi_utama = $semua_transaksi[0];
        foreach ($semua_transaksi as $transaksi) {
            if ($transaksi->RUBBER == 0) {
                $transaksi_utama = $transaksi;
                break;
            }
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
            "data" => ["diagnosa" => $this->getAllDiagnosa($semua_transaksi)]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        sendRequest($key, $data);

        $data_proc = json_encode([
            "metadata" => [
                "method" => "inacbg_procedure_set",
                "nomor_sep" => $no_sep,
            ],
            "data" => ["procedure" => $this->getAllProcedure($semua_transaksi)]
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
            }

            DB::connection('sqlsrvsimrs')
                ->table('PASIEN_INACBG')
                ->updateOrInsert(
                    [
                        'no_sep' => $no_sep,
                        'pasien_id' => $transaksi_utama->FRPPASIEN_ID
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
        $semua_transaksi = $this->allTransactionsBySep($no_sep);
        if (!$semua_transaksi || count($semua_transaksi) < 1) {
            return false;
        }

        $transaksi_utama = $semua_transaksi[0];
        foreach ($semua_transaksi as $transaksi) {
            if ($transaksi->RUBBER == 0) {
                $transaksi_utama = $transaksi;
                break;
            }
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
            foreach ($semua_transaksi as $transaksi) {
                DB::connection('sqlsrvsimrs')
                    ->table('TRANSAKSIPASIEN')
                    ->where('FTNO_TRANSAKSI', $transaksi->FRPNOTRANSAKSIKJ)
                    ->update([
                        'FTKODEINACBG' => $cbg_code,
                        'FTTARIPINACBG' => (float) $tarif_inacbg,
                        'FKUNCI_VALIDASI2' => DB::raw('FKUNCI_VALIDASI2 + 1')
                    ]);
            }

            DB::connection('sqlsrvsimrs')
                ->table('PASIEN_INACBG')
                ->updateOrInsert(
                    [
                        'no_sep' => $no_sep,
                        'pasien_id' => $transaksi_utama->FRPPASIEN_ID
                    ],
                    [
                        "response_inacbg" => json_encode($grouping_2_inacbg->response->response_inacbg),
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
            Log::error("PasienRujukanEklaimRepository bridgingGroupingInaStageDua err: " . $e->getMessage());
        }

        DB::connection('sqlsrvsimrs')->commit();
        return $grouping_2_inacbg;
    }

    /**
     * Process bridgingFinalINACBG by no_sep
     * 
     * @param string $no_sep
     */
    public function bridgingFinalINACBG($no_sep)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;
        $now = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        DB::connection('sqlsrvsimrs')->beginTransaction();

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
            "metadata" => ["method" => "inacbg_grouper_final"],
            "data" => ["nomor_sep" => $no_sep]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $responseFinal = sendRequest($key, $data);
        if ($responseFinal->response->metadata->code == 200) {
            try {
                DB::connection('sqlsrvsimrs')
                    ->table('PASIEN_INACBG')
                    ->where('no_sep', $no_sep)
                    ->where('pasien_id', $transaksi_utama->FRPPASIEN_ID)
                    ->update([
                        'is_final' => 1,
                        'updated_at' => $now,
                        'updated_by' => $user->email,
                    ]);

                $this->auditTrail->insert([
                    "object_id" => $transaksi_utama->FMNOSEP,
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
                Log::error('bridgingFinalINACBG final inacbg: ' . $e->getMessage());
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
     * Process bridgingEditUlangINACBG by no_sep
     * 
     * @param string $no_sep
     */
    public function bridgingEditUlangINACBG($no_sep)
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
                    ->where('pasien_id', $transaksi_utama->FRPPASIEN_ID)
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
                Log::error('bridgingFinalIDRG err: ' . $e->getMessage());
                return (object)[
                    "status" => "nok",
                    "error" => "Lihat Log"
                ];
            }
        }
        return $response;
    }

    public function bridgingFinalKlaim($no_sep)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;
        $now = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        DB::connection('sqlsrvsimrs')->beginTransaction();

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
            "data" => [
                "nomor_sep" => $no_sep,
                "coder_nik" => $user->nik,
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $responseFinal = sendRequest($key, $data);
        if ($responseFinal->response->metadata->code == 200) {
            try {
                DB::connection('sqlsrvsimrs')
                    ->table('PASIEN_INACBG')
                    ->where('no_sep', $no_sep)
                    ->where('pasien_id', $transaksi_utama->FRPPASIEN_ID)
                    ->update([
                        'is_final_claim' => 1,
                        'updated_at' => $now,
                        'updated_by' => $user->email,
                    ]);

                foreach ($semua_transaksi as $transaksi) {
                    DB::connection('sqlsrvsimrs')
                        ->table('TRANSAKSIPASIEN')
                        ->where('FTNO_TRANSAKSI', $transaksi->FRPNOTRANSAKSIKJ)
                        ->update([
                            'FKUNCI_VALIDASI' => 1,
                        ]);

                    DB::connection('sqlsrvsimrs')
                        ->table('PASIEN_RUJUKAN')
                        ->where('FRPNOTRANSAKSI', $transaksi->FRPNOTRANSAKSI)
                        ->update([
                            'IS_INACBG_FINAL' => 1,
                        ]);
                }

                $this->auditTrail->insert([
                    "object_id" => $no_sep,
                    "action_id" => 24,
                    "user_email" => $user->email,
                    "user_id" => $user->id,
                    "created_at" => $now,
                    "data" => [
                        "nomor_sep" => $no_sep,
                    ],
                ]);
            } catch (\Exception $e) {
                DB::connection('sqlsrvsimrs')->rollBack();
                Log::error('bridgingFinalKlaim Final klaim err: ' . $e->getMessage());
                return (object)[
                    "status" => "nok",
                    "error" => "Lihat Log"
                ];
            }
        }
        DB::connection('sqlsrvsimrs')->commit();
        return $responseFinal;
    }

    public function bridgingReeditKlaim($no_sep)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;
        $now = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        DB::connection('sqlsrvsimrs')->beginTransaction();

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
                    ->where('pasien_id', $transaksi_utama->FRPPASIEN_ID)
                    ->update([
                        'is_final_claim' => null,
                        'updated_at' => $now,
                        'updated_by' => $user->email,
                    ]);

                foreach ($semua_transaksi as $transaksi) {
                    DB::connection('sqlsrvsimrs')
                        ->table('TRANSAKSIPASIEN')
                        ->where('FTNO_TRANSAKSI', $transaksi->FRPNOTRANSAKSIKJ)
                        ->update([
                            'FKUNCI_VALIDASI' => 0,
                        ]);

                    DB::connection('sqlsrvsimrs')
                        ->table('PASIEN_RUJUKAN')
                        ->where('FRPNOTRANSAKSI', $transaksi->FRPNOTRANSAKSI)
                        ->update([
                            'IS_INACBG_FINAL' => 0,
                        ]);
                }

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
                Log::error('bridgingReeditKlaim Reedit Kalim err: ' . $e->getMessage());
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
     * Process bridgingKirimKlaimIndividualProcess by no_sep
     * 
     * @param string $no_sep
     */
    public function bridgingKirimKlaimIndividualProcess($no_sep)
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
     * Process bridgingGetClaimData by no_sep
     * 
     * @param string $no_sep
     */
    public function bridgingGetClaimData($no_sep)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;

        // Data request
        $data = json_encode([
            "metadata" => ["method" => "get_claim_data"],
            "data" => ["nomor_sep" => $no_sep]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $response = sendRequest($key, $data);
        return $response;
    }

    /**
     * Process searchDiagnosis by keyword
     * 
     * @param string $keyword
     */
    public function searchDiagnosis($keyword)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;

        // Data request
        $data = json_encode([
            "metadata" => ["method" => "search_diagnosis"],
            "data" => ["keyword" => $keyword]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $response = sendRequest($key, $data);
        return $response;
    }

    /**
     * Process searchProcedure by keyword
     * 
     * @param string $keyword
     */
    public function searchProcedure($keyword)
    {
        $user = Auth::user();
        $key = $user->eklaim_key;

        // Data request
        $data = json_encode([
            "metadata" => ["method" => "search_procedures"],
            "data" => ["keyword" => $keyword]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $response = sendRequest($key, $data);
        return $response;
    }

    /**
     * Process dischargeStatusEMR
     */
    public function dischargeStatusEMR($kode_reg)
    {
        if (!str_starts_with($kode_reg, 'RGD')) {
            // jika bukan IGD
            $cara_pulang = DB::connection('sqlsrvemr')
                ->table('TAC_RJ_MEDIS')
                ->leftJoin('MASTER_CARA_PULANG AS master', 'master.ID_CARA_PULANG', '=', 'TAC_RJ_MEDIS.FS_CARA_PULANG')
                ->select('master.code_bpjs')
                ->where('FS_KD_REG', $kode_reg)
                ->orderByDesc('mdd')
                ->orderByDesc('FS_JAM_TRS')
                ->first();
        } else {
            // jika IGD
            $cara_pulang = DB::connection('sqlsrvemr')
                ->table('TAC_IGD_MEDIS')
                ->leftJoin('TAC_IGD_PLH_CARA_PULANG_RTL AS master', 'master.ID', '=', 'TAC_IGD_MEDIS.CARA_PULANG')
                ->select('master.code_bpjs')
                ->where('KD_REG', $kode_reg)
                ->orderByDesc('created_at')
                ->first();
        }

        if ($cara_pulang) {
            $cara_pulang = $cara_pulang->code_bpjs ?? 1;
            return $cara_pulang;
        }
        return 1;
    }
}
