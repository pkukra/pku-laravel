<?php

namespace App\Repositories\KlaimInap;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Bpjs\Bridging\Vclaim\BridgeVclaim;
use App\Repositories\RM\RMAuditTrail;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SEPInapRepository
{
    public function getSEPDetail($kode_reg)
    {
        $sep = DB::connection('sqlsrvsimrs')
            ->table('BPJS_SEP')
            // ->leftJoin('TRANSAKSIPASIENINAP AS TPI', 'TPI.FTNO_TRANSAKSI', '=', 'BPJS_SEP.FMNOTRANSAKSI')
            ->leftJoin('PASIEN', 'BPJS_SEP.FMPASIEN_ID', '=', 'PASIEN.KD_PASIEN')
            ->select(
                'BPJS_SEP.*',
                'PASIEN.*'
            )
            ->where('FMNOTRANSAKSI', $kode_reg)
            ->first();

        return $sep;
    }
    public function getDummyData()
    {
        return [
            'sep' => (object) [
                'FMPRB' => 'PRB',
                'FMNOSEP' => '1301R0010626V000001',
                'FMTGL_SEP_FORMATTED' => '24 Juni 2026',
                'FMNO_KARTU' => '0001234567890',
                'FMPASIEN_ID' => '123456',
                'FMNAMA_PESERTA' => 'BUDI SANTOSO',
                'FMJENISRAWAT' => 1,
                'FMTGL_LAHIR_FORMATTED' => '01 Januari 1980',
                'FMJENIS_KELAMIN' => 'L',
                'TUJ_KUNJUNGAN' => 'Kontrol',
                'telp' => '08123456789',
                'FMNOTELP' => '08123456789',
                'FMPOLYN' => 'PENYAKIT DALAM',
                'dpjpn' => 'dr. Ahmad',
                'FMPPK_RUJUKANN' => 'PUSKESMAS KARANGANYAR',
                'DIAGNOSA_AWAL' => 'Diabetes Mellitus',
                'FMCATATAN' => '-',
                'FMPCETAK' => 1,
                'JAM_TANGGAL_SEP' => now()->format('d-m-Y H:i:s'),
            ],
            'pasien' => (object) ['FMPESERTA' => 'PBI',                'FMNAMA_KELAS' => 'KELAS 3',],
            'penyakit_premiers' => [
                [
                    'PENYAKIT' => 'Diabetes Mellitus Type 2',
                    'MRPKD_PENYAKIT' => 'E11',
                ],
            ],

            'penyakit_sekunders' => [
                [
                    'PENYAKIT' => 'Hipertensi',
                    'MRPKD_PENYAKIT' => 'I10',
                ],
                [
                    'PENYAKIT' => 'Dislipidemia',
                    'MRPKD_PENYAKIT' => 'E78.5',
                ],
                [
                    'PENYAKIT' => 'Hipertensi',
                    'MRPKD_PENYAKIT' => 'I10',
                ],
                [
                    'PENYAKIT' => 'Dislipidemia',
                    'MRPKD_PENYAKIT' => 'E78.5',
                ],
                [
                    'PENYAKIT' => 'Hipertensi',
                    'MRPKD_PENYAKIT' => 'I10',
                ],
                [
                    'PENYAKIT' => 'Dislipidemia',
                    'MRPKD_PENYAKIT' => 'E78.5',
                ],
            ],

            'tindakans' => [
                [
                    'FMI9KETERANGAN' => 'Pemeriksaan Laboratorium',
                    'MRTKD_TINDAKAN' => '90.59',
                ],
                [
                    'FMI9KETERANGAN' => 'EKG',
                    'MRTKD_TINDAKAN' => '89.52',
                ],
                [
                    'FMI9KETERANGAN' => 'Pemeriksaan Laboratorium',
                    'MRTKD_TINDAKAN' => '90.59',
                ],
                [
                    'FMI9KETERANGAN' => 'EKG',
                    'MRTKD_TINDAKAN' => '89.52',
                ],
                [
                    'FMI9KETERANGAN' => 'Pemeriksaan Laboratorium',
                    'MRTKD_TINDAKAN' => '90.59',
                ],
                [
                    'FMI9KETERANGAN' => 'EKG',
                    'MRTKD_TINDAKAN' => '89.52',
                ],
                [
                    'FMI9KETERANGAN' => 'Pemeriksaan Laboratorium',
                    'MRTKD_TINDAKAN' => '90.59',
                ],
                [
                    'FMI9KETERANGAN' => 'EKG',
                    'MRTKD_TINDAKAN' => '89.52',
                ],
                [
                    'FMI9KETERANGAN' => 'Pemeriksaan Laboratorium',
                    'MRTKD_TINDAKAN' => '90.59',
                ],
                [
                    'FMI9KETERANGAN' => 'EKG',
                    'MRTKD_TINDAKAN' => '89.52',
                ],
                [
                    'FMI9KETERANGAN' => 'Pemeriksaan Laboratorium',
                    'MRTKD_TINDAKAN' => '90.59',
                ],
                [
                    'FMI9KETERANGAN' => 'EKG',
                    'MRTKD_TINDAKAN' => '89.52',
                ],
                [
                    'FMI9KETERANGAN' => 'Pemeriksaan Laboratorium',
                    'MRTKD_TINDAKAN' => '90.59',
                ],
                [
                    'FMI9KETERANGAN' => 'EKG',
                    'MRTKD_TINDAKAN' => '89.52',
                ],
                [
                    'FMI9KETERANGAN' => 'Pemeriksaan Laboratorium',
                    'MRTKD_TINDAKAN' => '90.59',
                ],
                [
                    'FMI9KETERANGAN' => 'EKG',
                    'MRTKD_TINDAKAN' => '89.52',
                ],
            ],

            'catatans' => [
                [
                    'MRCATATANKHUSUS' =>
                    'Kontrol ulang 1 bulan dan melanjutkan terapi rutin.',
                ],
            ],
        ];
    }
}
