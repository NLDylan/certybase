<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/organizations/SettingsLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ref, computed } from 'vue';

interface Props {
    organization: {
        id: string;
        name: string;
        has_active_subscription?: boolean;
    };
}

const props = defineProps<Props>();

const priceId = ref<string>('');
const hasActive = computed<boolean>(() => props.organization.has_active_subscription !== false);
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
                                    {{ hasActive ? 'Active' : 'No Subscription' }}
                                </Badge>
                            </div>
                            <div class="flex flex-col gap-4">
                                <div class="grid gap-2 max-w-md">
                                    <Label for="priceId">Stripe Price ID</Label>
                                    <Input id="priceId" v-model="priceId" placeholder="price_..." />
                                    <Link
                                        as="button"
                                        method="post"
                                        :href="`/organization/subscription/checkout/${priceId}`"
                                        :disabled="!priceId"
                                    >
                                        <Button :disabled="!priceId">{{ hasActive ? 'Change Plan' : 'Subscribe' }}</Button>
                                    </Link>
                                </div>

                                <div>
                                    <Link :href="'/organization/subscription/portal'">
                                        <Button variant="secondary">Manage Billing</Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

