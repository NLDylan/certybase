<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Building2, ChevronsUpDown, Plus } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import {
    SidebarMenu,
    SidebarMenuItem,
    SidebarMenuButton,
    useSidebar,
} from '@/components/ui/sidebar';

type OrgOption = { id: string; name: string; icon_url?: string | null; logo_url?: string | null; has_growth_plan?: boolean };

const page = usePage();
const { isMobile } = useSidebar();

const currentOrg = computed<OrgOption | null>(() => {
    const org = page.props.organization as OrgOption | null;
    return org ? { id: org.id, name: org.name, icon_url: org.icon_url, logo_url: org.logo_url, has_growth_plan: org.has_growth_plan } : null;
});

const organizations = computed<OrgOption[]>(() => {
    const orgs = (page.props.organizations as Array<OrgOption>) || [];
    return orgs;
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                    >
                        <div class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-lg bg-sidebar-accent text-sidebar-accent-foreground ring-1 ring-sidebar-border dark:ring-sidebar-border/60">
                            <img
                                v-if="currentOrg?.icon_url"
                                :src="currentOrg.icon_url"
                                alt="Org icon"
                                class="h-full w-full object-cover mix-blend-multiply dark:mix-blend-screen"
                            />
                            <Building2 v-else class="size-4" />
                        </div>
                        <div class="grid flex-1 text-left text-sm leading-tight min-w-0">
                            <span class="truncate font-semibold" :title="currentOrg?.name || 'Select organization'">
                                <template v-if="currentOrg?.has_growth_plan && currentOrg?.logo_url">
                                <img
                                    :src="currentOrg.logo_url"
                                    alt="Org logo"
                                    class="inline-block max-h-5 align-middle mix-blend-multiply dark:mix-blend-screen"
                                />
                                </template>
                                <template v-else>
                                    {{ currentOrg?.name || 'Select organization' }}
                                </template>
                            </span>
                            <span class="truncate text-xs">Organization</span>
                        </div>
                        <ChevronsUpDown class="ml-auto" />
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </DropdownMenuTrigger>
        <DropdownMenuContent
            class="w-[--reka-dropdown-menu-trigger-width] min-w-56 rounded-lg"
            align="start"
            :side="isMobile ? 'bottom' : 'right'"
            :side-offset="4"
        >
            <DropdownMenuLabel class="text-xs text-muted-foreground">Organizations</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <div class="max-h-72 overflow-auto">
                <DropdownMenuItem
                    v-for="(org, index) in organizations"
                    :key="org.id"
                    class="gap-2 p-2"
                    as-child
                >
                    <Link :href="`/organizations/${org.id}/switch`" method="post" preserve-scroll class="flex items-center gap-2 w-full">
                        <div class="flex size-6 items-center justify-center overflow-hidden rounded-sm border bg-sidebar-accent text-sidebar-accent-foreground dark:border-sidebar-border/60">
                            <img
                                v-if="org.icon_url"
                                :src="org.icon_url"
                                alt="Org icon"
                                class="h-full w-full object-cover mix-blend-multiply dark:mix-blend-screen"
                            />
                            <Building2 v-else class="size-4 shrink-0" />
                        </div>
                        <span class="truncate">
                            <template v-if="org.has_growth_plan && org.logo_url">
                                <img
                                    :src="org.logo_url"
                                    alt="Org logo"
                                    class="inline-block max-h-5 align-middle mix-blend-multiply dark:mix-blend-screen"
                                />
                            </template>
                            <template v-else>
                                {{ org.name }}
                            </template>
                        </span>
                        <span class="ml-auto text-[11px] text-muted-foreground">⌘{{ index + 1 }}</span>
                    </Link>
                </DropdownMenuItem>
            </div>
            <DropdownMenuSeparator />
            <DropdownMenuItem class="gap-2 p-2" as-child>
                <Link href="/organizations/create" class="flex items-center gap-2 w-full">
                    <div class="flex size-6 items-center justify-center rounded-md border bg-background">
                        <Plus class="size-4" />
                    </div>
                    <div class="font-medium text-muted-foreground">Add organization</div>
                </Link>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

