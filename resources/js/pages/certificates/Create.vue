<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import CertificateController from '@/actions/App/Http/Controllers/Certificates/CertificateController';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import InputError from '@/components/InputError.vue';
import type { BreadcrumbItem } from '@/types';

type CampaignOption = {
    id: string;
    name: string;
    design?: {
        id: string;
        name: string;
    } | null;
};

interface Props {
    organizationId: string;
    campaigns: CampaignOption[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Certificates',
        href: CertificateController.index.url(),
    },
    {
        title: 'Create',
        href: CertificateController.create.url(),
    },
];

const hasCampaigns = computed(() => props.campaigns.length > 0);
const initialCampaignId = hasCampaigns.value ? props.campaigns[0]?.id ?? '' : '';

const form = useForm({
    campaign_id: initialCampaignId,
    recipient_name: '',
    recipient_email: '',
    recipient_data: {} as Record<string, string>,
});

type AdditionalField = {
    key: string;
    value: string;
};

const additionalFields = ref<AdditionalField[]>([]);

const addField = () => {
    additionalFields.value.push({ key: '', value: '' });
};

const removeField = (index: number) => {
    additionalFields.value.splice(index, 1);
};

const submit = () => {
    form
        .transform((data) => {
            const mapped = additionalFields.value
                .filter((row) => row.key.trim() !== '')
                .reduce<Record<string, string>>((accumulator, row) => {
                    accumulator[row.key.trim()] = row.value;
                    return accumulator;
                }, {});

            return {
                ...data,
                recipient_data: Object.keys(mapped).length > 0 ? mapped : null,
            };
        })
        .post(CertificateController.store.url(), {
            preserveScroll: true,
        });
};

const designForCampaign = computed(() => {
    const campaign = props.campaigns.find((item) => item.id === form.campaign_id);
    return campaign?.design ?? null;
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Certificate" />

        <div class="flex w-full flex-col gap-6 px-4 pb-12 pt-6 lg:px-12 xl:px-16">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="space-y-2">
                    <h1 class="text-2xl font-semibold">Create certificate</h1>
                    <p class="text-sm text-muted-foreground">
                        Issue an individual certificate for a recipient.
                    </p>
                </div>
                <Link :href="CertificateController.index.url()">
                    <Button variant="outline">
                        Cancel
                    </Button>
                </Link>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Certificate details</CardTitle>
                    <CardDescription>
                        Select a campaign and enter recipient information.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-6" @submit.prevent="submit">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2 md:col-span-2">
                                <Label for="campaign_id">Campaign</Label>
                                <select
                                    id="campaign_id"
                                    v-model="form.campaign_id"
                                    name="campaign_id"
                                    required
                                    :disabled="form.processing || !hasCampaigns"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option v-if="!hasCampaigns" value="" disabled>
                                        No active campaigns available
                                    </option>
                                    <option v-for="campaign in props.campaigns" :key="campaign.id" :value="campaign.id">
                                        {{ campaign.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.campaign_id" />
                                <p v-if="designForCampaign" class="text-xs text-muted-foreground">
                                    Design: <span class="font-medium text-foreground">{{ designForCampaign.name }}</span>
                                </p>
                            </div>

                            <div class="grid gap-2">
                                <Label for="recipient_name">Recipient name</Label>
                                <Input
                                    id="recipient_name"
                                    v-model="form.recipient_name"
                                    name="recipient_name"
                                    required
                                    maxlength="255"
                                    :disabled="form.processing"
                                    placeholder="Jordan Smith"
                                />
                                <InputError :message="form.errors.recipient_name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="recipient_email">Recipient email</Label>
                                <Input
                                    id="recipient_email"
                                    v-model="form.recipient_email"
                                    name="recipient_email"
                                    type="email"
                                    required
                                    maxlength="255"
                                    :disabled="form.processing"
                                    placeholder="jordan@example.com"
                                />
                                <InputError :message="form.errors.recipient_email" />
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-sm font-medium">Additional data</h2>
                                    <p class="text-xs text-muted-foreground">
                                        Optional key/value pairs used to populate variables in the certificate design.
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    :disabled="form.processing"
                                    @click="addField"
                                >
                                    Add field
                                </Button>
                            </div>

                            <div v-if="additionalFields.length === 0" class="rounded-md border border-dashed p-4 text-sm text-muted-foreground">
                                No additional data. Add fields to include personalized variables.
                            </div>

                            <div class="grid gap-4" v-else>
                                <div v-for="(field, index) in additionalFields" :key="index" class="grid gap-3 rounded-lg border p-4 md:grid-cols-[1fr_1fr_auto]">
                                    <div class="grid gap-2">
                                        <Label :for="`field-key-${index}`">Variable key</Label>
                                        <Input
                                            :id="`field-key-${index}`"
                                            v-model="field.key"
                                            type="text"
                                            maxlength="255"
                                            :disabled="form.processing"
                                            placeholder="course_title"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label :for="`field-value-${index}`">Value</Label>
                                        <Input
                                            :id="`field-value-${index}`"
                                            v-model="field.value"
                                            type="text"
                                            maxlength="255"
                                            :disabled="form.processing"
                                            placeholder="Leadership Essentials"
                                        />
                                    </div>
                                    <div class="flex items-end justify-end">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            class="text-destructive"
                                            :disabled="form.processing"
                                            @click="removeField(index)"
                                        >
                                            Remove
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <Button type="submit" :disabled="form.processing || !hasCampaigns">
                                {{ form.processing ? 'Creating...' : 'Create certificate' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>


