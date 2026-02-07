<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'db_events';
    protected $fillable = [
        'id',
        'code_data',
        'code_event',
        'code_gaya',
        'jarak',
        'code_kategori',
        'gender',
        'tanggal',
        'code_kejuaraan',
        'created_at',
        'updated_at'
    ];
    
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';    
    
    // 🔗 Event milik satu Championship
    public function championship()
    {
        return $this->belongsTo(
            Championship::class,
            'code_kejuaraan', // FK di db_events
            'code_data'       // key di db_championships
        );
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'code_gaya', 'code_data');
    }      
    
    public function kelompokUmur()
    {
        return $this->belongsTo(KelompokUmur::class, 'code_kategori', 'code_data');
    }
    
    public function heat()
    {
        return $this->hasMany(Heat::class, 'code_event', 'code_data');
    }
    
    public function registrasi()
    {
        return $this->hasMany(Registrasi::class, 'code_event', 'code_data');
    }
    
    public function result()
    {
        return $this->hasMany(Result::class, 'code_event', 'code_data');
    }
}