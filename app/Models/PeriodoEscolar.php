<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodoEscolar extends Model
{
    protected $table = 'periodo_escolars';

    // PK personalizada (la tabla usa id_periodo, no el id por defecto)
    protected $primaryKey = 'id_periodo';

    // Campos que se permiten asignar masivamente (create / update)
    protected $fillable = [
        'clave',
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'activo',
    ];
}
