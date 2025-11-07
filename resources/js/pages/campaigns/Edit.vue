<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import CampaignController from '@/actions/App/Http/Controllers/Campaigns/CampaignController';
import CampaignImportController from '@/actions/App/Http/Controllers/Campaigns/CampaignImportController';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import InputError from '@/components/InputError.vue';
import type { BreadcrumbItem } from '@/types';
import type { Campaign } from '@/types/models';

type DesignOption = {
    id: string;
    name: string;
    variables: string[];
};

type CampaignWithMapping = Campaign & {
    variable_mapping?: {
        recipient_name?: string | null;
        recipient_email?: string | null;
        variables?: Record<string, string> | Array<Record<string, string>> | null;
    } | null;
    design?: {
        id: string;
        name: string;
    } | null;
};

interface Props {
    campaign: CampaignWithMapping;
    designs: DesignOption[];
    statuses: string[];
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
    {
        title: 'Edit',
        href: CampaignController.edit.url({ campaign: props.campaign.id }),
    },
];

const existingMapping = props.campaign.variable_mapping ?? {};

const form = useForm({
    name: props.campaign.name,
    description: props.campaign.description ?? '',
    design_id: props.campaign.design_id,
    status: props.campaign.status,
    start_date: props.campaign.start_date ?? '',
    end_date: props.campaign.end_date ?? '',
    certificate_limit: props.campaign.certificate_limit ?? '',
    variable_mapping: {
        recipient_name: (existingMapping as Record<string, unknown>).recipient_name ?? '',
        recipient_email: (existingMapping as Record<string, unknown>).recipient_email ?? '',
    },
});

type VariableRow = {
    key: string;
    column: string;
};

const initialVariables = (() => {
    const variables = (existingMapping as Record<string, unknown>).variables;

    if (!variables) {
        return [] as VariableRow[];
    }

    if (Array.isArray(variables)) {
        return variables
            .map((row: Record<string, string>) => ({ key: row.key, column: row.column }))
            .filter((row) => row.key && row.column);
    }

    return Object.entries(variables as Record<string, string>).map(([key, column]) => ({ key, column }));
})();

const variableRows = ref<VariableRow[]>(initialVariables);

const selectedDesignVariables = computed(() => {
    const design = props.designs.find((option) => option.id === form.design_id);
    return design?.variables ?? [];
});

const addVariableRow = () => {
    variableRows.value.push({ key: '', column: '' });
};

const removeVariableRow = (index: number) => {
    variableRows.value.splice(index, 1);
};

const transformPayload = () => {
    const mappedVariables = variableRows.value
        .filter((row) => row.key && row.column)
        .reduce<Record<string, string>>((accumulator, row) => {
            accumulator[row.key] = row.column;
            return accumulator;
        }, {});

    const certificateLimit = form.certificate_limit === ''
        ? null
        : Number(form.certificate_limit);

    return {
        ...form.data(),
        certificate_limit: certificateLimit,
        variable_mapping: {
            recipient_name: form.variable_mapping.recipient_name || null,
            recipient_email: form.variable_mapping.recipient_email || null,
            variables: mappedVariables,
        },
    };
};

const submit = () => {
    form.transform(() => transformPayload()).put(
        CampaignController.update.url({ campaign: props.campaign.id }),
        {
            preserveScroll: true,
        }
    );
};

const canUpdate = computed(() => props.can.update && !form.processing);

