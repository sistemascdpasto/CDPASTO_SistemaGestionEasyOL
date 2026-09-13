import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import {
    capacitacionesSubmodule,
    colaboradoresReadOnlySubmodule,
    modules,
    type ModuleDef,
    type SubModuleDef,
} from '@/data/modules';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    BellRing,
    DollarSign,
    Gift,
    GraduationCap,
    HeartPulse,
    LayoutGrid,
    Stethoscope,
    TestTube,
    Trophy,
    User,
    UserCog,
} from 'lucide-react';
import AppLogo from './app-logo';

const footerNavItems: NavItem[] = [];

function buildSubNavItems(submodules: SubModuleDef[], moduleSlug: string, color: string, userRoles: string[]): NavItem[] {
    return submodules
        .filter((sub) => !sub.allowedRoles || sub.allowedRoles.some((r) => userRoles.includes(r)))
        .map((sub) =>
            sub.submodules
                ? {
                      title: sub.title,
                      url: '#',
                      icon: sub.icon,
                      color,
                      items: buildSubNavItems(sub.submodules, moduleSlug, color, userRoles),
                  }
                : {
                      title: sub.title,
                      url: sub.href ?? (sub.slug ? `/modules/${sub.moduleSlugOverride ?? moduleSlug}/${sub.slug}` : `/modules/${moduleSlug}`),
                      icon: sub.icon,
                      color,
                      shared: sub.shared,
                  },
        );
}

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;

    // Colaboradores es propiedad de Gente (crear/editar/importar/eliminar).
    // Seguridad y Flota conservan acceso de solo lectura, así que
    // ven el mismo enlace inyectado como submódulo dentro de SU PROPIA
    // sección — no una sección "Gente" ajena — mientras no tengan el rol
    // Gente (que ya trae la entrada real en su propia sección) ni sean
    // Administrador (que ve todos los módulos completos igual).
    const showColaboradoresReadOnlyLink =
        !auth.isAdmin && !auth.roles.includes('Gente') && ['Seguridad', 'Flota'].some((role) => auth.roles.includes(role));

    // Capacitaciones es transversal: se inyecta como submódulo dentro de la
    // sección de cada pilar visible en vez de vivir suelto en el sidebar.
    const inyectarSubmodulos = (mod: ModuleDef): ModuleDef => {
        const inyectados: SubModuleDef[] = [];
        if (showColaboradoresReadOnlyLink && mod.slug !== 'gente') {
            inyectados.push(colaboradoresReadOnlySubmodule);
        }
        return { ...mod, submodules: [...inyectados, ...mod.submodules, capacitacionesSubmodule] };
    };

    const visibleModules: ModuleDef[] = (auth.isAdmin ? modules : modules.filter((mod) => auth.accessibleModules.includes(mod.slug))).map(
        inyectarSubmodulos,
    );

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            url: '/dashboard',
            icon: LayoutGrid,
        },
        ...visibleModules.map((mod) => ({
            title: mod.title,
            url: `/modules/${mod.slug}`,
            icon: mod.icon,
            color: mod.accent,
            items: buildSubNavItems(mod.submodules, mod.slug, mod.accent, auth.isAdmin ? ['Administrador'] : auth.roles),
        })),
        ...(auth.isColaborador
            ? [
                  { title: 'Mi Perfil', url: '/portal/perfil', icon: User, color: '#3F7A22' },
                  { title: 'Mis Pruebas', url: '/portal/pruebas', icon: TestTube, color: '#3F7A22' },
                  { title: 'Mi Plan Premiación', url: '/portal/mi-plan-premiacion', icon: Trophy, color: '#D97706' },
                  { title: 'Mis Incentivos', url: '/portal/mis-incentivos', icon: Gift, color: '#D97706' },
                  { title: 'Mi Variable', url: '/portal/mi-variable', icon: DollarSign, color: '#D97706' },
                  { title: 'Condición de Salud', url: '/portal/condicion-salud', icon: HeartPulse, color: '#3F7A22' },
                  { title: 'Encuesta de Morbilidad', url: '/portal/encuesta-morbilidad', icon: Stethoscope, color: '#3F7A22' },
                  { title: 'Mis Capacitaciones', url: '/portal/capacitaciones', icon: GraduationCap, color: '#0D9488' },
                  { title: 'Alertas', url: '/portal/alertas', icon: BellRing, color: '#3F7A22' },
              ]
            : []),
        ...(auth.isAdmin
            ? [
                  {
                      title: 'Gestión de Usuarios',
                      url: '/admin/users',
                      icon: UserCog,
                      color: '#6B21A8',
                  },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
