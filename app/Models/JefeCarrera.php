<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JefeCarrera extends Model
{
    use HasFactory;

    protected $table = 'jefes_carrera';
    protected $primaryKey = 'id_jefe_carrera';

    protected $fillable = [
        'id_carrera',
        'id_docente',
        'fecha_inicio',
        'fecha_fin',
        'activo',
    ];

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id_docente');
    }
}