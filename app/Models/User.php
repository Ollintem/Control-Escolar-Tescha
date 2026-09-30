<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'activo',
        'FK_id_rol',
        'id_docente',
        'id_jefe_carrera',
        'id_alumno',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'FK_id_rol', 'id_rol');
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id_docente');
    }

    public function jefeCarrera()
    {
        return $this->belongsTo(JefeCarrera::class, 'id_jefe_carrera', 'id_jefe_carrera');
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'id_alumno', 'id_alumno');
    }

    /**
     * Indica si el usuario pertenece al rol Administrador.
     * Se reconoce por el NOMBRE real del rol (relación role), nunca por un
     * ID fijo ni por la columna legacy users.rol.
     */
    public function esAdministrador(): bool
    {
        $nombre = $this->role?->nombre;

        return $nombre !== null && mb_strtolower(trim($nombre)) === 'administrador';
    }

    /**
     * Consulta centralizada y reutilizable de la Matriz de Permisos:
     *
     *     $user->tienePermiso('Docentes', 'editar');
     *
     * Resuelve users -> role -> permisos (rol_permiso) sin hardcodear los 44
     * permisos. La comparación es sensible a mayúsculas/minúsculas gracias a
     * la colación utf8mb4_unicode_ci, pero sí respeta acentos (usar los
     * nombres exactos de la tabla permisos: "Historial Académico", etc.).
     * El rol Administrador tiene acceso total por nombre de rol.
     */
    public function tienePermiso(string $modulo, string $accion): bool
    {
        if ($this->esAdministrador()) {
            return true;
        }

        $rol = $this->role;

        if ($rol === null) {
            return false;
        }

        return $rol->permisos()
            ->where('modulo', trim($modulo))
            ->where('accion', trim($accion))
            ->exists();
    }
}
