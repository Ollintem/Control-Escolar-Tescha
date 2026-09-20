<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    protected $table = 'permisos';
    protected $primaryKey = 'id_permiso';

    protected $fillable = [
        'modulo',
        'accion',
        'descripcion',
    ];

    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'rol_permiso',
            'FK_id_permiso',
            'FK_id_rol'
        );
    }
}