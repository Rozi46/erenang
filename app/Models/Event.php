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
        'status_data',
        'created_at',
        'updated_at'
    ];
    
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';    
    
    public function championship()
    {
        return $this->belongsTo(Championship::class,'code_kejuaraan', 'code_data');
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

    public function heatLines()
    {
        return $this->hasManyThrough(
            HeatLine::class,
            Heat::class,
            'code_event', // FK di heats
            'code_heat',  // FK di heat_lines
            'code_data',  // PK event
            'code_data'   // PK heat
        );
    }
}