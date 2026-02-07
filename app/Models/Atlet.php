<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Atlet extends Model
{
    protected $table = 'db_athletes';
    protected $fillable = [
        'id',
        'code_data',
        'code_club',
        'nis',
        'nama',
        'gender',
        'tempat_lahir',
        'tanggal_lahir',
        'foto',
        'created_at',
        'updated_at'
    ];
    
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    
    public function club()
    {
        return $this->belongsTo(Club::class, 'code_club', 'code_data');
    }
    
    public function heatLines()
    {
        return $this->hasMany(HeatLine::class,'code_athlete', 'code_data');
    }
    
    public function registrasi()
    {
        return $this->hasMany(Registrasi::class,'code_athlete', 'code_data');
    }
    
    public function result()
    {
        return $this->hasMany(Result::class,'code_athlete', 'code_data');
    }
}
