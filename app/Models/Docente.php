<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Docente extends Model
{
    // PK personalizada (la tabla usa id_docente, no el id por defecto)
    protected $primaryKey = 'id_docente';

    // Campos que se permiten asignar masivamente (create / update)
    protected $fillable = [
        'no_empleado',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'email',
        'activo',
    ];
}
