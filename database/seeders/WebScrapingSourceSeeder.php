<?php

namespace Database\Seeders;

use App\Models\Seguridad\WebScrapingSource;
use Illuminate\Database\Seeder;

class WebScrapingSourceSeeder extends Seeder
{
    /**
     * El glosario oficial de INVÍAS (invias.gov.co/glosario) esta paginado por
     * letra. Cada pagina lista los terminos como <h2 id="aNNN">Termino</h2>
     * seguido de un <div class="termino"> con la definicion.
     */
    public function run(): void
    {
        $letras = [...range('a', 'z'), 'Otros'];

        foreach ($letras as $letra) {
            WebScrapingSource::updateOrCreate(
                ['url' => "https://www.invias.gov.co/glosario/{$letra}"],
                [
                    'nombre_fuente' => "INVÍAS Glosario - {$letra}",
                    'selector_css' => 'h2[id^="a"]',
                    'categoria' => 'GLOSARIO TÉCNICO INVÍAS',
                    'activo' => true,
                ]
            );
        }
    }
}
