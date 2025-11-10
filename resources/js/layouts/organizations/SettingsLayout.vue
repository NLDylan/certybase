<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { toUrl, urlIsActive } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const organization = computed(() => page.props.organization as { id: string; name: string } | null);

const sidebarNavItems: NavItem[] = [
    {
        title: 'General',
        href: '/organization/settings',
    },
    {
        title: 'Branding',
        href: '/organization/settings/branding',
    },
    {
        title: 'Users',
        href: '/organization/users',
    },
    {
        title: 'Subscription',
        href: '/organization/subscription',
    },
];

const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            :title="organization?.name ?? 'Organization Settings'"
            description="Manage your organization settings and preferences"
        />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-y-1 space-x-0">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            { 'bg-muted': urlIsActive(item.href, currentPath) },
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 lg:max-w-4xl xl:max-w-5xl">
                <section class="w-full space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>

