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
import { ArrowUpDown, Plus, Search, MoreVertical } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { BreadcrumbItem } from '@/types';
import type { Design, PaginatedResponse } from '@/types/models';

interface Props {
    designs: PaginatedResponse<Design>;
    filters: {
        status?: string;
        search?: string;
        sort_by?: string;
        sort_order?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');
const sortBy = ref(props.filters.sort_by || 'created_at');
const sortOrder = ref(props.filters.sort_order || 'desc');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Designs',
        href: '',
    },
];

const statusBadgeVariant = (status: string) => {
    const variants: Record<string, string> = {
        draft: 'secondary',
        active: 'default',
        inactive: 'outline',
        archived: 'secondary',
    };
    return variants[status.toLowerCase()] || 'secondary';
};

const handleSearch = () => {
    router.get(
        '/designs',
        {
            search: search.value || undefined,
            status: statusFilter.value === 'all' ? undefined : statusFilter.value,
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

const goToEditor = (id: string) => {
    router.visit(`/editor/${id}`);
};

const deleteDesign = (id: string) => {
    if (!confirm('Are you sure you want to delete this design?')) return;
    router.delete(`/designs/${id}`, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>

    <Head title="Designs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Designs</CardTitle>
                            <CardDescription>
                                Manage your certificate designs
                            </CardDescription>
                        </div>
                        <Link href="/designs/create">
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            Create Design
                        </Button>
                        </Link>
                    </div>
                </CardHeader>
                <CardContent>
                    <!-- Filters -->
                    <div class="mb-6 flex gap-4">
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input v-model="search" placeholder="Search designs..." class="pl-9"
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
                                <SelectItem value="inactive">Inactive</SelectItem>
                                <SelectItem value="archived">Archived</SelectItem>
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
                                        Creator
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
                                <tr v-if="designs.data.length === 0"
                                    class="border-b transition-colors hover:bg-muted/50">
                                    <td colspan="5" class="h-24 text-center text-muted-foreground">
                                        No designs found.
                                    </td>
                                </tr>
                                <tr v-for="design in designs.data" :key="design.id"
                                    class="border-b transition-colors hover:bg-muted/50 cursor-pointer"
                                    @click="goToEditor(design.id)">
                                    <td class="p-4 align-middle">
                                        <div>
                                            <Link :href="`/designs/${design.id}`" class="font-medium hover:underline"
                                                @click.stop>
                                            {{ design.name }}
                                            </Link>
                                            <p v-if="design.description" class="text-sm text-muted-foreground">
                                                {{ design.description }}
                                            </p>
                                        </div>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <Badge :variant="statusBadgeVariant(design.status)">
                                            {{ design.status }}
                                        </Badge>
                                    </td>
                                    <td class="p-4 align-middle">
                                        <span v-if="design.creator">{{ design.creator.name }}</span>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </td>
                                    <td class="p-4 align-middle text-sm text-muted-foreground">
                                        {{ new Date(design.created_at).toLocaleDateString() }}
                                    </td>
                                    <td class="p-4 align-middle text-right" @click.stop>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="icon">
                                                    <MoreVertical class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent class="w-40">
                                                <DropdownMenuItem as-child>
                                                    <Link :href="`/designs/${design.id}`">View</Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem @click="goToEditor(design.id)">Edit</DropdownMenuItem>
                                                <DropdownMenuItem class="text-red-600" @click="deleteDesign(design.id)">
                                                    Delete</DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="designs.last_page > 1" class="mt-4 flex items-center justify-between">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ (designs.current_page - 1) * designs.per_page + 1 }} to
                            {{ Math.min(designs.current_page * designs.per_page, designs.total) }} of
                            {{ designs.total }} results
                        </div>
                        <div class="flex gap-2">
                            <Button v-if="designs.current_page > 1" variant="outline" size="sm" @click="
                                router.get(
                                    designs.links.find((l) => l.label === '&laquo; Previous')?.url || ''
                                )
                                ">
                                Previous
                            </Button>
                            <Button v-if="designs.current_page < designs.last_page" variant="outline" size="sm" @click="
                                router.get(
                                    designs.links.find((l) => l.label === 'Next &raquo;')?.url || ''
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
