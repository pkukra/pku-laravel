<?php

namespace App\Repositories\Casemix;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class RanapMonitRepository
{
    /**
     * Get the list of pasien ranap each bangsal based on bangsal_induk
     */
    public function getOrCountPasienRanap($bulan, $tahun, $bangsal_induk, $nomer_rm, $status, $perPage = null, $offset = null, $countOnly = false)
    {
        // Buat cache key unik berdasarkan parameter pencarian
        $cacheKey = "ranap:$bulan:$tahun:$bangsal_induk:$nomer_rm:$status:$perPage:$offset:$countOnly";

        // Ambil dari cache atau eksekusi query jika belum ada
        $data = Cache::remember($cacheKey, 300, function () use ($bulan, $tahun, $bangsal_induk, $nomer_rm, $status, $perPage, $offset, $countOnly) {
            $query = DB::connection('sqlsrv')
                ->table('TRANSAKSIPASIENINAP AS TPI')
                ->join('PASIENRAWATINAP AS PRI', function ($join) {
                    $join->on(DB::raw('CAST(PRI.PRWINO_TRANSAKSI AS NVARCHAR)'), '=', 'TPI.FTNO_TRANSAKSI')
                        ->whereRaw('CAST(PRI.PRWINO_URUT AS NVARCHAR) = CAST(TPI.FTNO_URUT AS NVARCHAR)');
                })
                ->leftJoin('PASIEN AS P', 'P.KD_PASIEN', '=', 'TPI.FTKD_PASIEN')
                ->leftJoin('DOKTER AS DR', 'DR.FMDDOKTER_ID', '=', 'PRI.PRWIKD_DOKTER')
                ->leftJoin('KAMAR AS K', 'K.FMKKAMAR_ID', '=', 'PRI.PRWIKD_KAMAR')
                ->leftJoin('BPJS_SEP AS SEP', 'SEP.FMNOTRANSAKSI', '=', 'TPI.FTNO_TRANSAKSI')

                ->whereRaw('MONTH(TPI.FTTGL_TRANSAKSI) = ?', [$bulan])
                ->whereRaw('YEAR(TPI.FTTGL_TRANSAKSI) = ?', [$tahun])
                ->where('K.FMKKAMARINDUK', $bangsal_induk)
                ->when($status === 'dirawat', fn($query) => $query->whereNull('PRI.PRWITGL_KELUAR'))
                ->when($status === 'sudah_pulang', fn($query) => $query->whereNotNull('PRI.PRWITGL_KELUAR'));

            if ($nomer_rm) {
                $query->where('TPI.FTKD_PASIEN', $nomer_rm);
            }

            if ($countOnly) {
                return $query->count();
            }

            return $query->select(
                'TPI.FTNO_TRANSAKSI',
                'PRI.PRWIKD_KAMAR',
                'PRI.PRWIKD_KELAS',
                'PRI.PRWIKD_DOKTER',
                'PRI.PRWITGL_KELUAR',
                'TPI.FTTGL_TRANSAKSI',
                'TPI.FTKD_PASIEN',
                'P.NAMAPASIEN',
                'DR.FMDDOKTERN AS DPJP',
                'FMKODEKELAS AS KELAS_RAWAT'
            )
                ->orderBy('TPI.FTTGL_TRANSAKSI', 'desc')
                ->offset($offset)
                ->limit($perPage)
                ->get();
        });

        // Jika hanya menghitung total data, langsung return hasilnya
        if ($countOnly) {
            return $data;
        }

        // Lakukan pemrosesan tambahan pada data
        return collect($data)->map(function ($data_detail) {
            // $data_detail->FS_DIAGNOSA = get_diagnosa_ri($data_detail->FTNO_TRANSAKSI);

            $ranap = get_casemix_ranap_data($data_detail->FTNO_TRANSAKSI);
            if ($ranap) {
                foreach ($ranap as $key => $value) {
                    $data_detail->$key = $value;
                }
            }

            $sep = get_sep_by_kode_reg($data_detail->FTNO_TRANSAKSI);
            if ($sep) {
                foreach ($sep as $key => $value) {
                    $data_detail->$key = $value;
                }
            }

            return $data_detail;
        });
    }

