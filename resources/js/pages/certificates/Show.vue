<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import CertificateController from '@/actions/App/Http/Controllers/Certificates/CertificateController';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import type { BreadcrumbItem } from '@/types';
import type { Certificate } from '@/types/models';

type CertificatePayload = Certificate & {
    organization?: {
        id: string;
        name: string;
    } | null;
    design?: {
        id: string;
        name: string;
    } | null;
    campaign?: {
        id: string;
        name: string;
    } | null;
    issued_to_user?: {
        id: string;
        name: string;
    } | null;
};

interface Props {
    certificate: CertificatePayload;
    can: {
        download: boolean;
        revoke: boolean;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Certificates',
        href: CertificateController.index.url(),
    },
    {
        title: props.certificate.recipient_name,
        href: CertificateController.show.url({ certificate: props.certificate.id }),
    },
];

const statusVariant = (status: string) => {
    const variants: Record<string, string> = {
        pending: 'secondary',
        issued: 'default',
        expired: 'outline',
        revoked: 'destructive',
    };

    return variants[status.toLowerCase()] || 'secondary';
};

const statusLabel = (status: string) => status.charAt(0).toUpperCase() + status.slice(1);

const issuedAt = computed(() => {
    if (!props.certificate.issued_at) {
        return 'Not issued yet';
    }

    const date = new Date(props.certificate.issued_at);
    return `${date.toLocaleDateString()} at ${date.toLocaleTimeString()}`;
});

const expiresAt = computed(() => {
    if (!props.certificate.expires_at) {
        return 'No expiry set';
    }

    const date = new Date(props.certificate.expires_at);
    return `${date.toLocaleDateString()} at ${date.toLocaleTimeString()}`;
});

const revokedAt = computed(() => {
    if (!props.certificate.revoked_at) {
        return null;
    }

    const date = new Date(props.certificate.revoked_at);
    return `${date.toLocaleDateString()} at ${date.toLocaleTimeString()}`;
});

const recipientDataEntries = computed(() => {
    const data = props.certificate.recipient_data ?? {};
    return Object.entries(data as Record<string, unknown>);
});

const certificateData = computed(() => props.certificate.certificate_data ?? null);

const revokeForm = useForm({
    reason: '',
});

const canRevoke = computed(() => props.can.revoke && props.certificate.status !== 'revoked');

const revokeCertificate = () => {
    if (!canRevoke.value) {
        return;
    }

    revokeForm.post(CertificateController.revoke.url({ certificate: props.certificate.id }), {
        preserveScroll: true,
        onSuccess: () => revokeForm.reset('reason'),
    });
};

const downloadUrl = computed(() => CertificateController.download.url({ certificate: props.certificate.id }));

const hasRecipientData = computed(() => recipientDataEntries.value.length > 0);

const openDownload = () => {
    if (!props.can.download) {
        return;
    }

    window.open(downloadUrl.value, '_blank', 'noopener');
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Certificate • ${props.certificate.recipient_name}`" />

        <div class="flex w-full flex-col gap-6 px-4 pb-12 pt-6 lg:px-12 xl:px-16">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-semibold">
                            {{ props.certificate.recipient_name }}
                        </h1>
                        <Badge :variant="statusVariant(props.certificate.status)">
                            {{ statusLabel(props.certificate.status) }}
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ props.certificate.recipient_email }}
                    </p>
                    <p v-if="props.certificate.campaign" class="text-xs text-muted-foreground">
                        Campaign: <span class="font-medium text-foreground">{{ props.certificate.campaign.name }}</span>
                    </p>
                    <p v-if="props.certificate.design" class="text-xs text-muted-foreground">
                        Design: <span class="font-medium text-foreground">{{ props.certificate.design.name }}</span>
                    </p>
                    <p v-if="props.certificate.organization" class="text-xs text-muted-foreground">
                        Organization: <span class="font-medium text-foreground">{{ props.certificate.organization.name }}</span>
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button type="button" variant="outline" :disabled="!props.can.download" @click="openDownload">
                        Download PDF
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        :disabled="!canRevoke || revokeForm.processing"
                        @click="revokeCertificate"
                    >
                        {{ revokeForm.processing ? 'Revoking...' : 'Revoke certificate' }}
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Issued at</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold text-foreground">{{ issuedAt }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Expires</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold text-foreground">{{ expiresAt }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Created</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-semibold text-foreground">
                            {{ new Date(props.certificate.created_at).toLocaleDateString() }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <Card v-if="revokedAt">
                <CardHeader>
                    <CardTitle class="text-base">Revocation details</CardTitle>
                    <CardDescription>Recorded information about this revocation.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-2 text-sm">
                    <p><span class="font-medium">Revoked at:</span> {{ revokedAt }}</p>
                    <p><span class="font-medium">Reason:</span> {{ props.certificate.revocation_reason || 'No reason provided.' }}</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Recipient data</CardTitle>
                    <CardDescription>Values supplied for this certificate instance.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="!hasRecipientData" class="rounded-md border border-dashed p-4 text-sm text-muted-foreground">
                        No additional recipient data stored for this certificate.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-muted-foreground">
                                    <th class="px-3 py-2">Key</th>
                                    <th class="px-3 py-2">Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="[key, value] in recipientDataEntries" :key="key" class="border-b">
                                    <td class="px-3 py-2 font-medium text-foreground">{{ key }}</td>
                                    <td class="px-3 py-2">
                                        <span v-if="value === null" class="text-muted-foreground">—</span>
                                        <span v-else>{{ value }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Verification</CardTitle>
                    <CardDescription>Details used for manual verification flows.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4 text-sm">
                    <div>
                        <div class="text-xs uppercase text-muted-foreground">Verification token</div>
                        <code class="mt-1 inline-block rounded bg-muted px-2 py-1 text-xs">{{ props.certificate.verification_token }}</code>
                    </div>
                    <div>
                        <div class="text-xs uppercase text-muted-foreground">Issued to user</div>
                        <p class="mt-1">
                            <span v-if="props.certificate.issued_to_user">
                                {{ props.certificate.issued_to_user.name }}
                            </span>
                            <span v-else class="text-muted-foreground">No associated platform user.</span>
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Certificate payload</CardTitle>
                    <CardDescription>Raw data stored for rendering the certificate design.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="!certificateData" class="rounded-md border border-dashed p-4 text-sm text-muted-foreground">
                        Certificate payload has not been captured yet.
                    </div>
                    <pre v-else class="max-h-96 overflow-auto rounded-md bg-muted p-4 text-xs">
{{ JSON.stringify(certificateData, null, 2) }}
                    </pre>
                </CardContent>
            </Card>

            <form class="space-y-4 rounded-lg border p-4" @submit.prevent="revokeCertificate">
                <div class="flex flex-col gap-2">
                    <Label for="revoke-reason">Revocation reason</Label>
                    <textarea
                        id="revoke-reason"
                        v-model="revokeForm.reason"
                        rows="3"
                        :disabled="!canRevoke || revokeForm.processing"
                        class="flex min-h-[90px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="Optional reason for revoking the certificate"
                    />
                    <InputError :message="revokeForm.errors.reason" />
                </div>
                <div class="flex justify-end">
                    <Button type="submit" variant="destructive" :disabled="!canRevoke || revokeForm.processing">
                        {{ revokeForm.processing ? 'Revoking...' : 'Confirm revocation' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>


