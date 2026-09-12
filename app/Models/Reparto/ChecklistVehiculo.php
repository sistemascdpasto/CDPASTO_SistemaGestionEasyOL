<?php

namespace App\Models\Reparto;

use App\Models\Seguridad\Colaborador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistVehiculo extends Model
{
    protected $table = 'checklist_vehiculos';

    protected $fillable = [
        'id_form', 'estado', 'fecha', 'fecha_fin',
        'id_centro', 'id_regional', 'regional', 'centro', 'operacion',
        'cedula_conductor', 'placa_vehiculo', 'odometro',
        'salud_descanso', 'libre_medicamentos', 'fugas',
        'testigos_presion_aire', 'freno_parqueo', 'kit_reparto',
        'inventario', 'capacidad_vehiculo', 'condiciones_operar',
        'documentos_operar', 'licencia_vigente', 'licencia_original',
        'tecnomecanica', 'soat_vigente',
        'kit_totalidad', 'repuestos_buen_estado', 'extintor',
        'extintor_vigente', 'botiquin_condiciones', 'linterna_condiciones', 'kit_basico',
        'niveles_totalidad', 'combustible_suficiente', 'nivel_combustible',
        'liquido_embrague', 'refrigerante_estado', 'aceite_estado',
        'estado_hidraulico', 'aceite_caja', 'agua_limpiabrisas',
        'cumple_llantas', 'bandas_rodamientos', 'deformaciones_costados', 'labrado_profundidad',
        'cumple_visibilidad', 'estado_panoramico', 'estado_retrovisores',
        'estado_limpiabrisas', 'estado_cinturones', 'estado_colapies',
        'cerrar_fuera', 'estado_dashcam', 'estado_vidrios',
        'cumple_luces', 'luces_freno', 'estado_principales', 'luces_reserva',
        'luces_direccionales', 'luces_estacionarias', 'luces_laterales',
        'estado_pito', 'estado_pito_reserva', 'cumple_audible',
        'cumple_carroceria', 'estado_correas', 'estado_parales',
        'estado_cortinas', 'estado_chapas',
        'cumple_carretilla', 'cuenta_etiqueta', 'llantas_rodamientos_dos',
        'estado_carretilla_dos', 'carretilla_dos', 'etiqueta',
        'estado_rodamiento', 'estado_carretilla_uno', 'carretilla_uno',
        'observaciones', 'firma_conductor', 'conductor_operar',
        'vehiculo_operar', 'vehiculo_bitren', 'estado_bitren',
        'nombre_flota', 'apellido_flota', 'firma_responsable', 'codigo_responsable', 'estado_form',
        'cumpl', 'meta_td', 'tiempo_ejecucion',
        'mes', 'semana', 'anio', 'dia', 'meta', 'cumpl_meta',
    ];

    protected $casts = [
        'fecha'      => 'datetime',
        'fecha_fin'  => 'datetime',
        'cumpl'      => 'float',
        'cumpl_meta' => 'float',
        'mes'        => 'integer',
        'semana'     => 'integer',
        'anio'       => 'integer',
        'dia'        => 'integer',
    ];

    /** Relación con el colaborador conductor */
    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'cedula_conductor', 'cedula');
    }
}
