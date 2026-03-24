<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    ClipboardList,
    LayoutGrid,
    ListOrdered,
    Target,
    Trophy,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { Auth, NavItem } from '@/types';

const page = usePage<{ auth: Auth }>();

const mainNavItems = computed<NavItem[]>(() => {
    const user = page.props.auth?.user;

    if (!user?.role) {
        return [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
        ];
    }

    if (user.role === 'super_admin') {
        return [
            { title: 'Dashboard', href: '/admin/dashboard', icon: LayoutGrid },
            { title: 'Users', href: '/admin/users', icon: Users },
            { title: 'Event Setup', href: '/admin/event', icon: Target },
            { title: 'Score Gateway', href: '/admin/gateway', icon: BookOpen },
            { title: 'Contestants', href: '/admin/contestants', icon: ListOrdered },
            { title: 'Score Review', href: '/admin/scores', icon: ClipboardList },
            { title: 'Results', href: '/admin/results', icon: Trophy },
        ];
    }

    if (user.role === 'organizer') {
        return [
            { title: 'Dashboard', href: '/organizer/dashboard', icon: LayoutGrid },
            { title: 'Categories', href: '/organizer/categories', icon: Target },
            { title: 'Criteria', href: '/organizer/criteria', icon: ClipboardList },
            { title: 'Progress', href: '/organizer/progress', icon: BookOpen },
        ];
    }

    if (user.role === 'admin') {
        return [
            { title: 'Dashboard', href: '/judge/dashboard', icon: LayoutGrid },
            { title: 'Scoresheet', href: '/judge/scoresheet', icon: ClipboardList },
        ];
    }

    if (user.role === 'mc') {
        return [
            { title: 'Reveal', href: '/mc/reveal', icon: Trophy },
        ];
    }

    return [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];
});

const homeHref = computed(() => {
    const user = page.props.auth?.user;

    switch (user?.role) {
        case 'super_admin':
            return '/admin/dashboard';
        case 'organizer':
            return '/organizer/dashboard';
        case 'admin':
            return '/judge/dashboard';
        case 'mc':
            return '/mc/reveal';
        default:
            return dashboard();
    }
});

const footerNavItems: NavItem[] = [];

const roleSubtitle = computed(() => {
    const r = page.props.auth?.user?.role;
    if (r === 'super_admin') return 'Super Admin';
    if (r === 'organizer') return 'Organizer';
    if (r === 'admin') return 'Judge';
    if (r === 'mc') return 'MC';
    return '';
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child class="rounded-2xl! hover:bg-sidebar-accent">
                        <Link :href="homeHref" class="gap-3">
                            <AppLogo :subtitle="roleSubtitle" />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter v-if="footerNavItems.length" :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
