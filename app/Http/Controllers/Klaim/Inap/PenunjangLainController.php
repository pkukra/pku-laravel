<?php

namespace App\Http\Controllers\Klaim\Inap;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Repositories\KlaimInap\PenunjangLainRepository;

class PenunjangLainController extends Controller
{
    protected $repo;

    public function __construct(PenunjangLainRepository $repo)
    {
        $this->repo = $repo;
    }

    public function list($kode_reg)
    {
        try {
            $data = $this->repo->getByTransaksi($kode_reg);
            return response()->json(["status" => "ok", "data" => $data]);
        } catch (\Exception $e) {
            Log::error("Error fetching penunjang lain: " . $e->getMessage());
            return response()->json(["status" => "nok", "message" => "Gagal memuat dokumen penunjang"], 500);
        }
    }

    public function upload(Request $request, $kode_reg)
    {
        $request->validate([
            "nama_penunjang" => "required|string|max:50",
            "document_file" => "required|file|mimes:pdf,jpeg,png,jpg,gif,bmp|max:5120",
        ]);

        try {
            $file = $request->file("document_file");
            $filename = uniqid() . "_" . preg_replace('/[^A-Za-z0-9_\.-]/', '_', $file->getClientOriginalName());
            $path = Storage::disk('local')->putFileAs('penunjang_lain', $file, $filename);

            $this->repo->store([
                "FRPNOTRANSAKSI" => $kode_reg,
                "NAMA_PENUNJANG" => $request->input("nama_penunjang"),
                "FILE_NAME" => $path,
            ]);

            return response()->json(["status" => "ok"]);
        } catch (\Exception $e) {
            Log::error("Error uploading penunjang lain: " . $e->getMessage());
            return response()->json(["status" => "nok", "message" => "Gagal menyimpan dokumen penunjang"], 500);
        }
    }

    public function download($kode_reg, $id)
    {
        try {
            $record = $this->repo->findByIdAndTransaksi($id, $kode_reg);
            if (! $record) {
                return response()->json(["status" => "nok", "message" => "Dokumen tidak ditemukan"], 404);
            }

            $filePath = Storage::disk('local')->path($record->FILE_NAME);
            if (! Storage::disk('local')->exists($record->FILE_NAME)) {
                return response()->json(["status" => "nok", "message" => "File dokumen tidak ditemukan"], 404);
            }

            return response()->file($filePath);
        } catch (\Exception $e) {
            Log::error("Error downloading penunjang lain: " . $e->getMessage());
            return response()->json(["status" => "nok", "message" => "Gagal membuka dokumen penunjang"], 500);
        }
    }

    public function delete($kode_reg, $id)
    {
        try {
            $record = $this->repo->findByIdAndTransaksi($id, $kode_reg);
            if (! $record) {
                return response()->json(["status" => "nok", "message" => "Dokumen tidak ditemukan"], 404);
            }

            if (Storage::disk('local')->exists($record->FILE_NAME)) {
                Storage::disk('local')->delete($record->FILE_NAME);
            }

            $this->repo->deleteByIdAndTransaksi($id, $kode_reg);

            return response()->json(["status" => "ok"]);
        } catch (\Exception $e) {
            Log::error("Error deleting penunjang lain: " . $e->getMessage());
            return response()->json(["status" => "nok", "message" => "Gagal menghapus dokumen penunjang"], 500);
        }
    }
}
