import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Camera, FlipHorizontal2, RefreshCw, Timer, TriangleAlert } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

/**
 * Diálogo para tomar una fotografía con la cámara del dispositivo y devolverla
 * como `File` (JPEG). Incluye:
 * - Temporizador configurable: 0 (inmediato), 5, 10 o 15 segundos.
 * - Cambio de cámara: frontal ↔ trasera.
 * - Fallback al selector de archivos si el navegador no soporta getUserMedia.
 */
export function CameraCaptureDialog({
    open,
    onOpenChange,
    onCapture,
    titulo = 'Tomar fotografía',
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onCapture: (file: File) => void;
    titulo?: string;
}) {
    const videoRef        = useRef<HTMLVideoElement>(null);
    const streamRef       = useRef<MediaStream | null>(null);
    const fallbackInputRef = useRef<HTMLInputElement>(null);
    const countdownRef    = useRef<ReturnType<typeof setInterval> | null>(null);

    const [error, setError]           = useState<string | null>(null);
    const [listo, setListo]           = useState(false);
    const [facing, setFacing]         = useState<'environment' | 'user'>('environment');
    const [temporizador, setTemp]     = useState<0 | 5 | 10 | 15>(0);
    const [cuenta, setCuenta]         = useState<number | null>(null); // null = no activo

    const detener = useCallback(() => {
        streamRef.current?.getTracks().forEach((t) => t.stop());
        streamRef.current = null;
        setListo(false);
        if (countdownRef.current) { clearInterval(countdownRef.current); countdownRef.current = null; }
        setCuenta(null);
    }, []);

    const iniciar = useCallback(async (facingMode: 'environment' | 'user' = facing) => {
        detener();
        setError(null);
        setListo(false);
        if (!navigator.mediaDevices?.getUserMedia) {
            setError('Este dispositivo o navegador no permite usar la cámara. Puedes adjuntar una foto desde el archivo.');
            return;
        }
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: facingMode } },
                audio: false,
            });
            streamRef.current = stream;
            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                await videoRef.current.play();
                setListo(true);
            }
        } catch {
            setError('No se pudo acceder a la cámara. Revisa los permisos del navegador o adjunta una foto desde el archivo.');
        }
    }, [facing, detener]);

    // Abrir/cerrar diálogo
    useEffect(() => {
        if (open) { void iniciar(facing); }
        else       { detener(); }
        return () => detener();
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    // Cambiar de cámara
    const flipCamara = () => {
        const nuevo = facing === 'environment' ? 'user' : 'environment';
        setFacing(nuevo);
        void iniciar(nuevo);
    };

    // Captura inmediata
    const capturarAhora = () => {
        const video = videoRef.current;
        if (!video || !video.videoWidth) return;
        const MAX_LADO = 1920;
        const escala   = Math.min(1, MAX_LADO / Math.max(video.videoWidth, video.videoHeight));
        const canvas   = document.createElement('canvas');
        canvas.width   = Math.round(video.videoWidth  * escala);
        canvas.height  = Math.round(video.videoHeight * escala);
        canvas.getContext('2d')?.drawImage(video, 0, 0, canvas.width, canvas.height);
        canvas.toBlob(
            (blob) => {
                if (!blob) return;
                onCapture(new File([blob], `foto-${Date.now()}.jpg`, { type: 'image/jpeg' }));
                onOpenChange(false);
            },
            'image/jpeg',
            0.85,
        );
    };

    // Captura con o sin temporizador
    const capturar = () => {
        if (temporizador === 0) { capturarAhora(); return; }

        setCuenta(temporizador);
        let restante = temporizador;
        countdownRef.current = setInterval(() => {
            restante -= 1;
            setCuenta(restante);
            if (restante <= 0) {
                clearInterval(countdownRef.current!);
                countdownRef.current = null;
                setCuenta(null);
                capturarAhora();
            }
        }, 1000);
    };

    const cancelarCuenta = () => {
        if (countdownRef.current) { clearInterval(countdownRef.current); countdownRef.current = null; }
        setCuenta(null);
    };

    const desdeArchivo = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) { onCapture(file); onOpenChange(false); }
        e.target.value = '';
    };

    const OPCIONES_TIMER: (0 | 5 | 10 | 15)[] = [0, 5, 10, 15];

    return (
        <Dialog open={open} onOpenChange={(v) => { cancelarCuenta(); onOpenChange(v); }}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{titulo}</DialogTitle>
                </DialogHeader>

                <div className="grid gap-3">
                    {error ? (
                        <div className="flex items-start gap-2 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
                            <TriangleAlert className="mt-0.5 size-3.5 shrink-0" />
                            {error}
                        </div>
                    ) : (
                        <div className="relative overflow-hidden rounded-lg border border-border bg-black">
                            <video ref={videoRef} playsInline muted className="aspect-video w-full object-cover" />

                            {/* Cuenta regresiva superpuesta */}
                            {cuenta !== null && (
                                <div className="absolute inset-0 flex items-center justify-center">
                                    <span className="text-7xl font-black text-white drop-shadow-[0_2px_8px_rgba(0,0,0,0.8)]">
                                        {cuenta}
                                    </span>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Controles: temporizador + flip */}
                    {!error && (
                        <div className="flex items-center justify-between gap-2">
                            {/* Selector de temporizador */}
                            <div className="flex items-center gap-1">
                                <Timer className="size-4 shrink-0 text-muted-foreground" />
                                {OPCIONES_TIMER.map((s) => (
                                    <button
                                        key={s}
                                        type="button"
                                        onClick={() => setTemp(s)}
                                        className={`rounded-full px-2.5 py-1 text-xs font-semibold transition-colors ${
                                            temporizador === s
                                                ? 'bg-primary text-primary-foreground'
                                                : 'bg-muted text-muted-foreground hover:bg-accent'
                                        }`}
                                    >
                                        {s === 0 ? 'Off' : `${s}s`}
                                    </button>
                                ))}
                            </div>

                            {/* Cambiar cámara */}
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                title={facing === 'environment' ? 'Cambiar a cámara frontal' : 'Cambiar a cámara trasera'}
                                onClick={flipCamara}
                                disabled={!listo}
                            >
                                <FlipHorizontal2 className="size-4" />
                            </Button>
                        </div>
                    )}

                    <input ref={fallbackInputRef} type="file" accept="image/*" capture="environment" className="hidden" onChange={desdeArchivo} />

                    <div className="flex flex-wrap justify-end gap-2">
                        <Button type="button" variant="outline" onClick={() => fallbackInputRef.current?.click()}>
                            Adjuntar desde archivo
                        </Button>

                        {error ? (
                            <Button type="button" variant="secondary" onClick={() => void iniciar(facing)}>
                                <RefreshCw className="size-4" />
                                Reintentar cámara
                            </Button>
                        ) : cuenta !== null ? (
                            <Button type="button" variant="destructive" onClick={cancelarCuenta}>
                                Cancelar ({cuenta}s)
                            </Button>
                        ) : (
                            <Button type="button" onClick={capturar} disabled={!listo}>
                                <Camera className="size-4" />
                                {temporizador === 0 ? 'Capturar' : `Capturar en ${temporizador}s`}
                            </Button>
                        )}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
