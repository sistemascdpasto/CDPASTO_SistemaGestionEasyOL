import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { forwardRef, useEffect, useImperativeHandle, useRef, useState } from 'react';

export interface FirmaPadHandle {
    getFile: () => Promise<File | null>;
    limpiar: () => void;
}

export const FirmaPad = forwardRef<FirmaPadHandle, { firmaExistente?: string | null }>(
    function FirmaPad({ firmaExistente }, ref) {
        const canvasRef  = useRef<HTMLCanvasElement>(null);
        const drawing    = useRef(false);
        const [hasSignature, setHasSignature] = useState(false);
        const [cargando,     setCargando]     = useState(false);

        const getCtx = () => canvasRef.current?.getContext('2d') ?? null;

        // ── Cargar firma existente en el canvas ──────────────────────────────
        useEffect(() => {
            const canvas = canvasRef.current;
            const ctx    = getCtx();
            if (!canvas || !ctx) return;

            ctx.clearRect(0, 0, canvas.width, canvas.height);
            setHasSignature(false);

            if (!firmaExistente) return;

            setCargando(true);

            // Fetch → blob → drawImage (evita restricciones CORS de new Image())
            fetch(firmaExistente)
                .then((r) => r.blob())
                .then((blob) => {
                    const url = URL.createObjectURL(blob);
                    const img = new window.Image();
                    img.onload = () => {
                        const c = canvasRef.current;
                        const x = c?.getContext('2d');
                        if (c && x) {
                            x.clearRect(0, 0, c.width, c.height);
                            x.drawImage(img, 0, 0, c.width, c.height);
                            setHasSignature(true);
                        }
                        URL.revokeObjectURL(url);
                        setCargando(false);
                    };
                    img.onerror = () => { URL.revokeObjectURL(url); setCargando(false); };
                    img.src = url;
                })
                .catch(() => setCargando(false));
        // eslint-disable-next-line react-hooks/exhaustive-deps
        }, [firmaExistente]);

        // ── Dibujo manual ────────────────────────────────────────────────────
        const posFromEvent = (e: React.PointerEvent<HTMLCanvasElement>) => {
            const rect = canvasRef.current!.getBoundingClientRect();
            const scaleX = canvasRef.current!.width  / rect.width;
            const scaleY = canvasRef.current!.height / rect.height;
            return { x: (e.clientX - rect.left) * scaleX, y: (e.clientY - rect.top) * scaleY };
        };

        const start = (e: React.PointerEvent<HTMLCanvasElement>) => {
            drawing.current = true;
            const ctx = getCtx();
            const { x, y } = posFromEvent(e);
            ctx?.beginPath();
            ctx?.moveTo(x, y);
        };

        const move = (e: React.PointerEvent<HTMLCanvasElement>) => {
            if (!drawing.current) return;
            const ctx = getCtx();
            if (!ctx) return;
            const { x, y } = posFromEvent(e);
            ctx.lineWidth   = 2;
            ctx.lineCap     = 'round';
            ctx.strokeStyle = '#111827';
            ctx.lineTo(x, y);
            ctx.stroke();
            setHasSignature(true);
        };

        const end = () => { drawing.current = false; };

        const limpiar = () => {
            const canvas = canvasRef.current;
            const ctx    = getCtx();
            if (canvas && ctx) ctx.clearRect(0, 0, canvas.width, canvas.height);
            setHasSignature(false);
        };

        // ── Handle imperativo ────────────────────────────────────────────────
        useImperativeHandle(ref, () => ({
            getFile: () =>
                new Promise((resolve) => {
                    const canvas = canvasRef.current;
                    if (!canvas || !hasSignature) { resolve(null); return; }
                    canvas.toBlob(
                        (blob) => resolve(blob ? new File([blob], 'firma.png', { type: 'image/png' }) : null),
                        'image/png',
                    );
                }),
            limpiar,
        }));

        return (
            <div className="grid gap-2">
                <div className="flex items-center justify-between">
                    <Label>
                        Firma del colaborador
                        {firmaExistente && !cargando && (
                            <span className="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                Precargada
                            </span>
                        )}
                        {cargando && (
                            <span className="ml-2 text-[10px] text-muted-foreground">Cargando…</span>
                        )}
                        <span className="ml-1 font-normal text-muted-foreground">(opcional)</span>
                    </Label>
                    {hasSignature && (
                        <Button type="button" variant="ghost" size="sm" onClick={limpiar}>
                            Limpiar
                        </Button>
                    )}
                </div>
                <canvas
                    ref={canvasRef}
                    width={400}
                    height={150}
                    className="touch-none rounded-md border border-input bg-white w-full"
                    onPointerDown={start}
                    onPointerMove={move}
                    onPointerUp={end}
                    onPointerLeave={end}
                />
                <p className="text-muted-foreground text-xs">
                    {firmaExistente
                        ? 'Firma precargada del registro anterior. Dibuja encima para reemplazarla o usa Limpiar.'
                        : 'Dibuja la firma con el dedo o el mouse.'}
                </p>
            </div>
        );
    },
);
