<?php

// app/Repositories/PasienInapRepository.php
namespace App\Repositories\RM;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PasienInapRepository
{
    /**
     * Get the list of pasien inap based on no_rm
     * 
     * @param string $no_rm
     * @return \Illuminate\Support\Collection
     */
    public function getPasienInaps($no_rm)
    {
        $data =  DB::connection('sqlsrv')
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

        return $data->map(function ($data_detail) {
            $data_detail->TGL_KELUAR = get_tgl_keluar_inap($data_detail->FTNO_TRANSAKSI);
            return $data_detail;
        });
    }

    /**
     * Count the number of pasien inap
     * 
     * @return int
     */
    public function countPasienInap()
    {
        return DB::connection('sqlsrv')
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
        return DB::connection('sqlsrv')
            ->table('PASIENRAWATINAP AS PRI')
            ->leftJoin('PASIEN', 'PRI.PRWIKD_PASIEN', '=', 'PASIEN.KD_PASIEN')
            ->leftJoin('DOKTER', 'PRI.PRWIKD_DOKTER', '=', 'DOKTER.FMDDOKTER_ID')
            ->leftJoin('SPESIALISASI', 'PRI.PRWIKD_SPECIAL', '=', 'SPESIALISASI.FMSPESIALISASI_ID')
            ->select(
                'PASIEN.NAMAPASIEN',
                'PASIEN.TGL_LAHIR',
                'PASIEN.GOL_DARAH',
                'PASIEN.JENIS_KELAMIN',
                'PASIEN.ALAMAT',
                'PRI.*',
                'DOKTER.FMDDOKTERN',
                'SPESIALISASI.FMSPESIALISASIN',
            )
            ->where('PRI.PRWINO_TRANSAKSI', $kode_reg)
            ->orderBy('PRI.PRWITGL_MASUK', 'ASC')
            ->first();  // Menggunakan `first` karena hanya mengambil satu data
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
            return DB::connection('sqlsrv')
                ->table('BPJS_SEP')
                ->select('BPJS_SEP.FMNOSEP')
                ->where('BPJS_SEP.FMNOTRANSAKSI', $kode_reg)
                ->first();
        } catch (\Exception $e) {
            Log::error("Err get SEP inap: " . $e->getMessage());
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
        return DB::connection('sqlsrv')
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
        return DB::connection('sqlsrv')
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
        return DB::connection('sqlsrv')
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
        return DB::connection('sqlsrv')
            ->table('PENYAKIT')
            ->select('PENYAKIT.*')
            ->when($searchTerm, function ($query) use ($searchTerm) {
                return $query->where('PENYAKIT.KD_PENYAKIT', 'like', '%' . $searchTerm . '%')
                    ->orWhere('PENYAKIT.PENYAKIT', 'like', '%' . $searchTerm . '%');
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
     * Search procedure in MR_ICD9 table with a query
     * 
     * @param string $searchTerm
     * @param int $page
     * @return \Illuminate\Support\Collection
     */
    public function searchProcedure($searchTerm, $page)
    {
        return DB::connection('sqlsrv')
            ->table('MR_ICD9')
            ->select('MR_ICD9.*')
            ->when($searchTerm, function ($query) use ($searchTerm) {
                return $query->where('MR_ICD9.FMI9KODE', 'like', '%' . $searchTerm . '%')
                    ->orWhere('MR_ICD9.FMI9KETERANGAN', 'like', '%' . $searchTerm . '%');
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
            $updated = DB::connection('sqlsrv')
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
        return DB::connection('sqlsrv')
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
        return DB::connection('sqlsrv')
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
        return DB::connection('sqlsrv')
            ->table('MR_RUJUKAN_KELUAR')
            ->select('*')
            ->get();
    }

    public function updateCaraMasukPulangsByTransaksi(array $data)
    {
        try {
            DB::connection('sqlsrv')
                ->table('PASIENRAWATINAP')
                ->where('PRWINO_TRANSAKSI', $data['no_transaksi'])
                ->update(['CARA_MASUK' => $data['cara_masuk']]);

            // mulai update mr kematian
            $arrUpdate = [
                'MRKKEADAAN_KELUAR' => $data['keadaan_keluar'],
                'updated_at' => $data['now'],
                'updated_by' => $data['email'],
            ];

            // Jika keadaan_keluar adalah 4 atau 3, gunakan sebab_kematian, selain itu kosongkan
            $arrUpdate['MRKSEBAB'] = in_array($data['keadaan_keluar'], [3, 4]) ? ($data['sebab_kematian'] ?? "") : "";

            $exists = DB::connection('sqlsrv')
                ->table('MR_KEMATIAN')
                ->where('MRKNO_TRANSAKSI', $data['no_transaksi'])
                ->exists();

            if ($exists) {
                // Jika sudah ada, lakukan update
                DB::connection('sqlsrv')
                    ->table('MR_KEMATIAN')
                    ->where('MRKNO_TRANSAKSI', $data['no_transaksi'])
                    ->update($arrUpdate);
            } else {
                // Jika belum ada, lakukan insert
                $arrInsert = array_merge($arrUpdate, [
                    'MRKNO_TRANSAKSI' => $data['no_transaksi'],
                    'MRKKD_PASIEN' => $data['kode_pasien'],
                    'MRKKD_UNIT' => $data['kode_unit'],
                    'MRKKD_DOKTER' => $data['kode_dokter'],
                    'MRKTGL_MASUK' => $data['tgl_masuk'],
                    'MRKTGL_KELUAR' => $data['tgl_masuk'],
                    'created_at' => $data['now'],
                    'created_by' => $data['email'],
                ]);

                DB::connection('sqlsrv')
                    ->table('MR_KEMATIAN')
                    ->insert($arrInsert);
            }
        } catch (\Exception $e) {
            Log::error('Error update/insert Cara masuk: ' . $e->getMessage());
            return false;
        }

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
        return DB::connection('sqlsrv')
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
        $transactions = DB::connection('sqlsrv')
            ->table('TRANSAKSIPASIEND AS A')
            ->select('A.*')
            ->where('A.FDTNO_TRANSAKSI', $kode_reg_kj)
            ->where('A.FDTKD_PRODUK', 'ADL004')
            ->get();

        foreach ($transactions as $transaction) {
            $hasil[] = DB::connection('sqlsrv')
                ->table('RAD_HASIL AS rad')
                ->select('rad.*')
                ->where('rad.MRHNO_TRANSAKSI', $transaction->FDTNO_FAKTUR)
                ->first();
        }
        return $hasil;
    }
}
