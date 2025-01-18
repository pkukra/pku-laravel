<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasienRujukan extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'PASIEN_RUJUKAN';
}
