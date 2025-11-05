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

type OrgOption = { id: string; name: string };

const page = usePage();
const { isMobile } = useSidebar();

const currentOrg = computed<OrgOption | null>(() => {
    const org = page.props.organization as { id: string; name: string } | null;
    return org ? { id: org.id, name: org.name } : null;
});

const organizations = computed<OrgOption[]>(() => {
    const orgs = (page.props.organizations as Array<{ id: string; name: string }>) || [];
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
                        <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                            <Building2 class="size-4" />
                        </div>
                        <div class="grid flex-1 text-left text-sm leading-tight min-w-0">
                            <span class="truncate font-semibold" :title="currentOrg?.name || 'Select organization'">
                                {{ currentOrg?.name || 'Select organization' }}
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
                        <div class="flex size-6 items-center justify-center rounded-sm border">
                            <Building2 class="size-4 shrink-0" />
                        </div>
                        <span class="truncate">{{ org.name }}</span>
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

