<?php

namespace App\Repositories\RM;

use GuzzleHttp\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BridgingEKlaimRepository
{
    /**
     * Process bridgingDataProcess by kode reg
     * 
     * @param string $kode_reg
     * @return \Illuminate\Support\Collection
     */
    public function bridgingDataProcess($no_sep)
    {
        $semua_transaksi = $this->allTransactionsBySep($no_sep);
        $dpjp_uatama = $semua_transaksi[0];
        foreach ($semua_transaksi as $transaksi) {
            if ($transaksi->RUBBER == 0) {
                $dpjp_uatama = $transaksi;
                break;
            }
        }

        $data = (object)[  // 'data' adalah objek
            'nomor_sep' => $no_sep,
            'tgl_masuk' => Carbon::parse($dpjp_uatama->FRPTGL)->format('d/m/Y'),
            'tgl_pulang' => Carbon::parse($dpjp_uatama->FRPTGL)->format('d/m/Y'),
            'jenis_rawat' => 2, // 1 ranap, 2 rajal, 3 igd
            'kelas_rawat' => 3, // kelas rawat bpjs 1,2,3
            'birth_weight' => 0, // sementara 0 dulu
            'discharge_status' => 1,
            // 'tarif_rs' => $this->getDetailTarifTransaksi($kode_reg),
            // 'diagnosa' => $diagnosa_akhir,
            // 'diagnosa_inagrouper' => $diagnosa_akhir,
            // 'procedure' => $procedure_akhir,
            // 'procedure_inagrouper' => $procedure_akhir,
            // 'adl_sub_acute' => $adl_sub_acute,
            // 'adl_chronic' => $adl_chronic,
            // 'tarif_poli_eks' => (float)number_format($tarif_rs, 2, '.', ''),
            // 'nama_dokter' => $dpjp,
            // 'icu_indikator' => $icu_indikator,
            // 'icu_los' => $icu_los,
            // 'ventilator_hour' => $ventilator_hour,
            // 'kode_tarif' => $kode_tarif,
            // 'payor_id' => $payor_id,
            // 'payor_cd' => $payor_cd,
            // 'coder_nik' => $coder_nik,
            // 'sistole' => $sistole,
            // 'diastole' => $diastole,
            // 'cara_masuk' => $dpjp_uatama->CARA_MASUK,
        ];

        return $data;
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
            $detailTransaksi = DB::connection('sqlsrv')
                ->table('BPJS_SEP AS sep')
                ->leftJoin('PASIEN_RUJUKAN AS pr', function ($join) use ($no_sep) {
                    $join->on('pr.FRPNOTRANSAKSI', '=', 'sep.FMNOTRANSAKSI')
                        ->orOn('pr.FRPNOTRANSAKSIKJ', '=', 'sep.FMNOTRANSAKSI');
                })
                ->leftJoin('DOKTER AS dr', 'pr.FRPDOKTER_ID', '=', 'dr.FMDDOKTER_ID')
                ->leftJoin('POLIKLINIK AS poli', 'pr.FRPUNIT', '=', 'poli.FMPKLINIK_ID')
                ->leftJoin('PASIEN AS p', 'pr.FRPPASIEN_ID', '=', 'p.KD_PASIEN')
                ->select('sep.FMNOSEP', 'pr.*', 'dr.FMDDOKTERN', 'poli.FMPKLINIKN')
                ->where('sep.FMNOSEP', $no_sep)
                ->distinct()
                ->get();
        } catch (\Exception $e) {
            // Log the error if any exception occurs
            Log::error('Error updating Catatan Khusus: ' . $e->getMessage());
            return false;
        }
        return $detailTransaksi;
    }

    /**
     * Get detail tarif transaksi based on kode_reg
     * 
     * @param string $kode_reg
     * @return array
     */
    public function getDetailTarifTransaksi($kode_reg)
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

        $transaksiPasien = DB::connection('sqlsrv')
            ->table('TRANSAKSIPASIEN AS a')
            ->join('TRANSAKSIPASIEND AS b', 'a.FTNO_TRANSAKSI', '=', 'b.FDTNO_TRANSAKSI')
            ->join('PRODUK AS p', 'p.FMPPRODUK_ID', '=', 'b.FDTKD_PRODUK')
            ->join('PRODUK_UNIT AS pu', 'p.FMPUNITPRODUK', '=', 'pu.FTUKODE')
            ->whereNull('b.FDTNO_FAKTUR')
            ->where('b.FDTJENISTRANSAKSI', 'DB')
            ->where('a.FTNO_TRANSAKSI', $kode_reg)
            ->select('a.FTNO_TRANSAKSI', 'b.FDTQTY', 'b.FDTHARGA', 'b.FDTKD_PRODUK', 'pu.FTUKD_EKLAIM')
            ->get();

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

        $fjinkotaData = DB::connection('sqlsrv')
            ->table('FJINKOTA')
            ->where('FHFJNO_TRANSAKSI', '=', $kode_reg)
            ->where('FHFJKRONIS', '=', 0)
            ->select('FHFJBUKTI_ID', 'FHFJTOTAL')
            ->get();

        foreach ($fjinkotaData as $fjinkota) {
            $tarif['obat'] += (float) $fjinkota->FHFJTOTAL;
        }

        return $tarif;
    }
}
