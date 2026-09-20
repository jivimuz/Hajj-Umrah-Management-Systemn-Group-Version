<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jamaah extends Model
{
    use HasFactory;
    protected $table = "t_jamaah";
    protected $guarded = ["id"];

    protected $casts = [
        'tanggal_daftar' => 'date:Y-m-d',
        'born_date' => 'date:Y-m-d',
        'passport_date' => 'date:Y-m-d',
        'passport_expired' => 'date:Y-m-d',
        'tanggal_keberangkatan' => 'date:Y-m-d',
        'sudah_memiliki_paspor' => 'boolean',
    ];

    public function documents()
    {
        return $this->hasMany(JamaahDocument::class, 'jamaah_id');
    }
}
