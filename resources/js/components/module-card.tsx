import { SpotlightCard } from '@/components/spotlight-card';
import { Card, CardHeader, CardTitle } from '@/components/ui/card';
import { Link } from '@inertiajs/react';
import { ArrowRight, type LucideIcon } from 'lucide-react';

interface ModuleCardProps {
    title: string;
    href: string;
    icon: LucideIcon;
    color?: string;
}

export function ModuleCard({ title, href, icon: Icon, color = '#0065B9' }: ModuleCardProps) {
    return (
        <Link href={href} prefetch className="group block">
            <SpotlightCard color={color} className="h-full rounded-lg">
                <Card className="relative h-full overflow-hidden border-sidebar-border/70 transition-all duration-300 group-hover:-translate-y-0.5 group-hover:shadow-md dark:border-sidebar-border">
                    <span
                        className="absolute inset-x-0 top-0 h-1 rounded-t-lg opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                        style={{ backgroundColor: color }}
                    />
                    <CardHeader className="flex flex-col items-center gap-3 text-center">
                        <div
                            className="flex size-12 items-center justify-center rounded-full transition-transform duration-300 group-hover:scale-110"
                            style={{ backgroundColor: `${color}1a`, color }}
                        >
                            <Icon className="size-6" />
                        </div>
                        <CardTitle className="flex items-center gap-1 text-base">
                            {title}
                            <ArrowRight className="size-3.5 -translate-x-1 opacity-0 transition-all duration-300 group-hover:translate-x-0 group-hover:opacity-100" />
                        </CardTitle>
                    </CardHeader>
                </Card>
            </SpotlightCard>
        </Link>
    );
}
