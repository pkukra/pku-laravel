<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ICDImportController extends Controller
{
    public function form()
    {
        return view('icd_import');
    }

    public function import(Request $request)
    {
        set_time_limit(3000);
        $request->validate([
            'tsv_file' => 'required|file|mimes:txt,tsv',
        ]);

        $file = $request->file('tsv_file');
        $path = $file->getRealPath();
        $rows = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $nomer = 1;

        foreach ($rows as $row) {
            try {
                $columns = explode("\t", $row);
                DB::connection('sqlsrvsimrs')
                    ->table('ICD')->insert([
                        'code'       => $columns[0] ?? null,
                        'code2'      => $columns[1] ?? null,
                        'description' => $columns[2] ?? null,
                        'system'     => $columns[3] ?? null,
                        'validcode'  => $columns[4] ?? null,
                        'accpdx'     => $columns[5] ?? null,
                        'asterisk'   => $columns[6] ?? null,
                        'is_im'      => $columns[7] ?? null,
                        'mdb'        => null,
                    ]);
                echo $nomer. " - Data berhasil diimpor: " . $columns[0] . "<br>";
            } catch (\Exception $e) {
                echo $e->getMessage();
                echo "<br>";
                print_r($columns);
            }
        }

        return back()->with('success', 'Data berhasil diimpor!');
    }
}
