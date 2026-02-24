<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Championship extends Model
{
    protected $table = 'db_championships';
    protected $fillable = [
        'id',
        'code_data',
        'nama_kejuaraan',
        'lokasi',
        'jumlah_line',
        'tanggal_mulai',
        'tanggal_selesai',
        'status_data',
        'created_at',
        'updated_at'
    ];
        
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';    
    
    // Championship punya banyak Event
    public function event()
    {
        return $this->hasMany(Event::class,'code_kejuaraan', 'code_data');
    }

    public function registrasi()
    {
        return $this->hasMany(registrasi::class,'code_champion', 'code_data');
    }
}
