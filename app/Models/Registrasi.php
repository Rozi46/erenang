<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registrasi extends Model
{
    protected $table = 'db_registrations';
    protected $fillable = [
        'id',
        'code_data',
        'code_champion',
        'code_athlete',
        'code_event',
        'code_age_group',
        'status',
        'payment_status',
        'documents',
        'notes',
        'submitted_at',
        'verified_at',
        'code_user',
        'created_at',
        'updated_at'
    ];
    
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public function championship()
    {
        return $this->belongsTo(Championship::class, 'code_champion', 'code_data');
    }

    public function atlet()
    {
        return $this->belongsTo(Atlet::class, 'code_athlete', 'code_data');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'code_event', 'code_data');
    }

    public function kelompokUmur()
    {
        return $this->belongsTo(kelompokUmur::class, 'code_age_group', 'code_data');
    }

}
