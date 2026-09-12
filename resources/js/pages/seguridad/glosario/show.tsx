import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';

interface GlossaryTermDetail {
    id: number;
    nombre: string;
    definicion: string;
    categoria: string;
    pregunta_numero: string | null;
    representacion: string | null;
    enlaces_de_interes: string | null;
    source: 'manual' | 'scraped';
    created_at: string;
    updated_at: string;
}

export default function GlosarioShow({ term }: { term: GlossaryTermDetail }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Seguridad', href: '/modules/seguridad' },
        { title: 'Glosario', href: '/modules/seguridad/glosario' },
        { title: term.nombre, href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={term.nombre} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-start justify-between gap-4">
                    <div className="space-y-1">
                        <p className="text-sm text-muted-foreground">{term.categoria}</p>
                        <h1 className="text-2xl font-semibold">{term.nombre}</h1>
                        {term.pregunta_numero && (
                            <Badge variant="outline">Pregunta {term.pregunta_numero}</Badge>
                        )}
                    </div>
                    <Badge variant={term.source === 'scraped' ? 'default' : 'outline'}>
                        {term.source === 'scraped' ? 'Web' : 'Manual'}
                    </Badge>
                </div>

                <div className="grid max-w-2xl gap-6">
                    <div className="space-y-2">
                        <h2 className="text-sm font-medium text-muted-foreground">Definición</h2>
                        <p className="leading-relaxed">{term.definicion}</p>
                    </div>

                    {term.representacion && (
                        <div className="space-y-2">
                            <h2 className="text-sm font-medium text-muted-foreground">Representación</h2>
                            <p className="leading-relaxed">{term.representacion}</p>
                        </div>
                    )}

                    {term.enlaces_de_interes && (
                        <div className="space-y-2">
                            <h2 className="text-sm font-medium text-muted-foreground">Enlace de Interés</h2>
                            <a
                                href={term.enlaces_de_interes}
                                target="_blank"
                                rel="noreferrer"
                                className="flex items-center gap-1 text-primary underline underline-offset-4"
                            >
                                <ExternalLink className="size-4" />
                                {term.enlaces_de_interes}
                            </a>
                        </div>
                    )}

                    <div className="space-y-1 text-xs text-muted-foreground">
                        <p>Creado: {new Date(term.created_at).toLocaleString('es-CL')}</p>
                        <p>Actualizado: {new Date(term.updated_at).toLocaleString('es-CL')}</p>
                    </div>
                </div>

                <div className="flex gap-3">
                    <Button asChild>
                        <Link href={route('seguridad.glosario.edit', term.id)}>Editar</Link>
                    </Button>
                    <Button variant="outline" onClick={() => window.history.back()}>Volver</Button>
                </div>
            </div>
        </AppLayout>
    );
}
