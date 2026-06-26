<?php

namespace App\Repositories\KlaimInap;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LaporanAnastesiRepository
{
    public function getLaporanAnestesi($fjok)
    {
        return DB::connection('sqlsrvemr')
            ->table('laporan_anestesi')
            ->where('fjok', $fjok)
            ->first();
    }

    public function getTTDPerawat($fjok, $jenis)
    {
        return DB::connection('sqlsrvemr')
            ->table('TTD_PERAWAT_IBS AS A')
            ->leftJoin('TAC_COM_USER AS B', 'B.user_name', '=', 'A.created_by')
            ->select(
                'A.*',
                'B.nama_lengkap'
            )
            ->where('A.FJOKNO_JADWAL', $fjok)
            ->where('A.JENIS_PERAWAT', $jenis)
            ->first();
    }

    public function getAnestesiSedasi($fjok)
    {
        return DB::connection('sqlsrvemr')
            ->table('anestesi_sedasi')
            ->where('FJOKNO_JADWAL', $fjok)
            ->orderByDesc('ID')
            ->value('jenis_tindakan');
    }

    public function getJadwalOk($fjok)
    {
        return DB::connection('sqlsrvemr')
            ->table('PKUKRASIMRS.dbo.OK_JADWAL as A')
            ->select(
                'A.*',
                'B.NAMAPASIEN',
                'B.TGL_LAHIR',
                'B.KD_PASIEN',
                'B.FS_BB',
                'B.FS_TB',
                'B.ALAMAT',
                'C.FMKNAMA_KAMAR',
                'D.FMPKLINIKN',
                'E.NM_FILE',
                'F.FMDDOKTERN',
                'G.FS_SIGN_IN',
                'G.FS_SIGN_OUT',
                DB::raw('H.NM_FILE AS FILE_DOKTER')
            )
            ->join('PKUKRASIMRS.dbo.PASIEN as B', 'B.KD_PASIEN', '=', 'A.FJOKKD_PASIEN')
            ->leftJoin('PKUKRASIMRS.dbo.KAMAR as C', 'C.FMKKAMAR_ID', '=', 'A.FJOKKD_UNIT')
            ->leftJoin('PKUKRASIMRS.dbo.POLIKLINIK as D', 'D.FMPKLINIK_ID', '=', 'A.FJOKKD_UNIT')
            ->leftJoin('TTD_PERAWAT_IBS as E', 'E.FJOKNO_JADWAL', '=', 'A.FJOKNO_JADWAL')
            ->leftJoin('PKUKRASIMRS.dbo.DOKTER as F', 'F.FMDDOKTER_ID', '=', 'A.FJOKKD_DOKTER')
            ->leftJoin('TAC_RI_OK as G', 'G.FJOKNO_JADWAL', '=', 'A.FJOKNO_JADWAL')
            ->leftJoin('TTD_DOKTER as H', 'H.FJOKNO_JADWAL', '=', 'A.FJOKNO_JADWAL')
            ->where('A.FJOKNO_JADWAL', $fjok)
            ->first();
    }

    public function getTTDAnestesi($fjok)
    {
        return DB::connection('sqlsrvemr')
            ->table('TTD_ANESTESI as A')
            ->select(
                'A.*',
                'B.FMDDOKTERN'
            )
            ->leftJoin(
                'PKUKRASIMRS.dbo.DOKTER as B',
                'B.FMDDOKTER_ID',
                '=',
                'A.created_by'
            )
            ->where('A.FJOKNO_JADWAL', $fjok)
            ->first();
    }

    public function getTacRiOkByFjok($fjok)
    {
        return DB::connection('sqlsrvemr')
            ->table('TAC_RI_OK AS a')
            ->leftJoin('TAC_COM_USER AS b', 'a.FS_KD_MEDIS', '=', 'b.user_name')
            ->leftJoin('TAC_COM_USER AS c', 'a.FS_KD_ASISTEN', '=', 'c.user_name')
            ->leftJoin('TAC_COM_USER AS d', 'a.FS_INSTRUMEN', '=', 'd.user_name')
            ->leftJoin('TAC_COM_USER AS e', 'a.FS_KD_PERAWAT', '=', 'e.user_name')
            ->leftJoin('TAC_COM_USER AS f', 'a.FS_KD_MEDIS_AN', '=', 'f.user_name')
            ->leftJoin('TAC_COM_USER AS g', 'a.FS_KD_ANASTESI', '=', 'g.user_name')
            ->select(
                'a.*',
                DB::raw('b.nama_lengkap AS DokterOp'),
                DB::raw('c.nama_lengkap AS Asisten'),
                DB::raw('d.nama_lengkap AS Instrumentator'),
                DB::raw('e.nama_lengkap AS Sirkulator'),
                DB::raw('f.nama_lengkap AS Anastesis'),
                DB::raw('g.nama_lengkap AS Anastesi')
            )
            ->where('a.FJOKNO_JADWAL', $fjok)
            ->first();
    }
}
