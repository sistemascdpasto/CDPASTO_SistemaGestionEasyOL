<?php

namespace App\Support\Seguridad;

/**
 * Única fuente de verdad de qué claves de `colaborador_documentos.campo`
 * (o columnas legacy de un solo archivo) existen por paso del wizard de
 * colaboradores (HU02). La usan tanto los Request classes (para las reglas
 * de validación) como ColaboradorController (para guardar/leer archivos),
 * así ambos lados siempre están de acuerdo.
 */
class ColaboradorDocumentoCampos
{
    /** Paso 1 — Datos personales. */
    public const PASO1 = [
        'documento_tipo_identificacion',
        'documento_libreta_militar',
        'documento_eps',
        'documento_pension', // AFP
        'documento_arl',
        'documento_runt',
        'documento_sena_carta_presentacion',
    ];

    /** Paso 2 — Requisitos del cargo. */
    public const PASO2 = [
        'documento_carnet_manejo_defensivo',
        'documento_certificado_manejo_defensivo',
        'documento_certificado_carga_pesada',
        'documento_certificados_terreno_plano',
    ];

    /** Paso 4 — Archivos digitales (incluye los reutilizados de pasos 1/2). */
    public const PASO4 = [
        // 4.1 Documentos personales
        'documento_cedula',
        'documento_hoja_vida',
        'documento_titulo_bachiller',
        'documento_titulo_tecnico',
        'documento_titulo_tecnologo',
        'documento_titulo_profesional',
        'documento_titulo_academico', // "Otro" título
        'documento_antecedentes_procuraduria',
        'documento_antecedentes_contraloria',
        'documento_antecedentes_policia',
        'documento_certificado_bancario',
        'documento_runt',

        // 4.2 Documentos de salud
        'documento_examen_medico_ocupacional',
        'documento_arl',
        'documento_certificado_comfamiliar',
        'documento_afiliacion_comfamiliar',
        'documento_eps',

        // 4.3 Requerimientos de tránsito
        'documento_licencia_conduccion',
        'documento_licencia_a1',
        'documento_licencia_a2',
        'documento_licencia_b1',
        'documento_licencia_b2',
        'documento_licencia_b3',
        'documento_licencia_c1',
        'documento_licencia_c2',
        'documento_licencia_c3',
        'documento_carnet_manejo_defensivo',
        'documento_certificado_manejo_defensivo',
        'documento_certificado_carga_pesada',
        'documento_simit',
        'documento_recordatorio_vehiculo_licencia_conduccion',

        // 4.4 Documentación contrato
        'documento_contrato',
        'documento_carnet_ingreso_cd',
        'documento_preaviso_terminacion',
        'documento_prorroga',

        // 4.5 Documentación aprendiz
        'documento_sena_carta_presentacion',

        // 4.6 Documentación People
        'documento_induccion',
        'documento_reinduccion',
        'documento_induccion_pilares',

        // 4.7 Documentación plan padrino
        'documento_plan_padrino',

        // 4.8 / 4.9 / 4.10
        'documento_llamado_atencion',
        'documento_compromisos',
        'documento_escuela_pilotos',
        'documento_certificado_brigadista',
    ];

    /**
     * Todas las claves de documento conocidas, sin duplicados — usada por la
     * pantalla de edición clásica y por documentPaths() para fusionar
     * columnas legacy + colaborador_documentos.
     *
     * @return array<int, string>
     */
    public static function todas(): array
    {
        return array_values(array_unique([...self::PASO1, ...self::PASO2, ...self::PASO4]));
    }
}
