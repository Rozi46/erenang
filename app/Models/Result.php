<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $table = 'db_results';
    protected $fillable = [
        'id',
        'code_data',
        'code_athlete',
        'code_event',
        'hasil',
        'ranking',
        'catatan',
        'created_at',
        'updated_at'
    ];
    
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public function atlet()
    {
        return $this->belongsTo(Atlet::class, 'code_athlete', 'code_data');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'code_event', 'code_data');
    }
}
