<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import CampaignController from '@/actions/App/Http/Controllers/Campaigns/CampaignController';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ArrowUpDown, MoreVertical, Plus, Search } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';
import type { Campaign, PaginatedResponse } from '@/types/models';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

interface Props {
    campaigns: PaginatedResponse<Campaign>;
    filters: {
        status?: string;
        design_id?: string;
        search?: string;
        sort_by?: string;
        sort_order?: string;
    };
    designs: Array<{ id: string; name: string }>;
    statuses: string[];
    can: {
        create: boolean;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const ALL_OPTION = 'all';

const statusFilter = ref(props.filters.status || ALL_OPTION);
const designFilter = ref(props.filters.design_id || ALL_OPTION);
const sortBy = ref(props.filters.sort_by || 'created_at');
const sortOrder = ref(props.filters.sort_order || 'desc');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Campaigns',
        href: '',
    },
];

const statusBadgeVariant = (status: string) => {
    const variants: Record<string, string> = {
        draft: 'secondary',
        active: 'default',
        completed: 'default',
        cancelled: 'destructive',
    };
    return variants[status.toLowerCase()] || 'secondary';
};

const handleSearch = () => {
    router.get(
        CampaignController.index.url({
            query: {
                search: search.value || undefined,
                status: statusFilter.value === ALL_OPTION ? undefined : statusFilter.value,
                design_id: designFilter.value === ALL_OPTION ? undefined : designFilter.value,
                sort_by: sortBy.value,
                sort_order: sortOrder.value,
            },
        }),
        {},
        {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        }
    );
};

const handleSort = (column: string) => {
    if (sortBy.value === column) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortOrder.value = 'asc';
    }
    handleSearch();
};

const handleFilterChange = () => {
    handleSearch();
};

const getSortIcon = (column: string) => {
    if (sortBy.value !== column) return null;
    return sortOrder.value === 'asc' ? '↑' : '↓';
};

const statuses = computed(() => props.statuses || []);

const deleteCampaign = (campaign: Campaign) => {
    if (!(campaign.can?.delete ?? false)) {
        return;
    }

    if (!confirm('Are you sure you want to delete this campaign? This action cannot be undone.')) {
        return;
    }

    router.delete(
        CampaignController.destroy.url({ campaign: campaign.id }),
        {
            preserveScroll: true,
            preserveState: true,
        }
    );
};
</script>

