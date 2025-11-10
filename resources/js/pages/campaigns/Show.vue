<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import CampaignController from '@/actions/App/Http/Controllers/Campaigns/CampaignController';
import CampaignImportController from '@/actions/App/Http/Controllers/Campaigns/CampaignImportController';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { BreadcrumbItem } from '@/types';
import type { Campaign, Certificate } from '@/types/models';

type CampaignPayload = Campaign & {
    variable_mapping?: {
        recipient_name?: string | null;
        recipient_email?: string | null;
        variables?: Record<string, string> | Array<Record<string, string>> | null;
    } | null;
    design?: {
        id: string;
        name: string;
    } | null;
    certificates?: Certificate[];
};

interface Props {
    campaign: CampaignPayload;
    can: {
        update: boolean;
        execute: boolean;
        delete: boolean;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Campaigns',
        href: CampaignController.index.url(),
    },
    {
        title: props.campaign.name,
        href: CampaignController.show.url({ campaign: props.campaign.id }),
    },
];

const statusVariant = (status: string) => {
    const variants: Record<string, string> = {
        draft: 'secondary',
        active: 'default',
        completed: 'outline',
        cancelled: 'destructive',
        pending: 'secondary',
        issued: 'default',
        expired: 'outline',
        revoked: 'destructive',
    };

    return variants[status] || 'secondary';
};

const completionSummary = computed(() => {
    if (!props.campaign.completed_at) {
        return 'Not completed';
    }

    const date = new Date(props.campaign.completed_at);
    return `${date.toLocaleDateString()} at ${date.toLocaleTimeString()}`;
});

const certificateProgress = computed(() => {
    if (!props.campaign.certificate_limit) {
        return `${props.campaign.certificates_issued} issued`;
    }

    return `${props.campaign.certificates_issued} of ${props.campaign.certificate_limit}`;
});

const isDraft = computed(() => props.campaign.status === 'draft');
const isActive = computed(() => props.campaign.status === 'active');
const canStart = computed(() => props.can.execute && isDraft.value);
const canFinish = computed(() => props.can.execute && isActive.value);

const executeCampaign = () => {
    if (!canStart.value) {
        return;
    }

    router.post(
        CampaignController.execute.url({ campaign: props.campaign.id }),
        {},
        {
            preserveScroll: true,
        }
    );
};

const finishCampaign = () => {
    if (!canFinish.value) {
        return;
    }

    if (
        !confirm(
            'Mark this campaign as completed? No additional certificates will be issued once finished.'
        )
    ) {
        return;
    }

    router.post(
        CampaignController.finish.url({ campaign: props.campaign.id }),
        {},
        {
            preserveScroll: true,
        }
    );
};

const variableRows = computed(() => {
    const mapping = props.campaign.variable_mapping ?? {};
    const variables = (mapping as Record<string, unknown>).variables;

    if (!variables) {
        return [] as Array<{ key: string; column: string }>;
    }

    if (Array.isArray(variables)) {
        return variables
            .map((row: Record<string, string>) => ({ key: row.key, column: row.column }))
            .filter((row) => row.key && row.column);
    }

    return Object.entries(variables as Record<string, string>).map(([key, column]) => ({ key, column }));
});

const recipientColumns = computed(() => {
    const mapping = props.campaign.variable_mapping ?? {};
    return {
        name: (mapping as Record<string, unknown>).recipient_name ?? '—',
        email: (mapping as Record<string, unknown>).recipient_email ?? '—',
    };
});

const showImport = computed(() => props.can.execute || props.campaign.status === 'draft');

const certificates = computed(() => props.campaign.certificates ?? []);

const statusLabel = (value: string) => value.charAt(0).toUpperCase() + value.slice(1);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="props.campaign.name" />

        <div class="flex w-full flex-col gap-6 px-4 pb-12 pt-6 lg:px-12 xl:px-16">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-semibold">{{ props.campaign.name }}</h1>
                        <Badge :variant="statusVariant(props.campaign.status)">
                            {{ statusLabel(props.campaign.status) }}
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ props.campaign.description || 'No description provided.' }}
                    </p>
                    <p v-if="props.campaign.design" class="text-xs text-muted-foreground">
                        Design: <span class="font-medium text-foreground">{{ props.campaign.design.name }}</span>
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link :href="CampaignController.edit.url({ campaign: props.campaign.id })">
                        <Button variant="outline" :disabled="!props.can.update">
                            Edit
                        </Button>
                    </Link>
                    <Link v-if="showImport" :href="CampaignImportController.create.url({ campaign: props.campaign.id })">
                        <Button variant="secondary">
                            Import recipients
                        </Button>
                    </Link>
                    <Button
                        type="button"
                        variant="default"
                        :disabled="!canStart"
                        @click="executeCampaign"
                    >
                        Start execution
                    </Button>
                    <Button
                        v-if="isActive"
                        type="button"
                        variant="destructive"
                        :disabled="!canFinish"
                        @click="finishCampaign"
                    >
                        Finish campaign
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Certificates issued</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold text-foreground">{{ certificateProgress }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Completion status</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold text-foreground">{{ completionSummary }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Created</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold text-foreground">
                            {{ new Date(props.campaign.created_at).toLocaleDateString() }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Recipient mapping</CardTitle>
                    <CardDescription>
                        Current column mappings used to parse imports.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-medium uppercase text-muted-foreground">Recipient name column</p>
                            <p class="text-sm text-foreground">{{ recipientColumns.name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase text-muted-foreground">Recipient email column</p>
                            <p class="text-sm text-foreground">{{ recipientColumns.email }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase text-muted-foreground">Design variables</p>
                        <div v-if="variableRows.length === 0" class="text-sm text-muted-foreground">
                            No additional variables mapped.
                        </div>
                        <div v-else class="mt-3 space-y-2">
                            <div
                                v-for="row in variableRows"
                                :key="row.key"
                                class="flex items-center justify-between rounded-md border border-border px-3 py-2"
                            >
                                <span class="text-sm font-medium text-foreground">{{ row.key }}</span>
                                <span class="text-sm text-muted-foreground">{{ row.column }}</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Recent certificates</CardTitle>
                    <CardDescription>
                        Showing the 50 most recent certificates issued from this campaign.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b text-left text-xs font-medium uppercase text-muted-foreground">
                                    <th class="px-4 py-2">Recipient</th>
                                    <th class="px-4 py-2">Email</th>
                                    <th class="px-4 py-2">Status</th>
                                    <th class="px-4 py-2">Issued</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="certificates.length === 0">
                                    <td colspan="4" class="px-4 py-6 text-center text-sm text-muted-foreground">
                                        No certificates issued yet.
                                    </td>
                                </tr>
                                <tr
                                    v-for="certificate in certificates"
                                    :key="certificate.id"
                                    class="border-b last:border-b-0"
                                >
                                    <td class="px-4 py-2 text-sm text-foreground">{{ certificate.recipient_name }}</td>
                                    <td class="px-4 py-2 text-sm text-muted-foreground">{{ certificate.recipient_email }}</td>
                                    <td class="px-4 py-2">
                                        <Badge :variant="statusVariant(certificate.status)">
                                            {{ statusLabel(certificate.status) }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-muted-foreground">
                                        <span v-if="certificate.issued_at">
                                            {{ new Date(certificate.issued_at).toLocaleString() }}
                                        </span>
                                        <span v-else>—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

