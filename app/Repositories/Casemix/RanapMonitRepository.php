<?php

namespace App\Repositories\Casemix;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Repositories\RM\RMAuditTrail;

class RanapMonitRepository
{
    protected $auditTrail;

    public function __construct()
    {
        $this->auditTrail = new RMAuditTrail();
    }

    /**
     * Get the list of pasien ranap each bangsal based on bangsal_induk
     */
    public function getOrCountPasienRanap(
        $month_pulang,
        $year_pulang,
        $bulan,
        $tahun,
        $bangsal_induk,
        $nomer_rm,
        $status,
        $perPage = null,
        $offset = null,
        $countOnly = false,
        $order_kamar = false
    ) {
        $query = DB::connection('sqlsrvsimrs')
            ->table('TRANSAKSIPASIENINAP AS TPI')
            ->join('PASIENRAWATINAP AS PRI', function ($join) {
                $join->on(DB::raw('CAST(PRI.PRWINO_TRANSAKSI AS NVARCHAR)'), '=', 'TPI.FTNO_TRANSAKSI')
                    ->whereRaw('CAST(PRI.PRWINO_URUT AS NVARCHAR) = CAST(TPI.FTNO_URUT AS NVARCHAR)');
            })
            ->leftJoin('PASIEN AS P', 'P.KD_PASIEN', '=', 'TPI.FTKD_PASIEN')
            ->leftJoin('DOKTER AS DR', 'DR.FMDDOKTER_ID', '=', 'PRI.PRWIKD_DOKTER')
            ->leftJoin('KAMAR AS K', 'K.FMKKAMAR_ID', '=', 'PRI.PRWIKD_KAMAR')
            ->leftJoin('BPJS_SEP AS SEP', 'SEP.FMNOTRANSAKSI', '=', 'TPI.FTNO_TRANSAKSI')
            ->leftJoin('CUSTOMER AS C', 'C.CUSID', '=', 'PRI.PRWIKD_CUSTOMER')
            ->when(
                $month_pulang && $year_pulang,
                fn($q) =>
                $q->whereRaw('MONTH(PRI.PRWITGL_KELUAR) = ?', [$month_pulang])
                    ->whereRaw('YEAR(PRI.PRWITGL_KELUAR) = ?', [$year_pulang])
            )
            ->when($bulan && $tahun, fn($q) => $q->whereRaw('MONTH(TPI.FTTGL_TRANSAKSI) = ?', [$bulan])
                ->whereRaw('YEAR(TPI.FTTGL_TRANSAKSI) = ?', [$tahun]))
            ->when($status === 'dirawat', fn($q) => $q->whereNull('PRI.PRWITGL_KELUAR'))
            ->when($status === 'sudah_pulang', fn($q) => $q->whereNotNull('PRI.PRWITGL_KELUAR'));

        if ($bangsal_induk && $bangsal_induk !== 'all') {
            $query->where('K.FMKKAMARINDUK', $bangsal_induk);
        }

        if ($nomer_rm) {
            $query->where('TPI.FTKD_PASIEN', $nomer_rm);
        }

        if ($countOnly) {
            return $query->count();
        }

        $query->select(
            'TPI.FTNO_TRANSAKSI',
            'PRI.PRWIKD_KAMAR',
            'PRI.PRWIKD_KELAS',
            'PRI.PRWIKD_DOKTER',
            'PRI.PRWITGL_MASUK',
            'PRI.PRWITGL_KELUAR',
            'PRI.PRWIKD_CUSTOMER',
            'C.NAME AS PENJAMIN',
            'TPI.FTTGL_TRANSAKSI',
            'TPI.FTKD_PASIEN',
            'TPI.FTKODEINACBG',
            'TPI.FTTARIPINACBG',
            'TPI.FTTARIPINACBG1',
            'TPI.FTTARIPINACBG2',
            'TPI.FTTARIPINACBG3',
            'P.NAMAPASIEN',
            'P.TGL_LAHIR',
            'P.JENIS_KELAMIN',
            'P.ALAMAT',
            'DR.FMDDOKTERN AS DPJP',
            'SEP.FMKODEKELAS AS KELAS_RAWAT',
            'K.FMKNAMA_KAMAR',
            'SEP.FMNOSEP'
        );

        if ($order_kamar) {
            $query->orderBy('K.FMKKAMARINDUK', 'asc');
        } else {
            $query->orderBy('TPI.FTTGL_TRANSAKSI', 'desc');
        }

        $data = $query->offset($offset)->limit($perPage)->get();

        if ($data->isEmpty()) {
            return $data;
        }

        // === Kumpulkan semua ID ===
        $transaksiIds = $data->pluck('FTNO_TRANSAKSI')->toArray();
        $noseps       = $data->pluck('FMNOSEP')->filter()->toArray();

        // === Bills by transaksi ===
        $bills = DB::connection('sqlsrvsimrs')
            ->table('TRANSAKSIPASIENINAPD')
            ->select('FDTNO_TRANSAKSI', 'FDTQTY', 'FDTHARGA')
            ->whereIn('FDTNO_TRANSAKSI', $transaksiIds)
            ->where('FDTJENISTRANSAKSI', 'DB')
            ->get();

        $billMap = [];
        foreach ($bills as $b) {
            $billMap[$b->FDTNO_TRANSAKSI] = ($billMap[$b->FDTNO_TRANSAKSI] ?? 0) + ($b->FDTQTY * $b->FDTHARGA);
        }

        // === Casemix by transaksi ===
        $casemix = DB::connection('sqlsrvsimrs')
            ->table('CASEMIX_RANAP')
            ->whereIn('NO_TRANSAKSI', $transaksiIds)
            ->get()
            ->keyBy('NO_TRANSAKSI');

        // === Diagnosa by NOSEP ===
        $diagnosaRows = DB::connection('sqlsrvsimrs')
            ->table('PASIEN_DIAGNOSA_IM as A')
            ->leftJoin('ICD', 'A.code', '=', 'ICD.code')
            ->select(
                'A.code',
                'A.no_sep as no_sep',
                'A.is_primary',
                'ICD.is_code_warning',
                'ICD.description'
            )
            ->whereIn('A.no_sep', $noseps)
            ->get();

        $diagnosaLengkap = $diagnosaRows->groupBy('no_sep');
        $allDiagnosaCodes = $diagnosaRows->pluck('code')->unique()->toArray();

        // === Tindakan by NOSEP ===
        $tindakanRows = DB::connection('sqlsrvsimrs')
            ->table('PASIEN_TINDAKAN_IM as A')
            ->leftJoin('ICD', 'A.code', '=', 'ICD.code')
            ->select(
                'A.code',
                'A.no_sep as no_sep',
                'A.is_primary',
                'ICD.description'
            )
            ->whereIn('A.no_sep', $noseps)
            ->get();

        $tindakanLengkap = $tindakanRows->groupBy('no_sep');
        $allTindakanCodes = $tindakanRows->pluck('code')->unique()->toArray();

        // === ICD Alerts ===
        $allCodes = array_unique(array_merge($allDiagnosaCodes, $allTindakanCodes));

        $alerts = DB::connection('sqlsrvsimrs')
            ->table('ICD_ALERT')
            ->whereIn('icd_code', $allCodes)
            ->get()
            ->groupBy('icd_code');

        // === Cara Pulang by transaksi ===
        $caraPulangRows = DB::connection('sqlsrvemr')
            ->table('TAB_PX_PULANG_RESUME as R')
            ->leftJoin('DEV_CARA_PULANG_RANAP as M', 'M.id', '=', 'R.FS_CARA_PULANG')
            ->select('R.FS_KD_REG', 'M.nama')
            ->whereIn('R.FS_KD_REG', $transaksiIds) // atau pakai $noseps jika FS_KD_REG = no_sep
            ->get();

        $caraPulangMap = [];
        foreach ($caraPulangRows as $row) {
            $caraPulangMap[$row->FS_KD_REG] = $row->nama;
        }

        // === Merge hasil ===
        // === Merge hasil ===
        return $data->map(function ($item) use (
            $billMap,
            $casemix,
            $diagnosaLengkap,
            $tindakanLengkap,
            $alerts,
            $caraPulangMap
        ) {
            // total bill by transaksi
            $item->TOTAL_BILL = $billMap[$item->FTNO_TRANSAKSI] ?? 0;

            // casemix by transaksi
            if (isset($casemix[$item->FTNO_TRANSAKSI])) {
                foreach ($casemix[$item->FTNO_TRANSAKSI] as $key => $val) {
                    $item->$key = $val;
                }
            }

            // diagnosa & tindakan by SEP
            $item->DIAGNOSA_LENGKAP = ($diagnosaLengkap[$item->FMNOSEP] ?? collect())->values();
            $item->TINDAKAN_LENGKAP = ($tindakanLengkap[$item->FMNOSEP] ?? collect())->values();

            $item->CARA_PULANG = $caraPulangMap[$item->FTNO_TRANSAKSI] ?? '';

            // kumpulkan semua kode dari diagnosa & tindakan lengkap
            $codes = collect()
                ->merge($item->DIAGNOSA_LENGKAP->pluck('code'))
                ->merge($item->TINDAKAN_LENGKAP->pluck('code'))
                ->unique();

            // ICD Alerts
            $item->ALERTS = $codes
                ->filter(fn($c) => isset($alerts[$c]))
                ->flatMap(fn($c) => $alerts[$c]->map(fn($a) => [
                    'icd_code'        => $c,
                    'description'     => $a->description,
                    'is_code_warning' => $a->is_code_warning,
                ]))
                ->values()
                ->toArray();

            return $item;
        });
    }

