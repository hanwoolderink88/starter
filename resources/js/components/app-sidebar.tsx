import { Link } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { History, LayoutGrid, ShieldCheck, Users } from 'lucide-react';
import { useMemo } from 'react';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermissions } from '@/hooks/use-permissions';
import { dashboard } from '@/routes';
import { index as activityIndex } from '@/routes/activity';
import { index as rolesIndex } from '@/routes/roles';
import { index as usersIndex } from '@/routes/users';
import type { NavItem } from '@/types';
import { Permission } from '@/types/generated';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { t } = useLaravelReactI18n();
    const { can } = usePermissions();

    const mainNavItems = useMemo<NavItem[]>(
        () => [
            {
                title: t('navigation.dashboard'),
                href: dashboard(),
                icon: LayoutGrid,
            },
        ],
        [t],
    );

    // Administration lives at the bottom of the sidebar, each item only for
    // whoever may open it.
    const footerNavItems = useMemo<NavItem[]>(() => {
        const items: NavItem[] = [];

        if (can(Permission.ViewUsers)) {
            items.push({
                title: t('navigation.users'),
                href: usersIndex(),
                icon: Users,
            });
        }

        if (can(Permission.ManageRoles)) {
            items.push({
                title: t('navigation.roles'),
                href: rolesIndex(),
                icon: ShieldCheck,
            });
        }

        if (can(Permission.ViewActivity)) {
            items.push({
                title: t('navigation.activity'),
                href: activityIndex(),
                icon: History,
            });
        }

        return items;
    }, [can, t]);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
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
