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
import type { Certificate, PaginatedResponse } from '@/types/models';

interface Props {
    certificates: PaginatedResponse<Certificate>;
    filters: {
        status?: string;
        campaign_id?: string;
        design_id?: string;
        search?: string;
        sort_by?: string;
        sort_order?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');
const campaignFilter = ref(props.filters.campaign_id || '');
const designFilter = ref(props.filters.design_id || '');
const sortBy = ref(props.filters.sort_by || 'issued_at');
const sortOrder = ref(props.filters.sort_order || 'desc');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Certificates',
        href: '',
    },
];

const statusBadgeVariant = (status: string) => {
    const variants: Record<string, string> = {
        pending: 'secondary',
        issued: 'default',
        expired: 'outline',
        revoked: 'destructive',
    };
    return variants[status.toLowerCase()] || 'secondary';
};

const handleSearch = () => {
    router.get(
        '/certificates',
        {
            search: search.value || undefined,
            status: statusFilter.value === 'all' ? undefined : statusFilter.value,
            campaign_id: campaignFilter.value || undefined,
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
    <Head title="Certificates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Certificates</CardTitle>
                            <CardDescription>
                                Manage your issued certificates
                            </CardDescription>
                        </div>
                        <Link href="/certificates/create">
                            <Button>
                                <Plus class="mr-2 h-4 w-4" />
                                Create Certificate
                            </Button>
                        </Link>
                    </div>
                </CardHeader>
                <CardContent>
                    <!-- Filters -->
                    <div class="mb-6 flex gap-4">
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="search"
                                placeholder="Search by recipient name or email..."
                                class="pl-9"
                                @keyup.enter="handleSearch"
                            />
                        </div>
                        <Select v-model="statusFilter" @update:model-value="handleFilterChange">
                            <SelectTrigger class="w-[180px]">
                                <SelectValue placeholder="All Statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Statuses</SelectItem>
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="issued">Issued</SelectItem>
                                <SelectItem value="expired">Expired</SelectItem>
                                <SelectItem value="revoked">Revoked</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button @click="handleSearch">Search</Button>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th
                                        class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                                    >
                                        <button
                                            class="flex items-center gap-2 hover:text-foreground"
                                            @click="handleSort('recipient_name')"
                                        >
                                            Recipient
                                            <ArrowUpDown class="h-4 w-4" />
                                            <span v-if="getSortIcon('recipient_name')">{{
                                                getSortIcon('recipient_name')
                                            }}</span>
                                        </button>
                                    </th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                        Email
                                    </th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                        Design
                                    </th>
                                    <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                        Campaign
                                    </th>
                                    <th
                                        class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                                    >
                                        <button
                                            class="flex items-center gap-2 hover:text-foreground"
                                            @click="handleSort('status')"
                                        >
                                            Status
                                            <ArrowUpDown class="h-4 w-4" />
                                            <span v-if="getSortIcon('status')">{{
                                                getSortIcon('status')
                                            }}</span>
                                        </button>
                                    </th>
                                    <th
                                        class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                                    >
                                        <button
                                            class="flex items-center gap-2 hover:text-foreground"
                                            @click="handleSort('issued_at')"
                                        >
                                            Issued
                                            <ArrowUpDown class="h-4 w-4" />
                                            <span v-if="getSortIcon('issued_at')">{{
                                                getSortIcon('issued_at')
                                            }}</span>
                                        </button>
                                    </th>
                                    <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-if="certificates.data.length === 0"
                                    class="border-b transition-colors hover:bg-muted/50"
                                >
                                    <td colspan="7" class="h-24 text-center text-muted-foreground">
                                        No certificates found.
                                    </td>
                                </tr>
                                <tr
                                    v-for="certificate in certificates.data"
                                    :key="certificate.id"
                                    class="border-b transition-colors hover:bg-muted/50"
                                >
                                    <td class="p-4 align-middle">
                                        <div class="font-medium">{{ certificate.recipient_name }}</div>
                                    </td>
                                    <td class="p-4 align-middle text-sm text-muted-foreground">
                                        {{ certificate.recipient_email }}
                                    </td>
                                    <td class="p-4 align-middle">
                                        <span v-if="certificate.design">{{ certificate.design.name }}</span>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <span v-if="certificate.campaign">{{ certificate.campaign.name }}</span>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <Badge :variant="statusBadgeVariant(certificate.status)">
                                            {{ certificate.status }}
                                        </Badge>
                                    </td>
                                    <td class="p-4 align-middle text-sm text-muted-foreground">
                                        <span v-if="certificate.issued_at">
                                            {{ new Date(certificate.issued_at).toLocaleDateString() }}
                                        </span>
                                        <span v-else>—</span>
                                    </td>
                                    <td class="p-4 align-middle text-right">
                                        <div class="flex justify-end gap-2">
                                            <Link
                                                :href="`/certificates/${certificate.id}`"
                                            >
                                                <Button variant="ghost" size="sm">View</Button>
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="certificates.last_page > 1" class="mt-4 flex items-center justify-between">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ (certificates.current_page - 1) * certificates.per_page + 1 }} to
                            {{ Math.min(certificates.current_page * certificates.per_page, certificates.total) }} of
                            {{ certificates.total }} results
                        </div>
                        <div class="flex gap-2">
                            <Button
                                v-if="certificates.current_page > 1"
                                variant="outline"
                                size="sm"
                                @click="
                                    router.get(
                                        certificates.links.find((l) => l.label === '&laquo; Previous')?.url || ''
                                    )
                                "
                            >
                                Previous
                            </Button>
                            <Button
                                v-if="certificates.current_page < certificates.last_page"
                                variant="outline"
                                size="sm"
                                @click="
                                    router.get(
                                        certificates.links.find((l) => l.label === 'Next &raquo;')?.url || ''
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

