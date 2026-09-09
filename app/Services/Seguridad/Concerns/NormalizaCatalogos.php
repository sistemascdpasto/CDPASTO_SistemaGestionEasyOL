<?php

namespace App\Services\Seguridad\Concerns;

/**
 * Helpers para comparar valores de texto libre (como vienen en los Excel
 * reales) contra catálogos conocidos, tolerando tildes/mayúsculas/prefijos.
 * Usado por los importadores de colaboradores y de ACIS.
 */
trait NormalizaCatalogos
{
    /**
     * Quita tildes/mayúsculas/lo que no sea alfanumérico, para comparar
     * catálogos sin que un tilde faltante o un guion de más rompa el match.
     */
    private function normalizar(string $valor): string
    {
        // mb_strtoupper (no strtoupper) porque una tilde en minúscula (ej.
        // "reportó") no la sube a mayúscula el strtoupper de un solo byte,
        // y entonces no calza con el mapa de abajo y se termina descartando
        // como si fuera basura en vez de convertirse en "O".
        $sinTildes = strtr(mb_strtoupper(trim($valor), 'UTF-8'), [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N',
        ]);

        return preg_replace('/[^A-Z0-9]/', '', $sinTildes) ?? '';
    }

    /**
     * @param  array<int, string>  $catalogo
     */
    private function coincidirExacto(string $valorCrudo, array $catalogo): string
    {
        $normalizado = $this->normalizar($valorCrudo);

        foreach ($catalogo as $opcion) {
            if ($normalizado === $this->normalizar($opcion)) {
                return $opcion;
            }
        }

        return trim($valorCrudo);
    }

    /**
     * Para catálogos donde el Excel trae variantes con prefijos/sufijos
     * ("EPS-S EMSSANAR", "NUEVA E.P.S. S.A.") que no calzan con un match
     * exacto, así que se acepta que el valor de la fila *contenga* el
     * nombre canónico.
     *
     * @param  array<int, string>  $catalogo
     */
    private function coincidirParcial(string $valorCrudo, array $catalogo): string
    {
        $normalizado = $this->normalizar($valorCrudo);

        foreach ($catalogo as $opcion) {
            if ($normalizado !== '' && str_contains($normalizado, $this->normalizar($opcion))) {
                return $opcion;
            }
        }

        return trim($valorCrudo);
    }
}
