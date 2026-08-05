<?php

namespace App\Repositories\ReportRM;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class RL51Repository
{
    public function getAllPenyakitData($date_start, $date_end)
    {
        return DB::connection('sqlsrvsimrs')
            ->table('PASIEN_DIAGNOSA_IM as d')
            ->select(
                'd.code',
                DB::raw('COUNT(*) as jumlah_pasien')
            )
            ->whereBetween(
                DB::raw('CAST(d.created_at AS DATE)'),
                [$date_start, $date_end]
            )
            ->groupBy('d.code')
            ->orderByDesc('jumlah_pasien')
            ->get();
    }

    public function getData($date_start, $date_end, $icd10)
    {
        $rows = DB::connection('sqlsrvsimrs')
            ->table('PASIEN_DIAGNOSA_IM as d')
            ->leftJoin('PASIEN as p', 'p.KD_PASIEN', '=', 'd.pasien_id')
            ->select(
                'd.created_at',
                'p.TGL_LAHIR',
                'p.JENIS_KELAMIN'
            )
            ->where('d.code', $icd10)
            ->whereBetween(
                DB::raw('CAST(d.created_at AS DATE)'),
                [$date_start, $date_end]
            )
            ->get();

        $groups = [
            ['label' => '<1 jam', 'type' => 'hour', 'min' => 0, 'max' => 0],
            ['label' => '1-23 jam', 'type' => 'hour', 'min' => 1, 'max' => 23],
            ['label' => '1-7 hari', 'type' => 'day', 'min' => 1, 'max' => 7],
            ['label' => '8-28 hari', 'type' => 'day', 'min' => 8, 'max' => 28],
            ['label' => '29 hari-3 bulan', 'type' => 'month', 'min' => 1, 'max' => 2],
            ['label' => '3-6 bulan', 'type' => 'month', 'min' => 3, 'max' => 5],
            ['label' => '6-11 bulan', 'type' => 'month', 'min' => 6, 'max' => 11],
            ['label' => '1-4 tahun', 'type' => 'year', 'min' => 1, 'max' => 4],
            ['label' => '5-9 tahun', 'type' => 'year', 'min' => 5, 'max' => 9],
            ['label' => '10-14 tahun', 'type' => 'year', 'min' => 10, 'max' => 14],
            ['label' => '15-19 tahun', 'type' => 'year', 'min' => 15, 'max' => 19],
            ['label' => '20-24 tahun', 'type' => 'year', 'min' => 20, 'max' => 24],
            ['label' => '25-29 tahun', 'type' => 'year', 'min' => 25, 'max' => 29],
            ['label' => '30-34 tahun', 'type' => 'year', 'min' => 30, 'max' => 34],
            ['label' => '35-39 tahun', 'type' => 'year', 'min' => 35, 'max' => 39],
            ['label' => '40-44 tahun', 'type' => 'year', 'min' => 40, 'max' => 44],
            ['label' => '45-49 tahun', 'type' => 'year', 'min' => 45, 'max' => 49],
            ['label' => '50-54 tahun', 'type' => 'year', 'min' => 50, 'max' => 54],
            ['label' => '55-59 tahun', 'type' => 'year', 'min' => 55, 'max' => 59],
            ['label' => '60-64 tahun', 'type' => 'year', 'min' => 60, 'max' => 64],
            ['label' => '65-69 tahun', 'type' => 'year', 'min' => 65, 'max' => 69],
            ['label' => '70-74 tahun', 'type' => 'year', 'min' => 70, 'max' => 74],
            ['label' => '75-79 tahun', 'type' => 'year', 'min' => 75, 'max' => 79],
            ['label' => '80-84 tahun', 'type' => 'year', 'min' => 80, 'max' => 84],
            ['label' => '≥85 tahun', 'type' => 'year', 'min' => 85, 'max' => PHP_INT_MAX],
        ];

        $result = [];

        foreach ($groups as $group) {
            $result[$group['label']] = [
                'umur' => $group['label'],
                'laki_laki' => 0,
                'perempuan' => 0,
                'total' => 0,
            ];
        }

        foreach ($rows as $row) {

            if (!$row->TGL_LAHIR) {
                continue;
            }

            $lahir = Carbon::parse($row->TGL_LAHIR);
            $kunjungan = Carbon::parse($row->created_at);

            $interval = $lahir->diff($kunjungan);

            $hours = (int) floor($kunjungan->diffInHours($lahir));
            $days = (int) floor($kunjungan->diffInDays($lahir));
            $months = ($interval->y * 12) + $interval->m;
            $years = $interval->y;

            $umur = null;

            foreach ($groups as $group) {

                switch ($group['type']) {

                    case 'hour':
                        $value = $hours;
                        break;

                    case 'day':
                        $value = $days;
                        break;

                    case 'month':
                        $value = $months;
                        break;

                    default:
                        $value = $years;
                        break;
                }

                if ($value >= $group['min'] && $value <= $group['max']) {
                    $umur = $group['label'];
                    break;
                }
            }

            if (!$umur) {
                continue;
            }

            $jk = strtoupper(trim($row->JENIS_KELAMIN));

            if ($jk == 'L' || $jk == '1') {
                $result[$umur]['laki_laki']++;
            } else {
                $result[$umur]['perempuan']++;
            }

            $result[$umur]['total']++;
        }

        return array_values($result);
    }
}
