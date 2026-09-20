<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JamaahDocument extends Model
{
    use HasFactory;

    protected $table = 't_jamaah_documents';
    protected $guarded = ['id'];

    protected $casts = [
        'is_checked' => 'boolean',
    ];
}