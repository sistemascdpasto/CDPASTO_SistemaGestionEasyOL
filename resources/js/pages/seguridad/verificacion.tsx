import { Card, CardContent } from '@/components/ui/card';
import { Head } from '@inertiajs/react';
import { BadgeCheck } from 'lucide-react';

interface RegistroVerificado {
    colaborador: string | null;
    tipo: string;
    fecha: string;
    evaluacion: string | null;
    estado: string;
}

const EVALUACION_COLOR: Record<string, string> = {
    Apto: '#3F7A22',
    'Apto con Observaciones': '#E3A11E',
    'No Apto': '#D4102A',
};

export default function VerificacionPublica({ registro }: { registro: RegistroVerificado }) {
    const color = registro.evaluacion ? (EVALUACION_COLOR[registro.evaluacion] ?? '#3F7A22') : '#6B7280';

    return (
        <div className="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 text-center">
            <Head title="Verificación de registro" />
            <div className="flex size-16 items-center justify-center rounded-full" style={{ backgroundColor: `${color}1a`, color }}>
                <BadgeCheck className="size-8" />
            </div>
            <div className="space-y-1">
                <h1 className="text-2xl font-semibold tracking-tight">Registro auténtico</h1>
                <p className="text-muted-foreground max-w-sm text-sm">
                    Este código QR corresponde a un registro válido del Sistema Integral de Gestión EASY LOGÍSTICA.
                </p>
            </div>

            <Card className="w-full max-w-sm border-sidebar-border/70 dark:border-sidebar-border">
                <CardContent className="grid gap-2 p-6 text-left text-sm">
                    <p>
                        <span className="text-muted-foreground">Colaborador:</span> {registro.colaborador ?? '—'}
                    </p>
                    <p className="capitalize">
                        <span className="text-muted-foreground">Tipo de prueba:</span> {registro.tipo}
                    </p>
                    <p>
                        <span className="text-muted-foreground">Fecha:</span> {registro.fecha}
                    </p>
                    <p>
                        <span className="text-muted-foreground">Resultado:</span>{' '}
                        {registro.estado === 'programada' ? 'Pendiente (prueba programada)' : registro.evaluacion}
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
