<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Club extends Model
{
    protected $table = 'db_clubs';
    protected $fillable = [
        'id',
        'code_data',
        'nama_club',
        'kota_asal',
        'kontak',
        'logo',
        'created_at',
        'updated_at'
    ];
    
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    
    public function atlet()
    {
        return $this->hasMany(Atlet::class, 'code_club', 'code_data');
    }
}
