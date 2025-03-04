<?php

namespace App\Repositories\Casemix;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RanapMonitRepository
{
    /**
     * Get the list of pasien ranap each bangsal based on bangsal_induk
     */
    public function getOrCountPasienRanap($bulan, $tahun, $bangsal_induk, $nomer_rm, $status, $perPage = null, $offset = null, $countOnly = false)
    {
        $query = DB::connection('sqlsrv')
            ->table('TRANSAKSIPASIENINAP AS TPI')
            ->join('PASIENRAWATINAP AS PRI', function ($join) {
                $join->on(DB::raw('CAST(PRI.PRWINO_TRANSAKSI AS NVARCHAR)'), '=', 'TPI.FTNO_TRANSAKSI')
                    ->whereRaw('CAST(PRI.PRWINO_URUT AS NVARCHAR) = CAST(TPI.FTNO_URUT AS NVARCHAR)');
            })
            ->leftJoin('KAMAR AS K', 'K.FMKKAMAR_ID', '=', 'PRI.PRWIKD_KAMAR')

            ->whereRaw('MONTH(TPI.FTTGL_TRANSAKSI) = ?', [$bulan])
            ->whereRaw('YEAR(TPI.FTTGL_TRANSAKSI) = ?', [$tahun])
            ->where('K.FMKKAMARINDUK', $bangsal_induk)
            ->when($status === 'dirawat', fn($query) => $query->whereNull('PRI.PRWITGL_KELUAR'))
            ->when($status === 'sudah_pulang', fn($query) => $query->whereNotNull('PRI.PRWITGL_KELUAR'));
        if ($nomer_rm) {
            $query->where('TPI.FTKD_PASIEN', "$nomer_rm");
        }

        if ($countOnly) {
            return $query->count();
        }

        $data = $query->select(
            'TPI.FTNO_TRANSAKSI',
            'PRI.PRWIKD_KAMAR',
            'PRI.PRWIKD_KELAS',
            'PRI.PRWIKD_DOKTER',
            'PRI.PRWITGL_KELUAR',
            'TPI.FTTGL_TRANSAKSI',
            'TPI.FTKD_PASIEN',
        )
            ->orderBy('TPI.FTTGL_TRANSAKSI', 'desc')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        return $data->map(function ($data_detail) {
            $data_detail->FS_DIAGNOSA = get_diagnosa_ri($data_detail->FTNO_TRANSAKSI);
            $ranap = get_casemix_ranap_data($data_detail->FTNO_TRANSAKSI);
            if ($ranap) {
                foreach ($ranap as $key => $value) {
                    $data_detail->$key = $value;
                }
            }

            $pasien = get_pasien_by_no_rm($data_detail->FTKD_PASIEN);
            if ($pasien) {
                foreach ($pasien as $key => $value) {
                    $data_detail->$key = $value;
                }
            }

            $dokter = get_dokter_by_kode($data_detail->PRWIKD_DOKTER);
            if ($dokter) {
                foreach ($dokter as $key => $value) {
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

    public function getOrCountPasienRanap2($bulan, $tahun, $bangsal_induk, $nomer_rm, $status, $perPage = null, $offset = null, $countOnly = false)
    {
        $query = DB::connection('sqlsrv')
            ->table('PASIENRAWATINAP AS A')
            ->leftJoin('PASIEN AS B', 'A.PRWIKD_PASIEN', '=', 'B.KD_PASIEN')
            ->leftJoin('DOKTER AS C', 'A.PRWIKD_DOKTER', '=', 'C.FMDDOKTER_ID')
            ->leftJoin('KAMAR AS D', 'A.PRWIKD_KAMAR', '=', 'D.FMKKAMAR_ID')
            ->whereRaw('MONTH(A.PRWITGL_INAP) = ?', [$bulan])
            ->whereRaw('YEAR(A.PRWITGL_INAP) = ?', [$tahun])
            ->where('D.FMKKAMARINDUK', $bangsal_induk)
            ->when($status === 'dirawat', fn($query) => $query->whereNull('A.PRWITGL_KELUAR'))
            ->when($status === 'sudah_pulang', fn($query) => $query->whereNotNull('A.PRWITGL_KELUAR'));

        if ($nomer_rm) {
            $query->where('A.PRWIKD_PASIEN', "$nomer_rm");
        }

        // Jika hanya ingin menghitung total data
        if ($countOnly) {
            return $query->count();
        }

        // Ambil data dengan limit dan offset
        $data = $query->select(
            'A.PRWINO_TRANSAKSI',
            'A.PRWIKD_KAMAR',
            'C.FMDDOKTERN',
            DB::raw('MAX(A.PRWITGL_MASUK) as PRWITGL_MASUK'),
            DB::raw('MAX(A.PRWITGL_KELUAR) as PRWITGL_KELUAR'),
            'A.PRWIKD_PASIEN',
            'B.NAMAPASIEN',
            'D.FMKKAMARINDUK',
            DB::raw('CASE 
                    WHEN MAX(A.PRWITGL_KELUAR) IS NULL THEN DATEDIFF(DAY, MAX(A.PRWITGL_MASUK), GETDATE()) + 1
                    ELSE DATEDIFF(DAY, MAX(A.PRWITGL_MASUK), MAX(A.PRWITGL_KELUAR)) + 1
                    END as TOTAL_HARI')
        )
            ->groupBy('A.PRWINO_TRANSAKSI', 'A.PRWIKD_KAMAR', 'A.PRWIKD_PASIEN', 'B.NAMAPASIEN', 'C.FMDDOKTERN', 'D.FMKKAMARINDUK')
            ->orderByDesc('PRWITGL_MASUK')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        // Proses hasil dengan cache untuk diagnosa dan casemix ranap
        return $data->map(function ($pasien) {
            $cacheKey = "pasien_detail_{$pasien->PRWINO_TRANSAKSI}";
            return Cache::remember($cacheKey, 30, function () use ($pasien) {
                $pasien->FS_DIAGNOSA = get_diagnosa_ri($pasien->PRWINO_TRANSAKSI);
                $ranap = get_casemix_ranap_data($pasien->PRWINO_TRANSAKSI);
                if ($ranap) {
                    foreach ($ranap as $key => $value) {
                        $pasien->$key = $value;
                    }
                }
                return $pasien;
            });
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
}
