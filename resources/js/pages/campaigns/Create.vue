<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import CampaignController from '@/actions/App/Http/Controllers/Campaigns/CampaignController';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import InputError from '@/components/InputError.vue';
import type { BreadcrumbItem } from '@/types';

type DesignOption = {
    id: string;
    name: string;
    variables: string[];
};

interface Props {
    organizationId: string;
    designs: DesignOption[];
    defaultVariableMapping: {
        recipient_name: string;
        recipient_email: string;
        variables: Array<never>;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Campaigns',
        href: CampaignController.index.url(),
    },
    {
        title: 'Create',
        href: CampaignController.create.url(),
    },
];

const initialDesignId = props.designs[0]?.id ?? '';

const form = useForm({
    name: '',
    description: '',
    design_id: initialDesignId,
    start_date: '',
    end_date: '',
    certificate_limit: '' as string | number | '',
    variable_mapping: {
        recipient_name: props.defaultVariableMapping.recipient_name ?? '',
        recipient_email: props.defaultVariableMapping.recipient_email ?? '',
    },
});

type VariableRow = {
    key: string;
    column: string;
};

const variableRows = ref<VariableRow[]>([]);

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

const submit = () => {
    form.transform((data) => {
        const mappedVariables = variableRows.value
            .filter((row) => row.key && row.column)
            .reduce<Record<string, string>>((accumulator, row) => {
                accumulator[row.key] = row.column;
                return accumulator;
            }, {});

        const certificateLimit = data.certificate_limit === ''
            ? null
            : Number(data.certificate_limit);

        return {
            ...data,
            certificate_limit: certificateLimit,
            variable_mapping: {
                recipient_name: data.variable_mapping.recipient_name || null,
                recipient_email: data.variable_mapping.recipient_email || null,
                variables: mappedVariables,
            },
        };
    }).post(CampaignController.store.url(), {
        onSuccess: () => {
            variableRows.value = [];
        },
        preserveScroll: true,
    });
};

const canSubmit = computed(() => !form.processing);

const descriptionHelp = 'Optional. Helpful context about this campaign for your team.';
const mappingHelp = 'Map CSV column names to the design variables you plan to populate.';
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Campaign" />

        <div class="flex w-full flex-col gap-6 px-4 pb-12 pt-6 lg:px-12 xl:px-16">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="space-y-2">
                    <h1 class="text-2xl font-semibold">Create campaign</h1>
                    <p class="text-sm text-muted-foreground">
                        Launch a new certificate campaign using your organization’s designs.
                    </p>
                </div>
                <Link :href="CampaignController.index.url()">
                    <Button variant="outline">
                        Cancel
                    </Button>
                </Link>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Campaign details</CardTitle>
                        <CardDescription>
                            Configure the basics for your campaign.
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
                                :disabled="form.processing"
                                placeholder="Leadership Summit 2025"
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
                                :disabled="form.processing"
                                class="flex min-h-[120px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                :placeholder="descriptionHelp"
                            />
                            <div class="text-xs text-muted-foreground">{{ descriptionHelp }}</div>
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="design_id">Design</Label>
                            <select
                                id="design_id"
                                v-model="form.design_id"
                                name="design_id"
                                required
                                :disabled="form.processing || props.designs.length === 0"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option v-if="props.designs.length === 0" value="" disabled>
                                    No active designs available
                                </option>
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
                            <Label for="certificate_limit">Certificate limit</Label>
                            <Input
                                id="certificate_limit"
                                v-model="form.certificate_limit"
                                name="certificate_limit"
                                type="number"
                                min="1"
                                step="1"
                                :disabled="form.processing"
                                placeholder="Unlimited"
                            />
                            <div class="text-xs text-muted-foreground">Leave blank for unlimited certificates.</div>
                            <InputError :message="form.errors.certificate_limit" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="start_date">Start date</Label>
                            <Input
                                id="start_date"
                                v-model="form.start_date"
                                name="start_date"
                                type="date"
                                :disabled="form.processing"
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
                                :disabled="form.processing"
                                :min="form.start_date || undefined"
                            />
                            <InputError :message="form.errors.end_date" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Recipient & variable mapping</CardTitle>
                        <CardDescription>
                            {{ mappingHelp }}
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
                                    :disabled="form.processing"
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
                                    :disabled="form.processing"
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
                                        Match each design variable with a column heading from your import file.
                                    </p>
                                </div>
                                <Button type="button" variant="outline" size="sm" @click="addVariableRow">
                                    Add mapping
                                </Button>
                            </div>

                            <div v-if="variableRows.length === 0" class="text-sm text-muted-foreground">
                                No additional variables yet. Use “Add mapping” to start.
                            </div>

                            <div v-for="(row, index) in variableRows" :key="index" class="grid gap-3 md:grid-cols-[1fr_1fr_auto] md:items-end">
                                <div class="grid gap-2">
                                    <Label :for="`variable-key-${index}`">Design variable</Label>
                                    <select
                                        :id="`variable-key-${index}`"
                                        v-model="row.key"
                                        :disabled="form.processing"
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
                                        :disabled="form.processing"
                                        placeholder="course_title"
                                    />
                                </div>
                                <div class="flex justify-end md:justify-start">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        class="text-destructive"
                                        @click="removeVariableRow(index)"
                                        :disabled="form.processing"
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
                    <Button type="submit" :disabled="!canSubmit">
                        {{ form.processing ? 'Creating...' : 'Create campaign' }}
                    </Button>
                    <InputError :message="form.errors.message" />
                </div>
            </form>
        </div>
    </AppLayout>
</template>

