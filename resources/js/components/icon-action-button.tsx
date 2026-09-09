import { Button, type ButtonProps } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Link } from '@inertiajs/react';
import { type LucideIcon } from 'lucide-react';

interface IconActionButtonProps extends Omit<ButtonProps, 'children' | 'size' | 'asChild'> {
    icon: LucideIcon;
    label: string;
    href?: string;
    asChild?: boolean;
}

/**
 * Botón de acción de datatable: ícono + tooltip con el nombre de la acción.
 * Con `href` navega vía Inertia Link; sin él actúa como botón normal (onClick),
 * y con `asChild` se puede anidar dentro de un DialogTrigger para confirmaciones.
 */
export function IconActionButton({ icon: Icon, label, href, variant = 'ghost', className, asChild, ...props }: IconActionButtonProps) {
    const button = href ? (
        <Button variant={variant} size="icon" className={className} asChild {...props}>
            <Link href={href} aria-label={label}>
                <Icon className="size-4" />
            </Link>
        </Button>
    ) : (
        <Button variant={variant} size="icon" className={className} aria-label={label} asChild={asChild} {...props}>
            <Icon className="size-4" />
        </Button>
    );

    return (
        <TooltipProvider delayDuration={200}>
            <Tooltip>
                <TooltipTrigger asChild>{button}</TooltipTrigger>
                <TooltipContent>{label}</TooltipContent>
            </Tooltip>
        </TooltipProvider>
    );
}
