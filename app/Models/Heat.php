<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Heat extends Model
{
    protected $table = 'db_heats';
    protected $fillable = [
        'id',
        'code_data',
        'code_event',
        'nomor_seri',
        'created_at',
        'updated_at'
    ];
    
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    
    public function event()
    {
        return $this->belongsTo(Event::class, 'code_event', 'code_data');
    }

    public function heatLines()
    {
        return $this->hasMany(HeatLine::class, 'code_heat', 'code_data');
    }

}
