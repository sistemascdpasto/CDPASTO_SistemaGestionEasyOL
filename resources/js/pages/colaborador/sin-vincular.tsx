import HeadingSmall from '@/components/heading-small';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { UserX } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

export default function SinVincular() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mi Portal" />
            <div className="flex h-full flex-1 flex-col items-center justify-center gap-4 rounded-xl p-4 text-center">
                <div className="flex size-16 items-center justify-center rounded-full bg-muted text-muted-foreground">
                    <UserX className="size-8" />
                </div>
                <HeadingSmall
                    title="Tu cuenta aún no está vinculada"
                    description="Contacta a un administrador para vincular tu cuenta con tu registro de colaborador y así poder ver tu información personal."
                />
            </div>
        </AppLayout>
    );
}
