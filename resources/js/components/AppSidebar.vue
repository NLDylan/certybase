<script setup lang="ts">
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
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Palette, Megaphone, Award } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';
import OrganizationSwitcher from './OrganizationSwitcher.vue';

const page = usePage();
const currentOrganization = computed(() => {
    const org = page.props.organization as { id: string; name: string } | null;
    return org;
});

const mainNavItems: NavItem[] = computed(() => {
    const orgId = currentOrganization.value?.id;
    const baseItems: NavItem[] = [
    {
        title: 'Dashboard',
            href: '/dashboard',
        icon: LayoutGrid,
    },
];

    if (orgId) {
        baseItems.push(
            {
                title: 'Designs',
                href: '/designs',
                icon: Palette,
            },
            {
                title: 'Campaigns',
                href: '/campaigns',
                icon: Megaphone,
            },
            {
                title: 'Certificates',
                href: '/certificates',
                icon: Award,
            }
        );
    }

    return baseItems;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="'/dashboard'">
                        <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <div class="px-2 pt-2">
                <OrganizationSwitcher />
            </div>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>