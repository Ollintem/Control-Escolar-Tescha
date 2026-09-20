<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    use HasFactory;

    protected $table = 'grupos';
    protected $primaryKey = 'id_grupo';

    protected $fillable = [
        'id_materia',
        'id_docente',
        'id_periodo',
        'nombre',
        'cupo_maximo',
        'activo',
    ];

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'id_docente', 'id_docente');
    }

    public function periodo()
    {
        return $this->belongsTo(PeriodoEscolar::class, 'id_periodo', 'id_periodo');
    }
}