const executeCampaign = () => {
    if (!props.can.execute) {
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

const importRecipientsUrl = CampaignImportController.create.url({ campaign: props.campaign.id });

const statusLabel = (value: string) => value.charAt(0).toUpperCase() + value.slice(1);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${props.campaign.name}`" />

        <div class="flex w-full flex-col gap-6 px-4 pb-12 pt-6 lg:px-12 xl:px-16">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="space-y-2">
                    <h1 class="text-2xl font-semibold">Edit campaign</h1>
                    <p class="text-sm text-muted-foreground">
                        Update settings and mapping for <span class="font-medium text-foreground">{{ props.campaign.name }}</span>.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="CampaignController.show.url({ campaign: props.campaign.id })">
                        <Button variant="outline">Back to overview</Button>
                    </Link>
                    <Link :href="importRecipientsUrl">
                        <Button variant="secondary">Import recipients</Button>
                    </Link>
                    <Button
                        type="button"
                        variant="default"
                        :disabled="!props.can.execute"
                        @click="executeCampaign"
                    >
                        Start execution
                    </Button>
                </div>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Campaign settings</CardTitle>
                        <CardDescription>
                            Manage general information and scheduling.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-6 md:grid-cols-2">
                        <div class="grid gap-2 md:col-span-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                name="name"
                                required
                                maxlength="255"
                                :disabled="!props.can.update || form.processing"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="description">Description</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                name="description"
                                rows="4"
                                :disabled="!props.can.update || form.processing"
                                class="flex min-h-[120px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="Internal notes to help your team understand this campaign"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="design_id">Design</Label>
                            <select
                                id="design_id"
                                v-model="form.design_id"
                                name="design_id"
                                :disabled="!props.can.update || form.processing"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option
                                    v-for="design in props.designs"
                                    :key="design.id"
                                    :value="design.id"
                                >
                                    {{ design.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.design_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="status">Status</Label>
                            <select
                                id="status"
                                v-model="form.status"
                                name="status"
                                :disabled="!props.can.update || form.processing"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option
                                    v-for="statusOption in props.statuses"
                                    :key="statusOption"
                                    :value="statusOption"
                                >
                                    {{ statusLabel(statusOption) }}
                                </option>
                            </select>
                            <InputError :message="form.errors.status" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="certificate_limit">Certificate limit</Label>
                            <Input
                                id="certificate_limit"
                                v-model="form.certificate_limit"
                                name="certificate_limit"
                                type="number"
                                min="1"
                                step="1"
                                :disabled="!props.can.update || form.processing"
                                placeholder="Unlimited"
                            />
                            <div class="text-xs text-muted-foreground">Leave blank to continue issuing indefinitely.</div>
                            <InputError :message="form.errors.certificate_limit" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="start_date">Start date</Label>
                            <Input
                                id="start_date"
                                v-model="form.start_date"
                                name="start_date"
                                type="date"
                                :disabled="!props.can.update || form.processing"
                            />
                            <InputError :message="form.errors.start_date" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="end_date">End date</Label>
                            <Input
                                id="end_date"
                                v-model="form.end_date"
                                name="end_date"
                                type="date"
                                :disabled="!props.can.update || form.processing"
                                :min="form.start_date || undefined"
                            />
                            <InputError :message="form.errors.end_date" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Variable mapping</CardTitle>
                        <CardDescription>
                            Adjust how CSV columns map to design variables when importing recipients.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="recipient_name_column">Recipient name column</Label>
                                <Input
                                    id="recipient_name_column"
                                    v-model="form.variable_mapping.recipient_name"
                                    name="variable_mapping[recipient_name]"
                                    :disabled="!props.can.update || form.processing"
                                    placeholder="full_name"
                                />
                                <InputError :message="form.errors['variable_mapping.recipient_name']" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="recipient_email_column">Recipient email column</Label>
                                <Input
                                    id="recipient_email_column"
                                    v-model="form.variable_mapping.recipient_email"
                                    name="variable_mapping[recipient_email]"
                                    :disabled="!props.can.update || form.processing"
                                    placeholder="email"
                                />
                                <InputError :message="form.errors['variable_mapping.recipient_email']" />
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium">Design variable mapping</h3>
                                    <p class="text-xs text-muted-foreground">
                                        Align design placeholders with your CSV columns.
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    :disabled="!props.can.update || form.processing"
                                    @click="addVariableRow"
                                >
                                    Add mapping
                                </Button>
                            </div>

                            <div v-if="variableRows.length === 0" class="text-sm text-muted-foreground">
                                No additional mappings configured.
                            </div>

                            <div
                                v-for="(row, index) in variableRows"
                                :key="index"
                                class="grid gap-3 md:grid-cols-[1fr_1fr_auto] md:items-end"
                            >
                                <div class="grid gap-2">
                                    <Label :for="`variable-key-${index}`">Design variable</Label>
                                    <select
                                        :id="`variable-key-${index}`"
                                        v-model="row.key"
                                        :disabled="!props.can.update || form.processing"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="" disabled>Select variable</option>
                                        <option
                                            v-for="variable in selectedDesignVariables"
                                            :key="variable"
                                            :value="variable"
                                        >
                                            {{ variable }}
                                        </option>
                                    </select>
                                </div>
                                <div class="grid gap-2">
                                    <Label :for="`variable-column-${index}`">CSV column</Label>
                                    <Input
                                        :id="`variable-column-${index}`"
                                        v-model="row.column"
                                        :disabled="!props.can.update || form.processing"
                                        placeholder="course_title"
                                    />
                                </div>
                                <div class="flex justify-end md:justify-start">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        class="text-destructive"
                                        :disabled="!props.can.update || form.processing"
                                        @click="removeVariableRow(index)"
                                    >
                                        Remove
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <InputError :message="form.errors['variable_mapping.variables']" />
                    </CardContent>
                </Card>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="!canUpdate">
                        {{ form.processing ? 'Saving...' : 'Save changes' }}
                    </Button>
                    <InputError :message="form.errors.message" />
                </div>
            </form>
        </div>
    </AppLayout>
</template>

