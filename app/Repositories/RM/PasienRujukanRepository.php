<?php

// app/Repositories/PasienRujukanRepository.php
namespace App\Repositories\RM;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Bpjs\Bridging\Vclaim\BridgeVclaim;

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
    public function countPasienRujukan($no_rm)
    {
        return DB::connection('sqlsrv')
            ->table('PASIEN_RUJUKAN')
            ->where('PASIEN_RUJUKAN.FRPPASIEN_ID', $no_rm)
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
            ->leftJoin('MR_CARA_MASUK_BPJS AS cm', 'PASIEN_RUJUKAN.CARA_MASUK', '=', 'cm.KODE')
            ->select(
                'PASIEN.NAMAPASIEN',
                'PASIEN.TGL_LAHIR',
                'PASIEN.GOL_DARAH',
                'PASIEN.JENIS_KELAMIN',
                'PASIEN.ALAMAT',
                'PASIEN_RUJUKAN.*',
                'DOKTER.FMDDOKTERN',
                'POLIKLINIK.FMPKLINIKN',
                'cm.KETERANGAN AS CARA_MASUK_BPJS'
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
     * Get diagnosa utama pasien by koderegkj 
     *
     * @param string $kode_reg_kj
     * @return object|null
     */
    public function getDiagnosaUtamaPasienRujukan($kode_reg_kj)
    {
        try {
            return DB::connection('sqlsrv')
                ->table('MR_PENYAKIT')
                ->select('MRPKD_PENYAKIT')
                ->where('MRPSTAT_DIAG', 5)
                ->where('MRPNO_TRANSAKSI', $kode_reg_kj)
                ->first();
        } catch (\Exception $e) {
            Log::error("getDiagnosaUtamaPasienRujukan: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update nomer SEP dari pasien rujukan BPJS detail based on kode_reg 
     *
     * @param string $kode_reg, $kode_reg_kj, $no_rm, $new_sep
     * @return object
     */
    public function updateNomerSepPasienRujukan($kode_reg, $kode_reg_kj, $no_rm, $new_sep, $kode_poli, $dpjp)
    {
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

        // Mulai transaksi database
        DB::connection('sqlsrv')->beginTransaction();
        try {
            // Cari apakah salah satu FMNOTRANSAKSI sudah ada
            $existingRecord = DB::connection('sqlsrv')
                ->table('BPJS_SEP')
                ->whereIn('FMNOTRANSAKSI', [$kode_reg, $kode_reg_kj])
                ->first();

            $diagnosa = optional(app()->call([$this, 'getDiagnosaUtamaPasienRujukan'], ['kode_reg' => $kode_reg]))->MRPKD_PENYAKIT ?? null;
            if (!$diagnosa) {
                return [
                    "status" => "nok",
                    "message" => "Belum ada diagnosa, ganti nomer sep dari aplikasi rajal"
                ];
            }

            if ($existingRecord) {
                // Jika sudah ada, update berdasarkan FMNOTRANSAKSI yang ditemukan
                DB::connection('sqlsrv')
                    ->table('BPJS_SEP')
                    ->where('FMNOTRANSAKSI', $existingRecord->FMNOTRANSAKSI)
                    ->update([
                        'FMNOSEP'         => $new_sep,
                        'FMTGL_SEP'       => date('Y-m-d H:i:s', strtotime($tanggal_sep)),
                        'FMNO_KARTU'      => $nomer_kartu,
                        'FMPASIEN_ID'     => $no_rm,
                        'FMJENIS_KELAMIN' => $jenis_kelamin,
                        'FMNAMA_PESERTA'  => $nama,
                        'FMJENISRAWAT'    => '2',
                        'FMKODEKELAS'     => $hak_kelas,
                        'FMTGL_LAHIR'     => date('Y-m-d H:i:s', strtotime($tgl_lahir)),
                        'FMPOLYN'         => $kode_poli,
                        'dpjpn'           => $dpjp,
                        'FMDIAGNOSA'      => $diagnosa
                    ]);
            } else {
                // Jika tidak ada, lakukan insert
                DB::connection('sqlsrv')
                    ->table('BPJS_SEP')
                    ->insert([
                        'FMNOTRANSAKSI'   => $kode_reg_kj,
                        'FMNOSEP'         => $new_sep,
                        'FMTGL_SEP'       => date('Y-m-d H:i:s', strtotime($tanggal_sep)),
                        'FMNO_KARTU'      => $nomer_kartu,
                        'FMPASIEN_ID'     => $no_rm,
                        'FMJENIS_KELAMIN' => $jenis_kelamin,
                        'FMNAMA_PESERTA'  => $nama,
                        'FMJENISRAWAT'    => '2',
                        'FMKODEKELAS'     => $hak_kelas,
                        'FMTGL_LAHIR'     => date('Y-m-d H:i:s', strtotime($tgl_lahir)),
                        'FMPOLYN'         => $kode_poli,
                        'dpjpn'           => $dpjp,
                        'FMDIAGNOSA'      => $diagnosa
                    ]);
            }

            // Commit transaksi
            DB::connection('sqlsrv')->commit();

            return [
                "status" => "ok",
                "message" => "Update Nomer SEP berhasil"
            ];
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollBack();
            Log::error("Error update BPJS_SEP: " . $e->getMessage());

            return [
                "status" => "nok",
                "message" => "Terjadi kesalahan saat memperbarui data SEP"
            ];
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
                ->table('PASIEN_RUJUKAN')
                ->where('FRPNOTRANSAKSIKJ', $data['no_transaksi_kj'])
                ->update(['CARA_MASUK' => $data['cara_masuk']]);

            // Jika keadaan_keluar selain 1, KPRUJUKLUAR harus kosong
            $kodeRsRujukKeluar = ($data['keadaan_keluar'] == 7) ? $data['kode_rs_rujuk_keluar'] : "";

            // Update keperawatan di tabel KUNJUNGANPASIEN
            DB::connection('sqlsrv')
                ->table('KUNJUNGANPASIEN')
                ->where('KPNO_TRANSAKSI', $data['no_transaksi_kj'])
                ->update([
                    'KPRUJUKLUAR' => $kodeRsRujukKeluar,
                    'KPPERAWATAN' => $data['keperawatan'],
                ]);

            $arrUpdate = [
                'MRKKEADAAN_KELUAR' => $data['keadaan_keluar'],
                'updated_at' => $data['now'],
                'updated_by' => $data['email'],
            ];

            // Jika keadaan_keluar adalah 4 atau 3, gunakan sebab_kematian, selain itu kosongkan
            $arrUpdate['MRKSEBAB'] = in_array($data['keadaan_keluar'], [3, 4]) ? ($data['sebab_kematian'] ?? "") : "";

            $exists = DB::connection('sqlsrv')
                ->table('MR_KEMATIAN')
                ->where('MRKNO_TRANSAKSI', $data['no_transaksi_kj'])
                ->exists();

            if ($exists) {
                // Jika sudah ada, lakukan update
                DB::connection('sqlsrv')
                    ->table('MR_KEMATIAN')
                    ->where('MRKNO_TRANSAKSI', $data['no_transaksi_kj'])
                    ->update($arrUpdate);
            } else {
                // Jika belum ada, lakukan insert
                $arrInsert = array_merge($arrUpdate, [
                    'MRKNO_TRANSAKSI' => $data['no_transaksi_kj'],
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
            ->table('PKU.dbo.TAC_RJ_MEDIS')
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
