<?php

// app/Repositories/PasienRujukanRepository.php
namespace App\Repositories\RM;

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
            ->join('PASIEN', 'PASIEN_RUJUKAN.FRPPASIEN_ID', '=', 'PASIEN.KD_PASIEN')
            ->join('DOKTER', 'PASIEN_RUJUKAN.FRPDOKTER_ID', '=', 'DOKTER.FMDDOKTER_ID')
            ->join('POLIKLINIK', 'PASIEN_RUJUKAN.FRPUNIT', '=', 'POLIKLINIK.FMPKLINIK_ID')
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
                    'MRPSTAT_DIAG' => 1,
                    'MRPKASUS' => 1,
                    'STATUS_IMUN' => 1,
                    'MRPIMUNKE' => 1,
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
                ->where('ID', $id) // assuming 'MRPKEY' is the column identifier
                ->delete();

            return $deleted > 0;
        } catch (\Exception $e) {
            // Handle exception (logging, etc.)
            return false;
        }
    }
}
