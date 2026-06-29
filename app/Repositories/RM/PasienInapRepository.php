<?php

// app/Repositories/PasienInapRepository.php
namespace App\Repositories\RM;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Bpjs\Bridging\Vclaim\BridgeVclaim;
use App\Repositories\RM\RMAuditTrail;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class PasienInapRepository
{
    protected $auditTrail;

    public function __construct()
    {
        $this->auditTrail = new RMAuditTrail();
    }

    /**
     * Get the list of pasien inap based on no_rm
     * 
     * @param string $no_rm
     * @return \Illuminate\Support\Collection
     */
    public function getPasienInaps($no_rm)
    {
        $data =  DB::connection('sqlsrvsimrs')
            ->table('TRANSAKSIPASIENINAP AS TPI')
            ->join('PASIENRAWATINAP AS PRI', function ($join) {
                $join->on(DB::raw('CAST(PRI.PRWINO_TRANSAKSI AS NVARCHAR)'), '=', 'TPI.FTNO_TRANSAKSI')
                    ->whereRaw('CAST(PRI.PRWINO_URUT AS NVARCHAR) = CAST(TPI.FTNO_URUT AS NVARCHAR)');
            })
            ->leftJoin('SPESIALISASI AS S', 'PRI.PRWIKD_SPECIAL', '=', 'S.FMSPESIALISASI_ID')
            ->leftJoin('KAMAR_KELAS AS KK', 'PRI.PRWIKD_KELAS', '=', 'KK.FMKKODEKLAS')
            ->leftJoin('KAMAR AS K', 'PRI.PRWIKD_KAMAR', '=', 'K.FMKKAMAR_ID')
            ->leftJoin('DOKTER AS DR', 'PRI.PRWIKD_DOKTER', '=', 'DR.FMDDOKTER_ID')
            ->select(
                'TPI.*',
                'PRI.PRWITGL_MASUK',
                'PRI.PRWITGL_KELUAR',
                'KK.FMKKAMARN',
                'K.FMKNAMA_KAMAR',
                'S.FMSPESIALISASIN',
                'PRI.PRWIKD_DOKTER',
                'PRI.PRWIKD_CUSTOMER',
                'DR.FMDDOKTERN',
            )
            ->where('TPI.FTKD_PASIEN', $no_rm)
            ->orderBy('TPI.FTTGL_TRANSAKSI', 'desc')
            ->get();

        return $data;
    }

    /**
     * Get the list of pasien inap semuanya
     * 
     */
    public function getAllPasienInaps(
        $tanggal_masuk,
        $page,
        $per_page,
        $kode_dokter = null,
        $no_rm = null,
        $tanggal_keluar = null,
        $kode_bangsal = null,
        $is_inacbg_final = null
    ) {
        $baseQuery = DB::connection('sqlsrvsimrs')
            ->table('TRANSAKSIPASIENINAP AS TPI')
            ->join('PASIENRAWATINAP AS PRI', function ($join) {
                $join->on(DB::raw('CAST(PRI.PRWINO_TRANSAKSI AS NVARCHAR)'), '=', 'TPI.FTNO_TRANSAKSI')
                    ->whereRaw('CAST(PRI.PRWINO_URUT AS NVARCHAR) = CAST(TPI.FTNO_URUT AS NVARCHAR)');
            })
            ->leftJoin('PASIEN AS P', 'TPI.FTKD_PASIEN', '=', 'P.KD_PASIEN')
            ->leftJoin('KAMAR AS K', 'PRI.PRWIKD_KAMAR', '=', 'K.FMKKAMAR_ID')
            ->leftJoin('DOKTER AS DR', 'PRI.PRWIKD_DOKTER', '=', 'DR.FMDDOKTER_ID')
            ->leftJoin('BPJS_SEP', 'BPJS_SEP.FMNOTRANSAKSI', '=', 'TPI.FTNO_TRANSAKSI')
            ->when($kode_bangsal, function ($query, $kode_bangsal) {
                return $query->where('K.FMKKAMARINDUK', $kode_bangsal);
            })
            ->when($tanggal_masuk, function ($query, $tanggal_masuk) {
                return $query->whereDate('TPI.FTTGL_TRANSAKSI', $tanggal_masuk);
            })
            ->when($kode_dokter, function ($query, $kode_dokter) {
                return $query->where('PRI.PRWIKD_DOKTER', $kode_dokter);
            })
            ->when($no_rm, function ($query, $no_rm) {
                return $query->where('TPI.FTKD_PASIEN', $no_rm);
            })
            ->when($tanggal_keluar, function ($query, $tanggal_keluar) {
                return $query->whereDate('PRI.PRWITGL_KELUAR', $tanggal_keluar);
            })
            ->when($is_inacbg_final, function ($query, $is_inacbg_final) {
                if ($is_inacbg_final == "final") {
                    return $query->where('TPI.FKUNCI_VALIDASI', 1);
                }
                return $query->where(function ($subQuery) {
                    $subQuery->whereNull('TPI.FKUNCI_VALIDASI')
                        ->orWhere('TPI.FKUNCI_VALIDASI', 0);
                });
            });

        $total = (clone $baseQuery)->count();

        $data = $baseQuery
            ->select(
                'BPJS_SEP.FMNOSEP AS no_sep',
                'P.NAMAPASIEN',
                'TPI.*',
                'K.FMKNAMA_KAMAR',
                'PRI.PRWIKD_DOKTER',
                'PRI.PRWIKD_CUSTOMER',
                'DR.FMDDOKTERN',
                'PRI.PRWITGL_KELUAR AS TGL_KELUAR',
                'TPI.FKUNCI_VALIDASI'
            )
            ->orderBy('PRI.PRWITGL_KELUAR', 'asc')
            ->limit($per_page)
            ->offset(($page - 1) * $per_page)
            ->get();

        return [
            'total' => $total,
            'data' => $data,
        ];
    }


    /**
     * Count the number of pasien inap
     * 
     * @return int
     */
    public function countPasienInap()
    {
        return DB::connection('sqlsrvsimrs')
            ->table('PASIEN_RUJUKAN')
            ->count();
    }

    /**
     * Get pasien inap detail based on kode_reg
     *
     * @param string $kode_reg
     * @return object|null
     */
    public function getPasienInapDetail($kode_reg)
    {
        $pasienInap = DB::connection('sqlsrvsimrs')
            ->table('TRANSAKSIPASIENINAP AS TPI')
            ->leftJoin('PASIENRAWATINAP AS PRI', function ($join) {
                $join->on('PRI.PRWINO_TRANSAKSI', '=', 'TPI.FTNO_TRANSAKSI')
                    ->on('TPI.FTNO_URUT', '=', 'PRI.PRWINO_URUT');
            })
            ->leftJoin('PASIEN', 'PRI.PRWIKD_PASIEN', '=', 'PASIEN.KD_PASIEN')
            ->leftJoin('DOKTER', 'PRI.PRWIKD_DOKTER', '=', 'DOKTER.FMDDOKTER_ID')
            ->leftJoin('SPESIALISASI', 'PRI.PRWIKD_SPECIAL', '=', 'SPESIALISASI.FMSPESIALISASI_ID')
            ->leftJoin('CUSTOMER', 'PRI.PRWIKD_CUSTOMER', '=', 'CUSTOMER.CUSID')
            ->leftJoin('MR_CARA_MASUK_BPJS AS cm', 'PRI.CARA_MASUK', '=', 'cm.KODE')
            ->leftJoin('MR_RUJUKAN_KELUAR AS rk', 'PRI.PRWIRUJUKLUAR', '=', 'rk.MRKODERUJUKAN')
            ->leftJoin('BPJS_SEP AS sep', 'PRI.PRWINO_TRANSAKSI', '=', 'sep.FMNOTRANSAKSI')
            ->select(
                'TPI.*',
                'PASIEN.BERAT_LAHIR AS BBL',
                'PASIEN.SITB',
                'sep.FMNOSEP',
                'PASIEN.NAMAPASIEN',
                'PASIEN.TGL_LAHIR',
                'PASIEN.GOL_DARAH',
                'PASIEN.JENIS_KELAMIN',
                'PASIEN.ALAMAT',
                'PRI.*',
                'CUSTOMER.NAME AS CUSTOMER_NAME',
                'DOKTER.FMDDOKTERN',
                'SPESIALISASI.FMSPESIALISASIN',
                'cm.KETERANGAN AS CARA_MASUK_BPJS',
                'rk.MRKODERUJUKANN AS RS_RUJUKAN_KELUAR',
                DB::raw("'ranap' as JENIS_RAWAT")
            )
            ->where('TPI.FTNO_TRANSAKSI', $kode_reg)
            ->orderBy('PRI.PRWITGL_MASUK', 'ASC')
            ->first();

        if (!$pasienInap) {
            return null;
        }

        $pasienInap->SUDAH_DIKREDIT = $this->SudahDiKredit($kode_reg);
        $pasienInap->IS_SEP_VALID = false;
        if ($pasienInap->FMNOSEP) {
            $bridging = new BridgeVclaim();

            try {
                $endpoint = 'SEP/' . $pasienInap->FMNOSEP;
                $vclaim_detail = json_decode($bridging->getRequest($endpoint));
            } catch (\Exception $e) {
                Log::error("PasienInapRepository getPasienInapDetail Vclaim Err get SEP: " . $e->getMessage());
                return null;
            }

            if ($vclaim_detail->response && $vclaim_detail->response->peserta->noMr == $pasienInap->PRWIKD_PASIEN) {
                $pasienInap->IS_SEP_VALID = true;
            } else {
                $pasienInap->IS_SEP_VALID = false;
            }
        }

        return $pasienInap;
    }

    /**
     * Get SEP dari pasien inap BPJS detail based on kode_reg 
     *
     * @param string $kode_reg
     * @return object|null
     */
    public function getSepPasienInap($kode_reg)
    {
        try {
            return DB::connection('sqlsrvsimrs')
                ->table('BPJS_SEP')
                ->select('FMNOSEP', 'FMKODEKELAS')
                ->where('BPJS_SEP.FMNOTRANSAKSI', $kode_reg)
                ->first();
        } catch (\Exception $e) {
            Log::error("Err get SEP inap: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update nomer SEP dari pasien inap  BPJS detail based on kode_reg 
     *
     * @param string $kode_reg, $no_rm, $new_sep
     * @return object
     */
    public function updateNomerSepPasienInap($kode_reg, $no_rm, $new_sep, $kode_poli, $dpjp)
    {
        $pasienInap =  DB::connection('sqlsrvsimrs')
            ->table('TRANSAKSIPASIENINAP')
            ->leftJoin('BPJS_SEP AS sep', 'TRANSAKSIPASIENINAP.FTNO_TRANSAKSI', '=', 'sep.FMNOTRANSAKSI')
            ->select(
                'sep.FMNOSEP'
            )
            ->where('TRANSAKSIPASIENINAP.FTNO_TRANSAKSI', $kode_reg)
            ->first();

        if ($pasienInap) {
            if ($pasienInap->FMNOSEP) {
                $diagnosa = DB::connection('sqlsrvsimrs')
                    ->table('PASIEN_DIAGNOSA_IM')
                    ->where('no_sep', '=', $pasienInap->FMNOSEP)
                    ->pluck('code')
                    ->toArray();

                $procedures = DB::connection('sqlsrvsimrs')
                    ->table('PASIEN_TINDAKAN_IM')
                    ->where('no_sep', '=', $pasienInap->FMNOSEP)
                    ->select('code')
                    ->get();
                if (count($diagnosa) > 0 || count($procedures) > 0) {
                    return [
                        "status" => "nok",
                        "message" => "Untuk mengganti SEP hapus dulu diagnosa/prosedur dari SEP sebelumnya."
                    ];
                }
            }
        }

        $bridging = new BridgeVclaim();
        try {
            $endpoint = 'SEP/' . $new_sep;
            $response = json_decode($bridging->getRequest($endpoint));
            // Menghindari error jika response kosong
            $detail_pasien_vclaim = optional($response->response);
            $peserta = optional($detail_pasien_vclaim->peserta);

            // Validasi data dari API
            if (!$peserta->noMr) {
                Log::error("Response dari VClaim tidak valid atau kosong.");
                return [
                    "status" => "nok",
                    "message" => "Data SEP tidak ditemukan"
                ];
            }

            // Mengecek apakah nomor RM sesuai
            if ($peserta->noMr !== $no_rm) {
                return [
                    "status" => "nok",
                    "message" => "Nomor RM tidak cocok, lihat di VClaim"
                ];
            }

            // Ambil data dari response API
            $nomer_kartu   = $peserta->noKartu;
            $jenis_kelamin = $peserta->kelamin;
            $tgl_lahir     = $peserta->tglLahir;
            $hak_kelas     = optional($detail_pasien_vclaim->klsRawat)->klsRawatHak;
            $nama          = $peserta->nama;
            $tanggal_sep   = $detail_pasien_vclaim->tglSep;
        } catch (\Exception $e) {
            Log::error("Error BridgeVclaim: " . $e->getMessage());
            return [
                "status" => "nok",
                "message" => "Gagal mendapatkan data SEP dari VClaim"
            ];
        }

        if ($detail_pasien_vclaim->jnsPelayanan != "Rawat Inap") {
            return [
                "status" => "nok",
                "message" => "Gagal, SEP bukan rawat inap"
            ];
        }

        // Mulai transaksi database
        DB::connection('sqlsrvsimrs')->beginTransaction();
        try {
            // Cari apakah salah satu FMNOTRANSAKSI sudah ada
            $existingRecord = DB::connection('sqlsrvsimrs')
                ->table('BPJS_SEP')
                ->where('FMNOTRANSAKSI', $kode_reg)
                ->first();

            // $diagnosa = optional(app()->call([$this, 'getDiagnosaUtamaPasienInap'], ['kode_reg' => $kode_reg]))->MRPKD_PENYAKIT ?? null;

            // if (!$diagnosa) {
            //     return [
            //         "status" => "nok",
            //         "message" => "Belum ada diagnosa, ganti nomer sep dari aplikasi ranap"
            //     ];
            // }

            if ($existingRecord) {
                // Jika sudah ada, update berdasarkan FMNOTRANSAKSI yang ditemukan
                DB::connection('sqlsrvsimrs')
                    ->table('BPJS_SEP')
                    ->where('FMNOTRANSAKSI', $existingRecord->FMNOTRANSAKSI)
                    ->update([
                        'FMNOSEP'         => $new_sep,
                        'FMTGL_SEP'       => date('Y-m-d H:i:s', strtotime($tanggal_sep)),
                        'FMNO_KARTU'      => $nomer_kartu,
                        'FMPASIEN_ID'     => $no_rm,
                        'FMJENIS_KELAMIN' => $jenis_kelamin,
                        'FMNAMA_PESERTA'  => $nama,
                        'FMJENISRAWAT'    => '1',
                        'FMKODEKELAS'     => $hak_kelas,
                        'FMTGL_LAHIR'     => date('Y-m-d H:i:s', strtotime($tgl_lahir)),
                        'FMPOLYN'         => $kode_poli,
                        'dpjpn'           => $dpjp,
                        // 'FMDIAGNOSA'      => $diagnosa
                    ]);
            } else {
                // Jika tidak ada, lakukan insert
                DB::connection('sqlsrvsimrs')
                    ->table('BPJS_SEP')
                    ->insert([
                        'FMNOTRANSAKSI'   => $kode_reg,
                        'FMNOSEP'         => $new_sep,
                        'FMTGL_SEP'       => date('Y-m-d H:i:s', strtotime($tanggal_sep)),
                        'FMNO_KARTU'      => $nomer_kartu,
                        'FMPASIEN_ID'     => $no_rm,
                        'FMJENIS_KELAMIN' => $jenis_kelamin,
                        'FMNAMA_PESERTA'  => $nama,
                        'FMJENISRAWAT'    => '1',
                        'FMKODEKELAS'     => $hak_kelas,
                        'FMTGL_LAHIR'     => date('Y-m-d H:i:s', strtotime($tgl_lahir)),
                        'FMPOLYN'         => $kode_poli,
                        'dpjpn'           => $dpjp,
                        // 'FMDIAGNOSA'      => $diagnosa
                    ]);
            }

            // Commit transaksi
            DB::connection('sqlsrvsimrs')->commit();

            return [
                "status" => "ok",
                "message" => "Update Nomer SEP inap berhasil"
            ];
        } catch (\Exception $e) {
            DB::connection('sqlsrvsimrs')->rollBack();
            Log::error("Error update BPJS_SEP inap: " . $e->getMessage());

            return [
                "status" => "nok",
                "message" => "Terjadi kesalahan saat memperbarui data SEP inap"
            ];
        }
    }

    /**
     * Get diagnosa utama pasien by kode_reg
     *
     * @param string $kode_reg
     * @return object|null
     */
    public function getDiagnosaUtamaPasienInap($kode_reg)
    {
        try {
            return DB::connection('sqlsrvsimrs')
                ->table('MR_PENYAKIT')
                ->select('MRPKD_PENYAKIT')
                ->where('MRPSTAT_DIAG', 5)
                ->where('MRPNO_TRANSAKSI', $kode_reg)
                ->first();
        } catch (\Exception $e) {
            Log::error("getDiagnosaUtamaPasienInap: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get aktual keadaan keluar rs dari setiap pasien di tabel MR_KEMATIAN
     *
     * @param string $no_transaksi
     * @return \Illuminate\Support\Collection
     */
    public function getKeadaanKeluarByTransaksi($no_transaksi)
    {
        return DB::connection('sqlsrvsimrs')
            ->table('MR_KEMATIAN AS a')
            ->join('MR_KEADAAN_KELUAR_RS AS b', 'a.MRKKEADAAN_KELUAR', '=', 'b.FMKKRSKODE')
            ->select('a.*', 'b.FMKKRSKETERANGAN')
            ->where('a.MRKNO_TRANSAKSI', $no_transaksi)
            ->first();
    }

    /**
     * Get aktual kunjungan pasien dari setiap pasien di tabel KUNJUNGANPASIEN
     *
     * @param string $no_transaksi
     * @return \Illuminate\Support\Collection
     */
    public function getKunjunganPasienByTransaksi($no_transaksi)
    {
        return DB::connection('sqlsrvsimrs')
            ->table('KUNJUNGANPASIEN AS a')
            ->select('a.*')
            ->where('a.KPNO_TRANSAKSI', $no_transaksi)
            ->first();
    }

    /**
     * Get diagnosa penyakit by transaksi (MR_PENYAKIT)
     *
     * @param string $no_transaksi
     * @return \Illuminate\Support\Collection
     */
    public function getDiagnosaByTransaksi($no_transaksi)
    {
        return DB::connection('sqlsrvsimrs')
            ->table('MR_PENYAKIT')
            ->join('PENYAKIT', 'MR_PENYAKIT.MRPKD_PENYAKIT', '=', 'PENYAKIT.KD_PENYAKIT')
            ->orderBy('MR_PENYAKIT.MRPURUT_MASUK', 'ASC')
            ->select('MR_PENYAKIT.*', 'PENYAKIT.PENYAKIT')
            ->where('MR_PENYAKIT.MRPNO_TRANSAKSI', $no_transaksi)
            ->get();
    }

    /**
     * Search penyakit in PENYAKIT table with a query
     * 
     * @param string $searchTerm
     * @param int $page
     * @return \Illuminate\Support\Collection
     */
    public function searchPenyakit($searchTerm, $page)
    {
        return DB::connection('sqlsrvsimrs')
            ->table('PENYAKIT')
            ->select('PENYAKIT.*')
            ->when($searchTerm, function ($query) use ($searchTerm) {
                return $query->whereRaw(
                    "REPLACE(PENYAKIT.KD_PENYAKIT, '.', '') like ?",
                    ['%' . str_replace('.', '', $searchTerm) . '%']
                )
                    ->orWhereRaw(
                        "REPLACE(PENYAKIT.PENYAKIT, '.', '') like ?",
                        ['%' . str_replace('.', '', $searchTerm) . '%']
                    );
            })
            ->skip(($page - 1) * 20) // Skip based on current page
            ->take(20) // Limit results per page
            ->get();
    }

    /**
     * Save diagnosa for pasien inap
     * 
     * @param array $data
     * @return boolean
     */
    public function saveDiagnosa($data)
    {
        $user = Auth::user();
        $no_transaksikj = $data['no_transaksikj'];
        $now = now();
        $tgl_masuk = $data['tgl_masuk']; // Already parsed to a Carbon instance

        // Get the latest MRPURUT_MASUK value to generate next
        $lastUrutMasuk = DB::connection('sqlsrvsimrs')
            ->table('MR_PENYAKIT')
            ->where('MRPNO_TRANSAKSI', $no_transaksikj)
            ->orderBy('MR_PENYAKIT.MRPURUT_MASUK', 'desc')
            ->limit(1)
            ->value('MRPURUT_MASUK');

        $no_urut_masuk = $lastUrutMasuk ? $lastUrutMasuk + 1 : 1;

        // Prepare data to insert into MR_PENYAKIT table
        $data_to_save = [
            'MRPKD_PENYAKIT' => $data['icd10_code'],
            'MRPNO_TRANSAKSI' => $no_transaksikj,
            'MRPKD_PASIEN' => $data['no_rm'],
            'MRPKD_UNIT' => $data['kd_unit'],
            'MRPTGL_MASUK' => $tgl_masuk,
            'MRPURUT_MASUK' => $no_urut_masuk,
            'MRPJENIS' => 'RI', // Adjust if needed
            'MRPSTAT_DIAG' => $data['status_diagnosa'],
            'MRPKASUS' => $data['kasus'],
            'USER_ID' => $data['user_id'], // Assuming user ID is passed
            'UPDATE_DT' => $now,
        ];

        DB::connection('sqlsrvsimrs')->beginTransaction();
        try {
            // Insert data into MR_PENYAKIT table
            DB::connection('sqlsrvsimrs')
                ->table('MR_PENYAKIT')
                ->insert($data_to_save);

            // Insert audit trail
            $isrecorded = $this->auditTrail->insert([
                "object_id" => $no_transaksikj,
                "action_id" => 1, // Adjust the action_id as per your action mapping
                "user_email" => $user->email,
                "user_id" => $user->id,
                "created_at" => $now,
                "data" => $data_to_save,
            ]);

            if (!$isrecorded) {
                Log::error("saveDiagnosaRanap audittrail error");
                DB::connection('sqlsrvsimrs')->rollBack();
                return false;
            }
        } catch (\Exception $e) {
            DB::connection('sqlsrvsimrs')->rollBack();
            Log::error("saveDiagnosaRanap error: " . $e->getMessage());
            return false;
        }

        DB::connection('sqlsrvsimrs')->commit();
        return true;
    }

    /**
     * Delete diagnosa by ID from MR_PENYAKIT table
     * 
     * @param int $id
     * @return boolean
     */
    public function deleteDiagnosaById($id)
    {
        $user = Auth::user();
        $conn = DB::connection('sqlsrvsimrs');

        try {
            // Mulai transaksi
            $conn->beginTransaction();

            // Ambil data diagnosa sebelum dihapus (untuk audit trail)
            $deletedDiagnosa = $conn
                ->table('MR_PENYAKIT')
                ->where('ID', $id)
                ->first();

            if (!$deletedDiagnosa) {
                return false;
            }

            // Hapus data
            $deleted = $conn
                ->table('MR_PENYAKIT')
                ->where('ID', $id)
                ->delete();

            if (!$deleted) {
                $conn->rollBack();
                return false;
            }

            // Catat audit trail
            $auditSuccess = $this->auditTrail->insert([
                "object_id"  => $deletedDiagnosa->MRPNO_TRANSAKSI,
                "action_id"  => 2,
                "user_email" => $user->email,
                "user_id"    => $user->id,
                "created_at" => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                "data"       => $deletedDiagnosa,
            ]);

            if (!$auditSuccess) {
                Log::error("DiagnosaRanapRepository deleteDiagnosaById error: gagal simpan audittrail");
                $conn->rollBack();
                return false;
            }

            // Jika semuanya sukses
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            Log::error("DiagnosaRanapRepository deleteDiagnosaById error: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Get procedure penyakit by transaksi (MR_TINDAKAN)
     *
     * @param string $no_transaksi
     * @return \Illuminate\Support\Collection
     */
    public function getProcedureByTransaksi($no_transaksi)
    {
        return DB::connection('sqlsrvsimrs')
            ->table('MR_TINDAKAN')
            ->select('MR_TINDAKAN.*', 'MR_ICD9.FMI9KETERANGAN')
            ->join('MR_ICD9', 'MR_TINDAKAN.MRTKD_TINDAKAN', '=', 'MR_ICD9.FMI9KODE')
            ->orderBy('MR_TINDAKAN.MRTURUT_MASUK', 'ASC')
            ->where('MR_TINDAKAN.MRTNOTRANSAKSI', $no_transaksi)
            ->get();
    }

    /**
     * Search procedure in MR_ICD9 table with a query
     * 
     * @param string $searchTerm
     * @param int $page
     * @return \Illuminate\Support\Collection
     */
    public function searchProcedure($searchTerm, $page)
    {
        return DB::connection('sqlsrvsimrs')
            ->table('MR_ICD9')
            ->select('MR_ICD9.*')
            ->when($searchTerm, function ($query) use ($searchTerm) {
                $searchTermWithoutDot = str_replace('.', '', $searchTerm); // Menghapus titik dari search term
                return $query->whereRaw(
                    "REPLACE(MR_ICD9.FMI9KODE, '.', '') like ?",
                    ['%' . $searchTermWithoutDot . '%']
                )
                    ->orWhereRaw(
                        "REPLACE(MR_ICD9.FMI9KETERANGAN, '.', '') like ?",
                        ['%' . $searchTermWithoutDot . '%']
                    );
            })
            ->skip(($page - 1) * 20) // Skip based on current page
            ->take(20) // Limit results per page
            ->get();
    }

    /**
     * Save procedure for pasien inap
     * 
     * @param array $data
     * @return boolean
     */
    public function saveProcedureRanap($data)
    {
        $no_transaksikj = $data['no_transaksikj'];
        $now = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
        $tgl_masuk = $data['tgl_masuk']; // Already parsed to a Carbon instance
        $user = Auth::user();

        // Get the latest MRTURUT_MASUK value to generate next
        $lastUrutMasuk = DB::connection('sqlsrvsimrs')
            ->table('MR_TINDAKAN')
            ->where('MRTNOTRANSAKSI', $no_transaksikj)
            ->orderBy('MR_TINDAKAN.MRTURUT_MASUK', 'desc')
            ->limit(1)
            ->value('MRTURUT_MASUK');

        $no_urut_masuk = $lastUrutMasuk ? $lastUrutMasuk + 1 : 1;

        $data_to_save = [
            'MRTKD_TINDAKAN' => $data['icd9_code'],
            'MRTNOTRANSAKSI' => $no_transaksikj,
            'MRTKD_PASIEN' => $data['no_rm'],
            'MRTKD_UNIT' => $data['kd_unit'],
            'MRTTGL_MASUK' => $tgl_masuk,
            'MRTURUT_MASUK' => $no_urut_masuk,
            'MRTTGL_TINDAKAN' => $now,
        ];

        $conn = DB::connection('sqlsrvsimrs');
        $conn->beginTransaction();

        try {
            $conn->table('MR_TINDAKAN')->insert($data_to_save);

            $isrecorded = $this->auditTrail->insert([
                "object_id"  => $no_transaksikj,
                "action_id"  => 3,
                "user_email" => $user->email,
                "user_id"    => $user->id,
                "created_at" => $now,
                "data"       => $data_to_save,
            ]);

            if (!$isrecorded) {
                Log::error("saveProcedureRanap: Gagal menyimpan audit trail");
                $conn->rollBack();
                return false;
            }

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            Log::error("saveProcedureRanap error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete procedure by ID from MR_TINDAKAN table
     * 
     * @param int $id
     * @return boolean
     */
    public function deleteProcedureById($id)
    {
        $user = Auth::user();
        $conn = DB::connection('sqlsrvsimrs');

        try {
            // Mulai transaksi
            $conn->beginTransaction();

            // Ambil data sebelum dihapus
            $deletedProcedure = $conn
                ->table('MR_TINDAKAN')
                ->where('ID', $id)
                ->first();

            if (!$deletedProcedure) {
                return false;
            }

            // Hapus data tindakan
            $deleted = $conn
                ->table('MR_TINDAKAN')
                ->where('ID', $id)
                ->delete();

            if (!$deleted) {
                $conn->rollBack();
                return false;
            }

            // Simpan ke audit trail
            $auditSuccess = $this->auditTrail->insert([
                "object_id"  => $deletedProcedure->MRTNOTRANSAKSI,
                "action_id"  => 4,
                "user_email" => $user->email,
                "user_id"    => $user->id,
                "created_at" => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                "data"       => $deletedProcedure,
            ]);

            if (!$auditSuccess) {
                Log::error("deleteProcedureByIdranap error: gagal simpan audittrail");
                $conn->rollBack();
                return false;
            }

            // Commit jika semua sukses
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            Log::error("deleteProcedureByIdranap error: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Get procedure penyakit by transaksi (MR_DIAGNOSA)
     *
     * @param string $no_transaksi
     * @return \Illuminate\Support\Collection
     */
    public function getMrDiagnosaByTransaksi($no_transaksi)
    {
        return DB::connection('sqlsrvsimrs')
            ->table('MR_DIAGNOSA')
            ->select('MR_DIAGNOSA.*')
            ->where('MR_DIAGNOSA.MRDNO_TRANSAKSI', $no_transaksi)
            ->first();
    }

    /**
     * Update catatan khusus in MR_DIAGNOSA table based on no_transaksi
     *
     * @param string $no_transaksi
     * @param string $catatan_khusus
     * @return \Illuminate\Http\Response
     */
    public function updateCatatanKhususByTransaksi($no_transaksi, $catatan_khusus)
    {
        try {
            // Update the MRCATATANKHUSUS field for the given no_transaksi
            $updated = DB::connection('sqlsrvsimrs')
                ->table('MR_DIAGNOSA')
                ->where('MRDNO_TRANSAKSI', $no_transaksi)
                ->update(['MRCATATANKHUSUS' => $catatan_khusus]);

            if ($updated) {
                return response()->json([
                    'status' => 'ok',
                    'message' => 'Catatan Khusus berhasil diperbarui',
                ]);
            } else {
                return response()->json([
                    'status' => 'nok',
                    'message' => 'Tidak ada data yang diubah. Pastikan no_transaksi valid.',
                ], 404);
            }
        } catch (\Exception $e) {
            // Log the error if any exception occurs
            Log::error('Error updating Catatan Khusus: ' . $e->getMessage());

            return response()->json([
                'status' => 'nok',
                'message' => 'Terjadi kesalahan saat memperbarui catatan khusus.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get opsi cara_masuk_bpjs dari untuk transaksi pasien
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCaraMasukBPJS()
    {
        return DB::connection('sqlsrvsimrs')
            ->table('MR_CARA_MASUK_BPJS')
            ->select('*')
            ->orderBy('ORDER')
            ->get();
    }

    /**
     * Get opsi keadaan keluar rs dari untuk transaksi pasien
     *
     * @return \Illuminate\Support\Collection
     */
    public function getKeadaanKeluarRS()
    {
        return DB::connection('sqlsrvsimrs')
            ->table('MR_KEADAAN_KELUAR_RS')
            ->orderBy('FMKKRSKODE_BPJS')
            ->select('*')
            ->get();
    }

    /**
     * Get opsi rs rujukan untuk keadaan keluar rs yang dirujuk
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRSRujukan()
    {
        return DB::connection('sqlsrvsimrs')
            ->table('MR_RUJUKAN_KELUAR')
            ->select('*')
            ->get();
    }

    public function updateKeperawatan(array $data)
    {
        $user = Auth::user();
        DB::connection('sqlsrvsimrs')->beginTransaction();
        try {
            DB::connection('sqlsrvsimrs')
                ->table('PASIENRAWATINAP')
                ->where('PRWINO_TRANSAKSI', $data['no_transaksi'])
                ->update([
                    'CARA_MASUK' => $data['cara_masuk'], // cara masuk standara BPJS opsi
                ]);

            DB::connection('sqlsrvsimrs')
                ->table('PASIEN')
                ->where('KD_PASIEN', $data['kode_pasien'])
                ->update([
                    'BERAT_LAHIR' => $data['berat_lahir'],
                    'SITB' => $data['sitb'],
                ]);

            // 4. Audit Trail
            $this->auditTrail->insert([
                'object_id'  => $data['no_transaksi'],
                'action_id'  => 8,
                'user_email' => $user->email,
                'user_id'    => $user->id,
                'created_at' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'data'       => [
                    'PASIENRAWATINAP' => [
                        'PRWINO_TRANSAKSI' => $data['no_transaksi'],
                        'CARA_MASUK' => $data['cara_masuk'],
                        'BERAT_LAHIR' => $data['berat_lahir'],
                        'SITB' => $data['sitb'],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            DB::connection('sqlsrvsimrs')->rollBack();
            Log::error('Error update/insert Cara masuk: ' . $e->getMessage());
            return false;
        }

        DB::connection('sqlsrvsimrs')->commit();
        return true;
    }

    /**
     * Get resume dokter by kode reg
     *
     * @param string $kode_reg
     * @return \Illuminate\Support\Collection
     */
    public function getResumeByTransaksi($kode_reg)
    {
        return DB::connection('sqlsrvsimrs')
            ->table('PKU.dbo.TAC_RI_MEDIS')
            ->select('*')
            ->where('FS_KD_REG', $kode_reg)
            ->orderByDesc('FS_KD_REG')
            ->first();
    }

    /**
     * Get hasil radiologi dokter by kode reg kj
     *
     * @param string $kode_reg_kj
     * @return \Illuminate\Support\Collection
     */
    public function getListHasilRadiologiByTransaksi($kode_reg_kj)
    {
        $hasil = [];
        $transactions = DB::connection('sqlsrvsimrs')
            ->table('TRANSAKSIPASIENINAPD AS A')
            ->select('A.*')
            ->where('A.FDTNO_TRANSAKSI', $kode_reg_kj)
            ->where('A.FDTKD_PRODUK', 'ADL004')
            ->get();

        foreach ($transactions as $transaction) {
            $hasil[] = DB::connection('sqlsrvsimrs')
                ->table('RAD_HASIL AS rad')
                ->select('rad.*')
                ->where('rad.MRHNO_TRANSAKSI', $transaction->FDTNO_FAKTUR)
                ->first();
        }
        return $hasil;
    }

    /**
     * Get list of berkas RM by kode reg
     *
     * @param string $kode_reg
     * @return \Illuminate\Support\Collection
     */
    public function getListBerkasRMByRg($kode_reg)
    {
        return DB::connection('sqlsrvsimrs')
            ->table('PKU.dbo.TAC_RM_BERKAS')
            ->select('*')
            ->where('FS_KD_REG', $kode_reg)
            ->orderByDesc('mdd')
            ->get();
    }

    /**
     * Get list of receipt all no faktur by kode_reg
     *
     * @param string $kode_reg
     * @return \Illuminate\Support\Collection
     */
    public function getListAllObatByTransaksi($kode_reg)
    {
        $inkota = DB::connection('sqlsrvsimrs')
            ->table('FJINKOTA')
            ->select('FHFJBUKTI_ID', 'FHFJDATE','FHFJDOKTERN')
            ->where('FHFJNO_TRANSAKSI', $kode_reg)
            ->orderByDesc('FHFJDATE')
            ->get();

        return $inkota->map(function ($data_detail) {
            $items = DB::connection('sqlsrvsimrs')
                ->table('FJINKOTAD')
                ->select('*')
                ->where('FDFJBUKTI_ID', $data_detail->FHFJBUKTI_ID)
                ->get();

            $data_detail->items = $items;

            return $data_detail;
        });
    }

    /**
     * Get list of laporan ok by kode reg
     *
     * @param string $kode_reg
     * @return \Illuminate\Support\Collection
     */
    public function getListLaporanOKByTransaksi($kode_reg)
    {
        $transaksi = DB::connection('sqlsrvsimrs')
            ->table('TRANSAKSIPASIEN')
            ->select('FTNO_TRANSAKSI')
            ->where('FTNO_TRANSAKSIINAP', $kode_reg)
            ->where('FTKD_UNIT', "PK002")
            ->get();

        // N+1: query ke OK_JADWAL per transaksi
        $jadwal_arr = [];
        foreach ($transaksi as $item) {
            $jadwal = DB::connection('sqlsrvsimrs')
                ->table('OK_JADWAL')
                ->select('*')
                ->where('FJOKNO_TRANSAKSI', $item->FTNO_TRANSAKSI)
                ->first();

            $jadwal_arr[] = [
                'FTNO_TRANSAKSI' => $item->FTNO_TRANSAKSI,
                'FJOKNO_JADWAL' => $jadwal ? $jadwal->FJOKNO_JADWAL : null,
                'FJOKKD_TINDAKAN' => $jadwal ? $jadwal->FJOKKD_TINDAKAN : null,
            ];
        }

        $laporan_ok_arr = [];
        foreach ($jadwal_arr as $jadwal) {
            $laporan_ok_arr[] = DB::connection('sqlsrvemr')
                ->table('TAC_RI_OK')
                ->select('*')
                ->where('FJOKNO_JADWAL', $jadwal['FJOKNO_JADWAL'])
                ->first();
        }

        return $laporan_ok_arr;
    }

    function finalPasienUmum($kode_reg)
    {
        $user = Auth::user();
        $now = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');

        DB::connection('sqlsrvsimrs')->beginTransaction();
        try {
            DB::connection('sqlsrvsimrs')
                ->table('TRANSAKSIPASIENINAP')
                ->where('FTNO_TRANSAKSI', $kode_reg)
                ->update([
                    'FKUNCI_VALIDASI' => 1,
                ]);

            $this->auditTrail->insert([
                "object_id" => $kode_reg,
                "action_id" => 7,
                "user_email" => $user->email,
                "user_id" => $user->id,
                "created_at" => $now,
                "data" => [
                    "kode_reg" => $kode_reg,
                ],
            ]);
        } catch (\Exception $e) {
            DB::connection('sqlsrvsimrs')->rollBack();
            Log::error("PasienInapRepository finalPasienUmum : " . $e->getMessage());
            return [
                "status" => "nok",
                "message" => "Terjadi kesalahan saat final data"
            ];
        }
        DB::connection('sqlsrvsimrs')->commit();
        return [
            "status" => "ok",
            "message" => "Sukses"
        ];
    }

    public function SudahDiKredit($kode_reg)
    {
        $exists = DB::connection('sqlsrvsimrs')
            ->table('TRANSAKSIPASIENINAPD')
            ->where('FDTNO_TRANSAKSI', $kode_reg)
            ->where('FDTJENISTRANSAKSI', 'KR')
            ->exists();

        return $exists;
    }
}
