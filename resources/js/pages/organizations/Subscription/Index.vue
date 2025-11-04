<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/organizations/SettingsLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface Props {
    organization: {
        id: string;
        name: string;
        has_active_subscription?: boolean;
    };
}

const props = defineProps<Props>();
</script>

<template>
    <AppLayout>
        <Head title="Organization Subscription" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Subscription</CardTitle>
                        <CardDescription>
                            Manage your organization's subscription and billing
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Status</p>
                                <Badge
                                    variant="secondary"
                                >
                                    {{ props.organization.has_active_subscription !== false ? 'Active' : 'No Subscription' }}
                                </Badge>
                            </div>
                            <p class="text-sm text-muted-foreground">
                                Subscription management is coming soon.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