<template>

    <Head title="Campaigns" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex w-full flex-col gap-6 px-4 pb-12 pt-6 lg:px-12 xl:px-16">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="space-y-2">
                    <h1 class="text-2xl font-semibold">Campaigns</h1>
                    <p class="text-sm text-muted-foreground">
                        Manage your certificate campaigns
                    </p>
                </div>
                    <Link :href="CampaignController.create.url()">
                        <Button :disabled="!props.can.create" :variant="props.can.create ? 'default' : 'outline'">
                        <Plus class="mr-2 h-4 w-4" />
                        Create Campaign
                    </Button>
                </Link>
            </div>

            <Card>
                <CardHeader class="space-y-4 border-b py-4">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div class="relative w-full md:max-w-sm">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="search"
                                placeholder="Search campaigns..."
                                class="pl-9"
                                @keyup.enter="handleSearch"
                            />
                        </div>
                        <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-end sm:gap-4">
                            <Select v-model="statusFilter" @update:model-value="handleFilterChange">
                                <SelectTrigger class="w-full sm:w-[180px]">
                                    <SelectValue placeholder="All Statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="ALL_OPTION">All Statuses</SelectItem>
                                    <SelectItem v-for="status in statuses" :key="status" :value="status">
                                        {{ status.charAt(0).toUpperCase() + status.slice(1) }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Select v-model="designFilter" @update:model-value="handleFilterChange">
                                <SelectTrigger class="w-full sm:w-[220px]">
                                    <SelectValue placeholder="All Designs" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="ALL_OPTION">All Designs</SelectItem>
                                    <SelectItem v-for="design in props.designs" :key="design.id" :value="design.id">
                                        {{ design.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Button class="w-full sm:w-auto" @click="handleSearch">Search</Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                        <button class="flex items-center gap-2 hover:text-foreground"
                                            @click="handleSort('name')">
                                            Name
                                            <ArrowUpDown class="h-4 w-4" />
                                            <span v-if="getSortIcon('name')">{{
                                                getSortIcon('name')
                                                }}</span>
                                        </button>
                                    </th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                        Design
                                    </th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                        <button class="flex items-center gap-2 hover:text-foreground"
                                            @click="handleSort('status')">
                                            Status
                                            <ArrowUpDown class="h-4 w-4" />
                                            <span v-if="getSortIcon('status')">{{
                                                getSortIcon('status')
                                                }}</span>
                                        </button>
                                    </th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                        Certificates
                                    </th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                        <button class="flex items-center gap-2 hover:text-foreground"
                                            @click="handleSort('created_at')">
                                            Created
                                            <ArrowUpDown class="h-4 w-4" />
                                            <span v-if="getSortIcon('created_at')">{{
                                                getSortIcon('created_at')
                                                }}</span>
                                        </button>
                                    </th>
                                    <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="campaigns.data.length === 0"
                                    class="border-b transition-colors hover:bg-muted/50">
                                    <td colspan="6" class="h-24 text-center text-muted-foreground">
                                        No campaigns found.
                                    </td>
                                </tr>
                                <tr v-for="campaign in campaigns.data" :key="campaign.id"
                                    class="border-b transition-colors hover:bg-muted/50">
                                    <td class="p-4 align-middle">
                                        <div>
                                            <Link :href="CampaignController.show.url({ campaign: campaign.id })"
                                                class="font-medium hover:underline">
                                            {{ campaign.name }}
                                            </Link>
                                            <p v-if="campaign.description" class="text-sm text-muted-foreground">
                                                {{ campaign.description }}
                                            </p>
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <span v-if="campaign.design">{{ campaign.design.name }}</span>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <Badge :variant="statusBadgeVariant(campaign.status)">
                                            {{ campaign.status }}
                                        </Badge>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <span v-if="campaign.certificate_limit">
                                            {{ campaign.certificates_issued }} / {{ campaign.certificate_limit }}
                                        </span>
                                        <span v-else>
                                            {{ campaign.certificates_issued || campaign.certificates_count || 0 }}
                                        </span>
                                    </td>
                                    <td class="p-4 align-middle text-sm text-muted-foreground">
                                        {{ new Date(campaign.created_at).toLocaleDateString() }}
                                    </td>
                                    <td class="p-4 align-middle text-right" @click.stop>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="icon">
                                                    <MoreVertical class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent class="w-40" align="end">
                                                <DropdownMenuItem as-child>
                                                    <Link :href="CampaignController.show.url({ campaign: campaign.id })">
                                                        View
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem v-if="campaign.can?.update" as-child>
                                                    <Link :href="CampaignController.edit.url({ campaign: campaign.id })">
                                                        Edit
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    v-if="campaign.can?.delete"
                                                    class="text-red-600 focus:text-red-600"
                                                    @click="deleteCampaign(campaign)"
                                                >
                                                    Delete
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="campaigns.last_page > 1" class="mt-4 flex items-center justify-between">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ (campaigns.current_page - 1) * campaigns.per_page + 1 }} to
                            {{ Math.min(campaigns.current_page * campaigns.per_page, campaigns.total) }} of
                            {{ campaigns.total }} results
                        </div>
                        <div class="flex gap-2">
                            <Button
                                v-if="campaigns.current_page > 1"
                                variant="outline"
                                size="sm"
                                @click="
                                    router.get(
                                        campaigns.links.find((link) => link.label === '&laquo; Previous')?.url || '',
                                        {},
                                        {
                                            preserveScroll: true,
                                            preserveState: true,
                                        }
                                    )
                                "
                            >
                                Previous
                            </Button>
                            <Button
                                v-if="campaigns.current_page < campaigns.last_page"
                                variant="outline"
                                size="sm"
                                @click="
                                    router.get(
                                        campaigns.links.find((link) => link.label === 'Next &raquo;')?.url || '',
                                        {},
                                        {
                                            preserveScroll: true,
                                            preserveState: true,
                                        }
                                    )
                                "
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