    /**
     * Update data in CASEMIX_RANAP table
     */
    public function updateCasemixRanap($no_transaksi, $request)
    {
        $user = Auth::user();

        $data = [
            $request->key => $request->data,
        ];

        try {
            $pasien = DB::connection('sqlsrvsimrs')
                ->table('PASIENRAWATINAP AS A')
                ->where('A.PRWINO_TRANSAKSI', $no_transaksi)
                ->first();

            if (!$pasien) {
                Log::error("RanapMonitRepository Pasien dengan transaksi {$no_transaksi} tidak ditemukan.");
                return false;
            }

            // Update atau insert data
            $is_ok = DB::connection('sqlsrvsimrs')
                ->table('CASEMIX_RANAP')
                ->updateOrInsert(['NO_TRANSAKSI' => $no_transaksi], $data);

            if ($is_ok) {
                // Catat audit trail
                $this->auditTrail->insert([
                    "object_id"  => $no_transaksi,
                    "action_id"  => 10,
                    "user_email" => $user->email,
                    "user_id"    => $user->id,
                    "created_at" => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    "data"       => $data,
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error("RanapMonitRepository Error updating CASEMIX_RANAP: " . $e->getMessage());
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
            ->get();
    }

    /**
     * Get diagnosa penyakit by transaksi (MR_PENYAKIT)
     *
     * @param string $nosep
     * @return \Illuminate\Support\Collection
     */
    public function getDiagnosaByTransaksi($nosep)
    {
        return DB::connection('sqlsrvsimrs')
            ->table('MR_PENYAKIT')
            ->join('PENYAKIT', 'MR_PENYAKIT.MRPKD_PENYAKIT', '=', 'PENYAKIT.KD_PENYAKIT')
            ->orderBy('MR_PENYAKIT.MRPURUT_MASUK', 'ASC')
            ->select('MR_PENYAKIT.*', 'PENYAKIT.PENYAKIT')
            ->where('MR_PENYAKIT.NOSEP', $nosep)
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
                Log::error("RanapMonitRepository deleteDiagnosaById error: gagal simpan audittrail");
                $conn->rollBack();
                return false;
            }

            // Jika semuanya sukses
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            Log::error("RanapMonitRepository deleteDiagnosaById error: " . $e->getMessage());
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
                Log::error("RanapMonitRepository saveDiagnosaRanap audittrail error");
                DB::connection('sqlsrvsimrs')->rollBack();
                return false;
            }
        } catch (\Exception $e) {
            DB::connection('sqlsrvsimrs')->rollBack();
            Log::error("RanapMonitRepository saveDiagnosaRanap error: " . $e->getMessage());
            return false;
        }

        DB::connection('sqlsrvsimrs')->commit();
        return true;
    }

    /**
     * Save procedure for pasien rujukan
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
                Log::error("RanapMonitRepository saveProcedureRanap: Gagal menyimpan audit trail");
                $conn->rollBack();
                return false;
            }

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            Log::error("RanapMonitRepository saveProcedureRanap error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get procedure penyakit by transaksi (MR_TINDAKAN)
     *
     * @param string $no_sep
     * @return \Illuminate\Support\Collection
     */
    public function getProcedureByTransaksi($no_sep)
    {
        return DB::connection('sqlsrvsimrs')
            ->table('MR_TINDAKAN')
            ->select('MR_TINDAKAN.*', 'MR_ICD9.FMI9KETERANGAN')
            ->join('MR_ICD9', 'MR_TINDAKAN.MRTKD_TINDAKAN', '=', 'MR_ICD9.FMI9KODE')
            ->orderBy('MR_TINDAKAN.MRTURUT_MASUK', 'ASC')
            ->where('MR_TINDAKAN.NOSEP', $no_sep)
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
                Log::error("RanapMonitRepository deleteProcedureByIdranap error: gagal simpan audittrail");
                $conn->rollBack();
                return false;
            }

            // Commit jika semua sukses
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            Log::error("RanapMonitRepository deleteProcedureByIdranap error: " . $e->getMessage());
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
        return DB::connection('sqlsrvsimrs')
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
            DB::connection('sqlsrvsimrs')
                ->table('CASEMIX_BILLING_TEMP')
                ->insert([
                    'NO_TRANSAKSI' => $data['NO_TRANSAKSI'],
                    'KETERANGAN' => $data['KETERANGAN'],
                    'NOMINAL' => $data['NOMINAL'],
                    'CREATED_BY' => $user->email,
                ]);
        } catch (\Exception $e) {
            Log::error("RanapMonitRepository Error save CASEMIX_BILLING_TEMP: " . $e->getMessage());
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
            $deleted = DB::connection('sqlsrvsimrs')
                ->table('CASEMIX_BILLING_TEMP')
                ->where('ID', $id)
                ->delete();

            return $deleted > 0;
        } catch (\Exception $e) {
            Log::error("RanapMonitRepository Error delete CASEMIX_BILLING_TEMP: " . $e->getMessage());
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
        return DB::connection('sqlsrvsimrs')
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
        return DB::connection('sqlsrvsimrs')
            ->table('KAMAR_INDUK')
            ->select('*')
            ->where('IS_BANGSAL', 1)
            ->get();
    }
}
