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
        'estado',
        'activo',
    ];

    /**
     * Estados posibles de un periodo escolar.
     *
     * "activo" (tinyint) se mantiene sincronizado: solo vale 1 cuando el
     * estado es 'Activo'. Así los paneles que consultan where('activo', 1)
     * siguen funcionando igual que antes.
     */
    public const ESTADOS = ['Próximo', 'Activo', 'Finalizado'];

    /** Valor por defecto de un periodo recién registrado. */
    public const ESTADO_DEFECTO = 'Próximo';
}
