<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/organizations/SettingsLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { ref, computed } from 'vue';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Props {
    organization: {
        id: string;
        name: string;
        has_active_subscription?: boolean;
    };
    subscription: {
        currency: string;
        plans: Record<string, {
            name: string;
            prices: { monthly: string | null; yearly: string | null };
            limits: Record<string, unknown>;
        }>;
    };
    can: {
        update: boolean;
    };
}

const props = defineProps<Props>();

const hasActive = computed<boolean>(() => props.organization.has_active_subscription !== false);

const plan = ref<string>('starter');
const interval = ref<'monthly' | 'yearly'>('monthly');

const availablePlans = computed(() => props.subscription.plans);
const currency = computed(() => props.subscription.currency?.toUpperCase?.() ?? 'USD');

const priceId = computed<string | null>(() => {
    const selected = availablePlans.value[plan.value];
    if (!selected) {
        return null;
    }
    const id = selected.prices[interval.value];
    return typeof id === 'string' && id.length > 0 ? id : null;
});

const canUpdate = computed<boolean>(() => props.can?.update === true);
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
                                <div class="grid gap-3 max-w-md">
                                    <div class="grid gap-2">
                                        <Label for="plan">Plan</Label>
                                        <Select v-model="plan" :disabled="!canUpdate">
                                            <SelectTrigger id="plan" class="w-full">
                                                <SelectValue placeholder="Select plan" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectGroup>
                                                    <SelectLabel>Plans</SelectLabel>
                                                    <SelectItem v-for="(p, key) in availablePlans" :key="key" :value="key">
                                                        {{ p.name }}
                                                    </SelectItem>
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="interval">Billing Interval</Label>
                                        <Select v-model="interval" :disabled="!canUpdate">
                                            <SelectTrigger id="interval" class="w-full">
                                                <SelectValue placeholder="Select interval" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectGroup>
                                                    <SelectLabel>Interval</SelectLabel>
                                                    <SelectItem value="monthly">Monthly</SelectItem>
                                                    <SelectItem value="yearly">Yearly</SelectItem>
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="text-sm text-muted-foreground">
                                        <span>Currency: {{ currency }}</span>
                                    </div>

                                    <Link
                                        as="button"
                                        method="post"
                                        :href="priceId ? `/organization/subscription/checkout/${priceId}` : '#'"
                                        :disabled="!priceId || !canUpdate"
                                    >
                                        <Button :disabled="!priceId || !canUpdate">{{ hasActive ? 'Change Plan' : 'Subscribe' }}</Button>
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

