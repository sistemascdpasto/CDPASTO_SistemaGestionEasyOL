<?php

namespace Database\Seeders;

use App\Models\Seguridad\Recomendacion;
use Illuminate\Database\Seeder;

class RecomendacionSeeder extends Seeder
{
    /**
     * Catálogo inicial de recomendaciones de evaluación médica (HU-054),
     * tomado de los valores que trae el HU original. SST puede agregar,
     * editar o desactivar valores desde la pantalla de catálogo sin volver
     * a tocar código.
     */
    public function run(): void
    {
        $porCategoria = [
            'medica' => [
                'Uso de corrección visual para esfuerzos visuales',
                'Uso de corrección óptica para visión cercana',
                'Control médico por optometría y/u oftalmología',
                'Uso permanente de corrección óptica',
                'Uso de corrección visual / óptica',
                'Examen visual de control de un año',
                'Valoración EPS Optometría',
                'Control ambulatorio de tensión arterial y seguimiento médico pertinente',
                'Especialista nutrición, medicina interna',
                'Especialista cirugía vascular',
                'Usar medias antivarices permanente',
                'Usar medias antivarices - Compresión media',
                'Espirometría de control en un año',
                'Audiometría de control de un año',
                'Continuar manejo médico por Ortopedia',
                'Valoración otorrinolaringología',
                'Valoración por reumatología',
                'Valoración por EPS, manejo médico en EPS y control en 3 meses de laboratorios',
                'Cuidados generales con la piel - Hidratación tópica frecuente',
                'Valoración por EPS, manejo médico',
                'Especialista (por definir)',
                'Desparasitación anual (se deja fórmula particular)',
            ],
            'ocupacional' => [
                'Uso de EPP',
                'Continuar exámenes médicos periódicos',
                'Capacitación en prevención de riesgos',
                'Capacitación para mantener y mejorar higiene postural',
                'Capacitación en uso de herramientas y maquinarias',
                'Realizar levantamiento de cargas con técnicas apropiadas y de acuerdo con límites permisibles establecidos',
                'Cambio de posición de sedestación / bipedestación',
                'Adecuación de trabajo de acuerdo con ergonomía del trabajador',
                'Pausas activas e higiene postural',
                'Capacitación general en SST',
                'Realizar levantamiento de cargas con técnica apropiada y de acuerdo con límites permisibles establecidos',
                'PYP Riesgo cardiovascular',
                'PYP Conservación auditiva',
                'PYP Conservación visual',
                'PYP Lesiones osteomusculares',
                'Doble protección auditiva',
                'Uso de protección auditiva',
                'Cambiar posición durante la jornada, realizando movimientos dinámicos frecuentes de pies y piernas',
                'Mantener hábitos posturales durante la realización de las tareas',
                'Fortalecimiento muscular',
            ],
            'habitos' => [
                'Estilos de vida saludables',
                'Alimentación saludable y balanceada',
                'Disminución de peso con mejoría en hábitos de vida y seguimiento por médico nutricionista',
                'Control de peso',
                'Dieta balanceada baja en fritos y harinas',
                'Patrones alimenticios apropiados',
                'Dieta balanceada',
                'Deporte',
                'Actividad física aeróbica',
            ],
        ];

        foreach ($porCategoria as $categoria => $nombres) {
            foreach ($nombres as $nombre) {
                Recomendacion::firstOrCreate(['nombre' => $nombre], ['categoria' => $categoria, 'activo' => true]);
            }
        }
    }
}
