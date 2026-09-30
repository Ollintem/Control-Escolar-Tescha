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
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'no_empleado',
        'email',
        'id_carrera',
        'fecha_inicio',
        'fecha_fin',
        'activo',
    ];

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }
}