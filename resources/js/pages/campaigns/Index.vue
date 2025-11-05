<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ArrowUpDown, Plus, Search } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';
import type { Campaign, PaginatedResponse } from '@/types/models';

interface Props {
    campaigns: PaginatedResponse<Campaign>;
    filters: {
        status?: string;
        design_id?: string;
        search?: string;
        sort_by?: string;
        sort_order?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');
const designFilter = ref(props.filters.design_id || '');
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
        '/campaigns',
        {
            search: search.value || undefined,
            status: statusFilter.value === 'all' ? undefined : statusFilter.value,
            design_id: designFilter.value || undefined,
            sort_by: sortBy.value,
            sort_order: sortOrder.value,
        },
        {
            preserveState: true,
            replace: true,
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
</script>

<template>

    <Head title="Campaigns" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Campaigns</CardTitle>
                            <CardDescription>
                                Manage your certificate campaigns
                            </CardDescription>
                        </div>
                        <Link href="/campaigns/create">
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            Create Campaign
                        </Button>
                        </Link>
                    </div>
                </CardHeader>
                <CardContent>
                    <!-- Filters -->
                    <div class="mb-6 flex gap-4">
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input v-model="search" placeholder="Search campaigns..." class="pl-9"
                                @keyup.enter="handleSearch" />
                        </div>
                        <Select v-model="statusFilter" @update:model-value="handleFilterChange">
                            <SelectTrigger class="w-[180px]">
                                <SelectValue placeholder="All Statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Statuses</SelectItem>
                                <SelectItem value="draft">Draft</SelectItem>
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="completed">Completed</SelectItem>
                                <SelectItem value="cancelled">Cancelled</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button @click="handleSearch">Search</Button>
                    </div>

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
                                            <Link :href="`/campaigns/${campaign.id}`"
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
                                    <td class="p-4 align-middle text-right">
                                        <div class="flex justify-end gap-2">
                                            <Link :href="`/campaigns/${campaign.id}`">
                                            <Button variant="ghost" size="sm">View</Button>
                                            </Link>
                                        </div>
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
                            <Button v-if="campaigns.current_page > 1" variant="outline" size="sm" @click="
                                router.get(
                                    campaigns.links.find((l) => l.label === '&laquo; Previous')?.url || ''
                                )
                                ">
                                Previous
                            </Button>
                            <Button v-if="campaigns.current_page < campaigns.last_page" variant="outline" size="sm"
                                @click="
                                    router.get(
                                        campaigns.links.find((l) => l.label === 'Next &raquo;')?.url || ''
                                    )
                                    ">
                                Next
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
