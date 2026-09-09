import { Reveal } from '@/components/reveal';
import { Card, CardContent } from '@/components/ui/card';
import { findSubmodule } from '@/data/modules';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Construction } from 'lucide-react';

export default function Submodule({ module, submodule }: { module: string; submodule: string }) {
    const found = findSubmodule(module, submodule);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: found?.module.title ?? 'Módulo', href: `/modules/${module}` },
        { title: found?.submodule.title ?? 'Submódulo', href: `/modules/${module}/${submodule}` },
    ];

    const Icon = found?.submodule.icon ?? Construction;
    const accent = found?.module.accent ?? '#3F7A22';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={found?.submodule.title ?? 'Submódulo'} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <Reveal className="flex items-center gap-3">
                    <div className="flex size-11 shrink-0 items-center justify-center rounded-xl" style={{ backgroundColor: `${accent}1a`, color: accent }}>
                        <Icon className="size-6" />
                    </div>
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">{found?.submodule.title ?? 'Submódulo no encontrado'}</h1>
                        {found && <p className="text-muted-foreground">Módulo {found.module.title}</p>}
                    </div>
                </Reveal>

                <Reveal delay={100}>
                    <Card className="relative overflow-hidden border-sidebar-border/70 dark:border-sidebar-border">
                        <span className="absolute inset-x-0 top-0 h-1" style={{ backgroundColor: accent }} />
                        <CardContent className="flex min-h-[50vh] flex-col items-center justify-center gap-3 p-6 text-center">
                            <div
                                className="flex size-14 items-center justify-center rounded-full"
                                style={{ backgroundColor: `${accent}1a`, color: accent }}
                            >
                                <Icon className="size-7" />
                            </div>
                            <p className="max-w-sm text-muted-foreground">
                                Esta es una pantalla de prototipo. La funcionalidad de{' '}
                                <span className="font-medium text-foreground">{found?.submodule.title ?? submodule}</span> aún no está
                                implementada.
                            </p>
                        </CardContent>
                    </Card>
                </Reveal>
            </div>
        </AppLayout>
    );
}
