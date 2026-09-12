<?php

namespace Database\Seeders;

use App\Models\Seguridad\GlossaryTerm;
use Illuminate\Database\Seeder;

class GlossarySeeder extends Seeder
{
    public function run(): void
    {
        $terms = [
            [
                'nombre' => 'Señalización horizontal',
                'pregunta_numero' => '2.1',
                'definicion' => 'Corresponde a la aplicación de marcas viales, tales como líneas, flechas, cifras, letras, símbolos y dispositivos, que se pintan o colocan sobre la calzada, sardineles y obras de ingeniería vial, para orientar y regular el tránsito vehicular y peatonal.',
                'categoria' => 'SEÑALIZACIÓN DE LA VÍA',
            ],
            [
                'nombre' => 'Señalización Vertical',
                'pregunta_numero' => '2.2',
                'definicion' => 'Toda señal instalada al costado o sobre el camino, destinada a entregar información a los usuarios, mediante el uso de leyendas y/o símbolos.',
                'categoria' => 'SEÑALIZACIÓN DE LA VÍA',
            ],
            [
                'nombre' => 'Señalización Vertical: Reglamentarias',
                'pregunta_numero' => '2.3',
                'definicion' => 'Son las encargadas de notificar a los usuarios de las vías las prioridades en el uso de las mismas, así como las prohibiciones, restricciones, obligaciones y autorizaciones existentes.',
                'categoria' => 'SEÑALIZACIÓN DE LA VÍA',
            ],
            [
                'nombre' => 'Señalización Vertical: Preventivas',
                'pregunta_numero' => '2.4',
                'definicion' => 'Son responsables de advertir a los usuarios sobre la existencia y naturaleza de riesgos y/o situaciones imprevistas presentes en la vía o en sus zonas adyacentes, ya sea en forma permanente o temporal.',
                'categoria' => 'SEÑALIZACIÓN DE LA VÍA',
            ],
            [
                'nombre' => 'Señalización Vertical: Informativas',
                'pregunta_numero' => '2.5',
                'definicion' => 'Estas señales tienen la finalidad de guiar a los usuarios a lo largo de su itinerario por las vías públicas y de suministrarles cualquier otra información que pueda serles útil.',
                'categoria' => 'SEÑALIZACIÓN DE LA VÍA',
            ],
        ];

        foreach ($terms as $term) {
            GlossaryTerm::firstOrCreate(
                ['nombre' => $term['nombre'], 'categoria' => $term['categoria']],
                array_merge($term, ['source' => 'manual'])
            );
        }
    }
}
