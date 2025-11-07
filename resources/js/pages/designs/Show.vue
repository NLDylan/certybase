<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { BreadcrumbItem } from '@/types';
import type { Design } from '@/types/models';

type UserSummary = {
    id: string;
    name: string;
};

type OrganizationSummary = {
    id: string;
    name: string;
};

interface DesignView extends Design {
    status_label?: string;
    preview_image_url?: string | null;
    campaigns_count?: number;
    certificates_count?: number;
    creator?: UserSummary | null;
    organization?: OrganizationSummary | null;
    created_at: string | null;
    updated_at: string | null;
}

interface Props {
    design: DesignView;
    can: {
        update: boolean;
        publish: boolean;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Designs',
        href: '/designs',
    },
    {
        title: props.design.name,
        href: `/designs/${props.design.id}`,
    },
];

const statusBadgeVariant = computed(() => {
    const variantMap: Record<string, string> = {
        draft: 'secondary',
        active: 'default',
        inactive: 'outline',
        archived: 'secondary',
    };

    return variantMap[props.design.status] ?? 'secondary';
});

const statusLabel = computed(() => props.design.status_label ?? props.design.status);

const formattedDate = (value: string | null | undefined): string | null => {
    if (!value) {
        return null;
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return null;
    }

    return `${date.toLocaleDateString()} ${date.toLocaleTimeString()}`;
};

const hasPreview = computed(() => Boolean(props.design.preview_image_url));

const editDetailsHref = computed(() => `/designs/${props.design.id}/details`);
const editorHref = computed(() => `/editor/${props.design.id}`);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`${props.design.name} – Design`" />

        <div class="flex w-full flex-col gap-6 px-4 pb-12 pt-6 lg:px-12 xl:px-16">
            <div class="flex flex-col gap-4 justify-between md:flex-row md:items-center">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-semibold">{{ props.design.name }}</h1>
                        <Badge :variant="statusBadgeVariant">
                            {{ statusLabel }}
                        </Badge>
                    </div>
                    <p v-if="props.design.description" class="max-w-3xl text-sm text-muted-foreground">
                        {{ props.design.description }}
                    </p>
                    <div v-if="props.design.organization" class="flex items-center gap-2 text-sm text-muted-foreground">
                        <span>Organization:</span>
                        <span class="font-medium text-foreground">{{ props.design.organization.name }}</span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Link :href="editorHref">
                        <Button variant="outline">
                            Open Editor
                        </Button>
                    </Link>
                    <Link v-if="props.can.update" :href="editDetailsHref">
                        <Button>
                            Edit Details
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Design Overview</CardTitle>
                        <CardDescription>Key information about this design</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <dl class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-medium uppercase text-muted-foreground">Status</dt>
                                <dd class="text-sm">{{ statusLabel }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase text-muted-foreground">Created</dt>
                                <dd class="text-sm">
                                    {{ formattedDate(props.design.created_at) ?? '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase text-muted-foreground">Last Updated</dt>
                                <dd class="text-sm">
                                    {{ formattedDate(props.design.updated_at) ?? '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase text-muted-foreground">Created By</dt>
                                <dd class="text-sm">
                                    {{ props.design.creator?.name ?? '—' }}
                                </dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Usage</CardTitle>
                        <CardDescription>How this design is used across the platform</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="rounded-md border p-4">
                                <p class="text-xs font-medium uppercase text-muted-foreground">Campaigns</p>
                                <p class="mt-1 text-2xl font-semibold">
                                    {{ props.design.campaigns_count ?? 0 }}
                                </p>
                            </div>
                            <div class="rounded-md border p-4">
                                <p class="text-xs font-medium uppercase text-muted-foreground">Certificates Issued</p>
                                <p class="mt-1 text-2xl font-semibold">
                                    {{ props.design.certificates_count ?? 0 }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Preview</CardTitle>
                        <CardDescription>Latest saved preview image for this design</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div
                            class="flex h-full min-h-[240px] items-center justify-center rounded-md border bg-muted/40 p-4">
                            <img v-if="hasPreview" :src="props.design.preview_image_url ?? undefined"
                                alt="Design preview" class="max-h-[320px] max-w-full rounded-md border shadow-sm" />
                            <div v-else class="text-sm text-muted-foreground">
                                No preview available yet. Open the editor to generate one.
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Metadata</CardTitle>
                        <CardDescription>Additional information about this design</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <dl class="space-y-4 text-sm">
                            <div>
                                <dt class="text-xs font-medium uppercase text-muted-foreground">Design ID</dt>
                                <dd class="font-mono text-xs text-foreground/80 break-all">{{ props.design.id }}</dd>
                            </div>
                            <div v-if="props.design.organization">
                                <dt class="text-xs font-medium uppercase text-muted-foreground">Organization</dt>
                                <dd>{{ props.design.organization.name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase text-muted-foreground">Status Value</dt>
                                <dd>{{ props.design.status }}</dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>


