<?php

namespace App\Notifications\Gente;

use App\Models\Seguridad\Colaborador;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\File;

class NotificacionPruebaPeriodoColaborador extends Notification
{

    public function __construct(
        public readonly Colaborador $colaborador,
        public readonly string $etapaKey,
        public readonly string $etapaLabel,
        public readonly string $fechaFormateada
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $asunto = "Hoy debes realizar tu prueba de {$this->etapaLabel}";
        
        $nombresArr = explode(' ', trim($this->colaborador->nombres));
        $primerNombre = $nombresArr[0] ?? $this->colaborador->nombres;

        $mailMessage = (new MailMessage)
            ->subject($asunto);

        // Escanear la carpeta public/images/emails/pruebas-periodo para adjuntar imágenes subidas por el usuario
        $imagenes = [];
        $folderPath = public_path('images/emails/pruebas-periodo');

        if (File::isDirectory($folderPath)) {
            $files = File::files($folderPath);
            foreach ($files as $file) {
                if ($file->getFilename() === '.gitkeep') {
                    continue;
                }
                $ext = strtolower($file->getExtension());
                if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'])) {
                    $imagenes[] = asset('images/emails/pruebas-periodo/' . $file->getFilename());
                    $mailMessage->attach($file->getRealPath(), [
                        'as' => $file->getFilename(),
                        'mime' => File::mimeType($file->getRealPath()),
                    ]);
                }
            }
        }

        return $mailMessage->view('emails.prueba_periodo_colaborador', [
            'colaborador' => $this->colaborador,
            'nombreCompleto' => $this->colaborador->nombre_completo,
            'cedula' => $this->colaborador->cedula,
            'cargo' => $this->colaborador->cargo ?? 'Sin cargo',
            'primerNombre' => $primerNombre,
            'etapaLabel' => $this->etapaLabel,
            'fechaFormateada' => $this->fechaFormateada,
            'imagenes' => $imagenes,
        ]);
    }
}
