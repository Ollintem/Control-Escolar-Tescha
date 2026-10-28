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

    /**
     * Docentes que PERTENECEN a esta carrera (N:N, relación inversa).
     *
     * No confundir con los docentes que imparten grupos de esta carrera
     * (docentes -> grupos -> materias -> carreras): un docente puede
     * impartir clases en una carrera sin pertenecer formalmente a ella.
     */
    public function docentes()
    {
        return $this->belongsToMany(
            Docente::class,
            'carrera_docente',
            'id_carrera',
            'id_docente'
        );
    }
}
