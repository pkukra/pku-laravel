<?php

// app/Repositories/PasienRujukanRepository.php
namespace App\Repositories\RM;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PasienRujukanRepository
{
    /**
     * Get the list of pasien rujukan based on no_rm
     * 
     * @param string $no_rm
     * @return \Illuminate\Support\Collection
     */
    public function getPasienRujukans($no_rm)
    {
        return DB::connection('sqlsrv')
            ->table('PASIEN_RUJUKAN')
            ->join('DOKTER', 'PASIEN_RUJUKAN.FRPDOKTER_ID', '=', 'DOKTER.FMDDOKTER_ID')
            ->join('POLIKLINIK', 'PASIEN_RUJUKAN.FRPUNIT', '=', 'POLIKLINIK.FMPKLINIK_ID')
            ->select(
                'PASIEN_RUJUKAN.*',
                'DOKTER.FMDDOKTERN',
                'POLIKLINIK.FMPKLINIKN'
            )
            ->where('PASIEN_RUJUKAN.FRPPASIEN_ID', $no_rm)
            ->orderBy('FRPTGL', 'desc')
            ->get();
    }

    /**
     * Count the number of pasien rujukan
     * 
     * @return int
     */
    public function countPasienRujukan()
    {
        return DB::connection('sqlsrv')
            ->table('PASIEN_RUJUKAN')
            ->count();
    }

    /**
     * Get pasien rujukan detail based on kode_reg
     *
     * @param string $kode_reg
     * @return object|null
     */
    public function getPasienRujukanDetail($kode_reg)
    {
        return DB::connection('sqlsrv')
            ->table('PASIEN_RUJUKAN')
            ->leftJoin('PASIEN', 'PASIEN_RUJUKAN.FRPPASIEN_ID', '=', 'PASIEN.KD_PASIEN')
            ->leftJoin('DOKTER', 'PASIEN_RUJUKAN.FRPDOKTER_ID', '=', 'DOKTER.FMDDOKTER_ID')
            ->leftJoin('POLIKLINIK', 'PASIEN_RUJUKAN.FRPUNIT', '=', 'POLIKLINIK.FMPKLINIK_ID')
            ->select(
                'PASIEN.NAMAPASIEN',
                'PASIEN.TGL_LAHIR',
                'PASIEN.GOL_DARAH',
                'PASIEN.JENIS_KELAMIN',
                'PASIEN.ALAMAT',
                'PASIEN_RUJUKAN.*',
                'DOKTER.FMDDOKTERN',
                'POLIKLINIK.FMPKLINIKN'
            )
            ->where('PASIEN_RUJUKAN.FRPNOTRANSAKSIKJ', $kode_reg)
            ->first();  // Menggunakan `first` karena hanya mengambil satu data
    }

    /**
     * Get SEP dari pasien rujukan BPJS detail based on kode_reg 
     *
     * @param string $kode_reg
     * @return object|null
     */
    public function getSepPasienRujukan($kode_reg, $kode_reg_kj)
    {
        try {
            return DB::connection('sqlsrv')
                ->table('BPJS_SEP')
                ->select('BPJS_SEP.FMNOSEP')
                ->whereIn('BPJS_SEP.FMNOTRANSAKSI', [$kode_reg, $kode_reg_kj])
                ->first();
        } catch (\Exception $e) {
            Log::error("Err get SEP: " . $e->getMessage());
            return false;
        }
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
}
