<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    protected $table = 'db_swimming_styles';
    protected $fillable = [
        'id',
        'code_data',
        'nama_gaya',
        'istilah',
        'created_at',
        'updated_at'
    ];
    
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public function event()
    {
        return $this->hasMany(Event::class, 'code_gaya', 'code_data');
    }
}
