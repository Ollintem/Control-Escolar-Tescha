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

    /**
     * Carreras a las que PERTENECE formalmente el docente (N:N).
     *
     * Se guardan en la tabla intermedia `carrera_docente` y se asignan de
     * forma explícita desde los módulos Docentes o Usuarios y Roles.
     * NO se deducen de las materias ni de los grupos que imparta: esos
     * son "los grupos que imparte", una relación académica distinta.
     */
    public function carreras()
    {
        return $this->belongsToMany(
            Carrera::class,
            'carrera_docente',
            'id_docente',
            'id_carrera'
        );
    }
}
