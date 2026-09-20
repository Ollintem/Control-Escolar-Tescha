<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanEstudio extends Model
{
    use HasFactory;

    protected $table = 'plan_estudios';
    protected $primaryKey = 'id_plan_estudio';

    protected $fillable = [
        'id_carrera',
        'clave',
        'nombre',
        'anio_publicacion',
        'activo',
    ];

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }
}