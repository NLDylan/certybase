<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import CampaignController from '@/actions/App/Http/Controllers/Campaigns/CampaignController';
import CampaignImportController from '@/actions/App/Http/Controllers/Campaigns/CampaignImportController';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import InputError from '@/components/InputError.vue';
import type { BreadcrumbItem } from '@/types';
import type { Campaign } from '@/types/models';

interface Props {
    campaign: Pick<Campaign, 'id' | 'name' | 'status' | 'certificates_issued' | 'certificate_limit'>;
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
        title: 'Import recipients',
        href: CampaignImportController.create.url({ campaign: props.campaign.id }),
    },
];

const form = useForm<{ file: File | null }>({
    file: null,
});

const submit = () => {
    if (!form.file) {
        form.setError('file', 'Please choose a CSV file to import.');
        return;
    }

    form.post(CampaignImportController.store.url({ campaign: props.campaign.id }), {
        forceFormData: true,
        preserveScroll: true,
    });
};

const progressSummary = computed(() => {
    if (!props.campaign.certificate_limit) {
        return `${props.campaign.certificates_issued} certificates issued so far.`;
    }

    return `${props.campaign.certificates_issued} of ${props.campaign.certificate_limit} certificates issued.`;
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Import recipients – ${props.campaign.name}`" />

        <div class="flex w-full flex-col gap-6 px-4 pb-12 pt-6 lg:px-12 xl:px-16">
            <div class="flex flex-col gap-2">
                <h1 class="text-2xl font-semibold">Import recipients</h1>
                <p class="text-sm text-muted-foreground">
                    Upload a CSV file containing recipients for <span class="font-medium text-foreground">{{ props.campaign.name }}</span>.
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Import instructions</CardTitle>
                    <CardDescription>
                        Ensure your CSV file includes headers that match the variable mapping configured for this campaign.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4 text-sm text-muted-foreground">
                    <ul class="list-disc space-y-2 pl-5">
                        <li>Accepted formats: CSV or plain text encoded in UTF-8.</li>
                        <li>The first row must contain column headings.</li>
                        <li>Duplicate email addresses will create multiple certificates.</li>
                        <li>{{ progressSummary }}</li>
                    </ul>
                </CardContent>
            </Card>

            <form class="space-y-6" @submit.prevent="submit">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Upload CSV</CardTitle>
                        <CardDescription>
                            Select a file from your computer. The import will begin processing immediately after upload.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <input
                            type="file"
                            name="file"
                            accept=".csv,text/csv,text/plain"
                            :disabled="form.processing"
                            @change="(event) => {
                                const target = event.target as HTMLInputElement;
                                form.file = target.files?.[0] ?? null;
                                form.clearErrors('file');
                            }"
                            class="block w-full rounded-md border border-dashed border-muted-foreground/40 bg-muted/40 px-4 py-10 text-center text-sm text-muted-foreground transition hover:border-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed"
                        />
                        <InputError :message="form.errors.file" />

                        <div class="flex items-center gap-3">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Uploading…' : 'Queue import' }}
                            </Button>
                            <Link :href="CampaignController.show.url({ campaign: props.campaign.id })">
                                <Button type="button" variant="outline">
                                    Back to campaign
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </div>
    </AppLayout>
</template>

