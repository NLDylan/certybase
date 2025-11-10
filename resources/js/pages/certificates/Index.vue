<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import CertificateController from '@/actions/App/Http/Controllers/Certificates/CertificateController';
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
import type { Certificate, PaginatedResponse } from '@/types/models';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

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
    campaigns: Array<{ id: string; name: string }>;
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
const campaignFilter = ref(props.filters.campaign_id || ALL_OPTION);
const designFilter = ref(props.filters.design_id || ALL_OPTION);
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
        CertificateController.index.url({
            query: {
                search: search.value || undefined,
                status: statusFilter.value === ALL_OPTION ? undefined : statusFilter.value,
                campaign_id: campaignFilter.value === ALL_OPTION ? undefined : campaignFilter.value,
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

const revokeCertificate = (certificate: Certificate) => {
    const canRevoke = (certificate.can?.revoke ?? false) && certificate.status !== 'revoked';

    if (!canRevoke) {
        return;
    }

    if (
        !confirm(
            'Are you sure you want to revoke this certificate? The recipient will no longer be able to access it.'
        )
    ) {
        return;
    }

    router.post(
        CertificateController.revoke.url({ certificate: certificate.id }),
        { reason: null },
        {
            preserveScroll: true,
            preserveState: true,
        }
    );
};
</script>

<template>
    <Head title="Certificates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex w-full flex-col gap-6 px-4 pb-12 pt-6 lg:px-12 xl:px-16">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="space-y-2">
                    <h1 class="text-2xl font-semibold">Certificates</h1>
                    <p class="text-sm text-muted-foreground">
                        Manage your issued certificates
                    </p>
                </div>
                <Link :href="CertificateController.create.url()">
                    <Button :disabled="!props.can.create" :variant="props.can.create ? 'default' : 'outline'">
                        <Plus class="mr-2 h-4 w-4" />
                        Create Certificate
                    </Button>
                </Link>
            </div>

            <Card>
                <CardHeader class="space-y-4 border-b py-4">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div class="relative w-full md:max-w-md">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="search"
                                placeholder="Search by recipient name or email..."
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
                            <Select v-model="campaignFilter" @update:model-value="handleFilterChange">
                                <SelectTrigger class="w-full sm:w-[220px]">
                                    <SelectValue placeholder="All Campaigns" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="ALL_OPTION">All Campaigns</SelectItem>
                                    <SelectItem v-for="campaign in props.campaigns" :key="campaign.id" :value="campaign.id">
                                        {{ campaign.name }}
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
                                            {{ certificate.status.charAt(0).toUpperCase() + certificate.status.slice(1) }}
                                        </Badge>
                                    </td>
                                    <td class="p-4 align-middle text-sm text-muted-foreground">
                                        <span v-if="certificate.issued_at">
                                            {{ new Date(certificate.issued_at).toLocaleDateString() }}
                                        </span>
                                        <span v-else>—</span>
                                    </td>
                                    <td class="p-4 align-middle text-right" @click.stop>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="icon">
                                                    <MoreVertical class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent class="w-44" align="end">
                                                <DropdownMenuItem as-child>
                                                    <Link :href="CertificateController.show.url({ certificate: certificate.id })">
                                                        View
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    v-if="(certificate.can?.revoke ?? false) && certificate.status !== 'revoked'"
                                                    class="text-red-600 focus:text-red-600"
                                                    @click="revokeCertificate(certificate)"
                                                >
                                                    Revoke
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="certificates.last_page > 1"
                        class="flex flex-col gap-3 text-sm text-muted-foreground md:flex-row md:items-center md:justify-between"
                    >
                        <div>
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

