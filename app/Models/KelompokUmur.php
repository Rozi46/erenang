<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KelompokUmur extends Model
{
    protected $table = 'db_age_groups';
    protected $fillable = [
        'id',
        'code_data',
        'code_kelompok',
        'nama_kelompok',
        'min_usia',
        'max_usia',
        'created_at',
        'updated_at'
    ];
    
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public function event()
    {
        return $this->hasMany(Event::class, 'code_kategori', 'code_data');
    }

    public function registrasi()
    {
        return $this->hasMany(Registrasi::class, 'code_age_group', 'code_data');
    }
}