    /**
     * Update data in CASEMIX_RANAP table
     */
    public function updateCasemixRanap($no_transaksi, $request)
    {
        $data = [
            $request->key => $request->data,
        ];

        try {
            $pasien = DB::connection('sqlsrv')
                ->table('PASIENRAWATINAP AS A')
                ->where('A.PRWINO_TRANSAKSI', $no_transaksi)
                ->first();

            if (!$pasien) {
                Log::error("Pasien dengan transaksi {$no_transaksi} tidak ditemukan.");
                return false;
            }

            // Update atau insert data
            DB::connection('sqlsrv')
                ->table('CASEMIX_RANAP')
                ->updateOrInsert(['NO_TRANSAKSI' => $no_transaksi], $data);

            return true;
        } catch (\Exception $e) {
            Log::error("Error updating CASEMIX_RANAP: " . $e->getMessage());
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
        return DB::connection('sqlsrv')
            ->table('MR_DIAGNOSA')
            ->select('MR_DIAGNOSA.*')
            ->where('MR_DIAGNOSA.MRDNO_TRANSAKSI', $no_transaksi)
            ->get();
    }

    /**
     * Get diagnosa penyakit by transaksi (MR_PENYAKIT)
     *
     * @param string $no_transaksi
     * @return \Illuminate\Support\Collection
     */
    public function getDiagnosaByTransaksi($no_transaksi)
    {
        return DB::connection('sqlsrv')
            ->table('MR_PENYAKIT')
            ->join('PENYAKIT', 'MR_PENYAKIT.MRPKD_PENYAKIT', '=', 'PENYAKIT.KD_PENYAKIT')
            ->orderBy('MR_PENYAKIT.MRPURUT_MASUK', 'ASC')
            ->select('MR_PENYAKIT.*', 'PENYAKIT.PENYAKIT')
            ->where('MR_PENYAKIT.MRPNO_TRANSAKSI', $no_transaksi)
            ->get();
    }

    /**
     * Delete diagnosa by ID from MR_PENYAKIT table
     * 
     * @param int $id
     * @return boolean
     */
    public function deleteDiagnosaById($id)
    {
        try {
            $deleted = DB::connection('sqlsrv')
                ->table('MR_PENYAKIT')
                ->where('ID', $id)
                ->delete();

            return $deleted > 0;
        } catch (\Exception $e) {
            // Handle exception (logging, etc.)
            return false;
        }
    }

    /**
     * Save diagnosa for pasien rujukan
     * 
     * @param array $data
     * @return boolean
     */
    public function saveDiagnosa($data)
    {
        $no_transaksikj = $data['no_transaksikj'];
        $now = now();
        $tgl_masuk = $data['tgl_masuk']; // Already parsed to a Carbon instance

        // Get the latest MRPURUT_MASUK value to generate next
        $lastUrutMasuk = DB::connection('sqlsrv')
            ->table('MR_PENYAKIT')
            ->where('MRPNO_TRANSAKSI', $no_transaksikj)
            ->orderBy('MR_PENYAKIT.MRPURUT_MASUK', 'desc')
            ->limit(1)
            ->value('MRPURUT_MASUK');

        $no_urut_masuk = $lastUrutMasuk ? $lastUrutMasuk + 1 : 1;

        try {
            DB::connection('sqlsrv')
                ->table('MR_PENYAKIT')
                ->insert([
                    'MRPKD_PENYAKIT' => $data['icd10_code'],
                    'MRPNO_TRANSAKSI' => $no_transaksikj,
                    'MRPKD_PASIEN' => $data['no_rm'],
                    'MRPKD_UNIT' => $data['kd_unit'],
                    'MRPTGL_MASUK' => $tgl_masuk,
                    'MRPURUT_MASUK' => $no_urut_masuk,
                    'MRPJENIS' => 'RJ',
                    'MRPSTAT_DIAG' => $data['status_diagnosa'],
                    'MRPKASUS' => $data['kasus'],
                    // 'STATUS_IMUN' => 1,
                    // 'MRPIMUNKE' => 1,
                    'USER_ID' => $data['user_id'], // Assuming user ID is passed
                    'UPDATE_DT' => $now,
                ]);
        } catch (\Exception $e) {
            // Handle exception (logging, etc.)
            return false;
        }

        return true;
    }

    /**
     * Save procedure for pasien rujukan
     * 
     * @param array $data
     * @return boolean
     */
    public function saveProcedure($data)
    {
        $no_transaksikj = $data['no_transaksikj'];
        $now = now();
        $tgl_masuk = $data['tgl_masuk']; // Already parsed to a Carbon instance

        // Get the latest MRTURUT_MASUK value to generate next
        $lastUrutMasuk = DB::connection('sqlsrv')
            ->table('MR_TINDAKAN')
            ->where('MRTNOTRANSAKSI', $no_transaksikj)
            ->orderBy('MR_TINDAKAN.MRTURUT_MASUK', 'desc')
            ->limit(1)
            ->value('MRTURUT_MASUK');

        $no_urut_masuk = $lastUrutMasuk ? $lastUrutMasuk + 1 : 1;

        try {
            DB::connection('sqlsrv')
                ->table('MR_TINDAKAN')
                ->insert([
                    'MRTKD_TINDAKAN' => $data['icd9_code'],
                    'MRTNOTRANSAKSI' => $no_transaksikj,
                    'MRTKD_PASIEN' => $data['no_rm'],
                    'MRTKD_UNIT' => $data['kd_unit'],
                    'MRTTGL_MASUK' => $tgl_masuk,
                    'MRTURUT_MASUK' => $no_urut_masuk,
                    // 'USER_ID' => $data['user_id'], // Assuming user ID is passed
                    'MRTTGL_TINDAKAN' => $now,
                ]);
        } catch (\Exception $e) {
            Log::error("Error while saving procedure: " . $e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Get procedure penyakit by transaksi (MR_TINDAKAN)
     *
     * @param string $no_transaksi
     * @return \Illuminate\Support\Collection
     */
    public function getProcedureByTransaksi($no_transaksi)
    {
        return DB::connection('sqlsrv')
            ->table('MR_TINDAKAN')
            ->select('MR_TINDAKAN.*', 'MR_ICD9.FMI9KETERANGAN')
            ->join('MR_ICD9', 'MR_TINDAKAN.MRTKD_TINDAKAN', '=', 'MR_ICD9.FMI9KODE')
            ->orderBy('MR_TINDAKAN.MRTURUT_MASUK', 'ASC')
            ->where('MR_TINDAKAN.MRTNOTRANSAKSI', $no_transaksi)
            ->get();
    }

    /**
     * Delete procedure by ID from MR_TINDAKAN table
     * 
     * @param int $id
     * @return boolean
     */
    public function deleteProcedureById($id)
    {
        try {
            $deleted = DB::connection('sqlsrv')
                ->table('MR_TINDAKAN')
                ->where('ID', $id)
                ->delete();

            return $deleted > 0;
        } catch (\Exception $e) {
            // Handle exception (logging, etc.)
            return false;
        }
    }

    /**
     * Get diagnosa penyakit by transaksi (MR_PENYAKIT)
     *
     * @param string $no_transaksi
     * @return \Illuminate\Support\Collection
     */
    public function getListBillingTempByTransaksi($no_transaksi)
    {
        return DB::connection('sqlsrv')
            ->table('CASEMIX_BILLING_TEMP')
            ->orderBy('CREATED_AT', 'ASC')
            ->select('*')
            ->where('NO_TRANSAKSI', $no_transaksi)
            ->get();
    }

    /**
     * Save biliing temp for pasien inap
     * 
     * @param array $data
     * @return boolean
     */
    public function saveBillingTemp($data)
    {
        $user = Auth::user();

        try {
            DB::connection('sqlsrv')
                ->table('CASEMIX_BILLING_TEMP')
                ->insert([
                    'NO_TRANSAKSI' => $data['NO_TRANSAKSI'],
                    'KETERANGAN' => $data['KETERANGAN'],
                    'NOMINAL' => $data['NOMINAL'],
                    'CREATED_BY' => $user->email,
                ]);
        } catch (\Exception $e) {
            Log::error("Error save CASEMIX_BILLING_TEMP: " . $e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Delete diagnosa by ID from CASEMIX_BILLING_TEMP table
     * 
     * @param int $id
     * @return boolean
     */
    public function deleteBillingTempById($id)
    {
        try {
            $deleted = DB::connection('sqlsrv')
                ->table('CASEMIX_BILLING_TEMP')
                ->where('ID', $id)
                ->delete();

            return $deleted > 0;
        } catch (\Exception $e) {
            Log::error("Error delete CASEMIX_BILLING_TEMP: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get list CPPT by transaksi
     *
     * @param string $no_transaksi
     * @return \Illuminate\Support\Collection
     */
    public function getCPPTByTransaksi($no_transaksi)
    {
        return DB::connection('sqlsrv')
            ->table('PKU.dbo.TAC_RI_CPPT as a')
            ->selectRaw("
            a.*, 
            b.nama_lengkap AS FS_NM_PEG, 
            d.role_id, 
            RIGHT(a.mdd_date, 2) AS TGL, 
            e.nama_lengkap AS FS_NM_MEDIS_VERIF
        ")
            ->leftJoin('PKU.dbo.TAC_COM_USER as b', 'a.mdb', '=', 'b.user_name')
            ->leftJoin('PKU.dbo.TAC_COM_USER as c', 'a.mdb', '=', 'c.user_name')
            ->leftJoin('PKU.dbo.TAC_COM_ROLE_USER as d', 'c.user_id', '=', 'd.user_id')
            ->leftJoin('PKU.dbo.TAC_COM_USER as e', 'a.FS_KD_MEDIS_VERIF', '=', 'e.user_name')
            ->where('a.FS_KD_REG', $no_transaksi)
            ->where('a.FD_TGL_VOID', '3000-01-01')
            ->orderByDesc('a.mdd_date')
            ->orderByDesc('a.mdd_time')
            ->get();
    }

    /**
     * Get list kamar induk
     *
     * @return \Illuminate\Support\Collection
     */
    public function getListKamarIndukRanap()
    {
        return DB::connection('sqlsrv')
            ->table('KAMAR_INDUK')
            ->select('*')
            ->where('IS_BANGSAL', 1)
            ->get();
    }
}
