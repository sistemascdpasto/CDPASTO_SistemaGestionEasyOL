<?php

namespace App\Services\Seguridad;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeGenerator
{
    public function generateSvg(string $data, int $size = 200): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($data);
    }
}
