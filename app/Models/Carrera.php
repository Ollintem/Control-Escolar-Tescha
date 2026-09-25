<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    // PK personalizada (la tabla usa id_carrera, no el id por defecto)
    protected $primaryKey = 'id_carrera';

    // Campos que se permiten asignar masivamente (create / update)
    protected $fillable = [
        'clave',
        'nombre',
        'reticula_oficial',
        'activo',
    ];
}
