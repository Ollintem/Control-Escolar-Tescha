<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Semestre extends Model
{
    // La tabla no tiene timestamps (la migración no los define)
    public $timestamps = false;

    protected $table = 'semestres';

    // PK personalizada (la tabla usa id_semestre)
    protected $primaryKey = 'id_semestre';

    // Campos que se permiten asignar masivamente (create / update)
    protected $fillable = [
        'id_carrera',
        'numero',
        'descripcion',
    ];

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }
}
