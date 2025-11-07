<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import SettingsLayout from '@/layouts/organizations/SettingsLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ref, computed } from 'vue';
import { Check } from 'lucide-vue-next';

interface Plan {
    name: string;
    prices: {
        monthly: string | null;
        yearly: string | null;
    };
    display: {
        monthly: number | null;
        yearly: number | null;
    };
    limits: {
        members: number;
        designs: number;
        certificates_per_month: number;
        custom_branding: boolean;
        priority_support: boolean;
    };
}

interface Props {
    organization: {
        id: string;
        name: string;
        has_active_subscription?: boolean;
    };
    subscription: {
        currency: string;
        plans: Record<string, Plan>;
        current?: {
            stripe_price?: string | null;
            plan_key?: string | null;
            interval?: 'monthly' | 'yearly' | null;
        };
    };
    can: {
        update: boolean;
    };
}

const props = defineProps<Props>();

const hasActive = computed<boolean>(() => props.organization.has_active_subscription === true);
const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Organization', href: '/organization/settings' },
    { title: 'Settings', href: '/organization/settings' },
    { title: 'Subscription' },
];
const interval = ref<'monthly' | 'yearly'>(
    (props.subscription.current?.interval as 'monthly' | 'yearly' | null) ?? 'monthly'
);
const availablePlans = computed(() => Object.entries(props.subscription.plans));
const canUpdate = computed<boolean>(() => props.can?.update === true);

const currencyFormatter = computed(() => new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: (props.subscription.currency || 'USD').toUpperCase(),
}));

const formatFeature = (value: unknown): string => {
    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }
    if (typeof value === 'number') {
        if (value === -1) {
            return 'Unlimited';
        }
        return value.toLocaleString();
    }
    return String(value);
};

const getDisplayAmount = (plan: Plan): number | null => {
    const amount = plan.display?.[interval.value] ?? null;
    return typeof amount === 'number' ? amount : null;
};

const getFormattedPrice = (plan: Plan): string | null => {
    const amount = getDisplayAmount(plan);
    return amount !== null ? currencyFormatter.value.format(amount) : null;
};

const getPriceId = (plan: Plan): string | null => {
    const priceId = plan.prices[interval.value];
    return typeof priceId === 'string' && priceId.length > 0 ? priceId : null;
};

const isCurrentPlanCard = (planKey: string, plan: Plan): boolean => {
    const currentPrice = props.subscription.current?.stripe_price ?? null;
    const selectedIntervalPrice = plan.prices[interval.value];
    return !!currentPrice && !!selectedIntervalPrice && currentPrice === selectedIntervalPrice;
};

const isCurrentSelection = (plan: Plan): boolean => {
    const currentPrice = props.subscription.current?.stripe_price ?? null;
    const selectedPrice = getPriceId(plan);
    return !!currentPrice && !!selectedPrice && currentPrice === selectedPrice;
};

const getCtaLabel = (planKey: string, plan: Plan): string => {
    if (isCurrentSelection(plan)) {
        return 'Current Plan';
    }

    const intervalLabel = interval.value === 'yearly' ? 'Yearly' : 'Monthly';

    if (hasActive.value) {
        if (isCurrentPlanCard(planKey, plan)) {
            // Same plan, different interval
            return `Switch to ${intervalLabel}`;
        }
        // Different plan
        return `Switch to ${plan.name} (${intervalLabel})`;
    }

    // No active subscription
    return `Subscribe to ${plan.name} (${intervalLabel})`;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Organization Subscription" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <!-- Current Status Card -->
                <Card>
                    <CardHeader>
                        <CardTitle>Subscription Status</CardTitle>
                        <CardDescription>
                            Manage your organization's subscription and billing
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Current Status</p>
                                <Badge variant="secondary" class="mt-1">
                                    {{ hasActive ? 'Active' : 'No Subscription' }}
                                </Badge>
                            </div>
                            <div>
                                <Link :href="'/organization/subscription/portal'">
                                    <Button variant="secondary">Manage Billing</Button>
                                </Link>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Pricing Table -->
                <div class="flex flex-col space-y-6">
                    <div class="flex flex-col items-center space-y-4">
                        <h2 class="text-2xl font-bold">Choose Your Plan</h2>
                        <p class="text-muted-foreground">Select the perfect plan for your organization</p>
                        
                        <!-- Monthly/Yearly Toggle -->
                        <div class="inline-flex items-center rounded-lg border p-1 bg-muted">
                            <button
                                @click="interval = 'monthly'"
                                :class="[
                                    'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                                    interval === 'monthly'
                                        ? 'bg-background text-foreground shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground'
                                ]"
                            >
                                Monthly
                            </button>
                            <button
                                @click="interval = 'yearly'"
                                :class="[
                                    'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                                    interval === 'yearly'
                                        ? 'bg-background text-foreground shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground'
                                ]"
                            >
                                Yearly
                            </button>
                        </div>
                    </div>

                    <!-- Plans Grid -->
                    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-2">
                        <Card
                            v-for="[planKey, plan] in availablePlans"
                            :key="planKey"
                            :class="[
                                'relative',
                                planKey === 'growth' ? 'border-primary shadow-lg' : ''
                            ]"
                        >
                            <CardHeader>
                                <div class="flex items-center justify-between">
                                    <CardTitle>{{ plan.name }}</CardTitle>
                                    <div class="flex items-center gap-2">
                                        <Badge v-if="isCurrentPlanCard(planKey as string, plan)" variant="secondary">
                                            Current
                                        </Badge>
                                        <Badge v-if="planKey === 'growth'" variant="default">
                                            Popular
                                        </Badge>
                                    </div>
                                </div>
                                <CardDescription v-if="planKey === 'growth'">
                                    Best for growing teams
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- Price -->
                                <div>
                                    <div class="flex items-baseline gap-2">
                                        <span
                                            v-if="getDisplayAmount(plan) !== null"
                                            class="text-4xl font-bold"
                                        >
                                            {{ getFormattedPrice(plan) }}
                                        </span>
                                        <span
                                            v-else
                                            class="text-4xl font-bold text-muted-foreground"
                                        >
                                            -
                                        </span>
                                        <span
                                            v-if="getDisplayAmount(plan) !== null"
                                            class="text-muted-foreground"
                                        >
                                            / {{ interval === 'monthly' ? 'month' : 'year' }}
                                        </span>
                                    </div>
                                    <p
                                        v-if="interval === 'yearly' && plan.display?.monthly && plan.display?.yearly"
                                        class="mt-1 text-sm text-muted-foreground"
                                    >
                                        {{ `Save ${Math.round((1 - (plan.display.yearly / (plan.display.monthly * 12))) * 100)}% vs monthly` }}
                                    </p>
                                </div>

                                <!-- CTA Button -->
                                <Link
                                    v-if="getPriceId(plan)"
                                    as="button"
                                    method="post"
                                    :href="`/organization/subscription/checkout/${getPriceId(plan)}`"
                                    :disabled="!canUpdate"
                                >
                                    <Button
                                        :variant="planKey === 'growth' ? 'default' : 'outline'"
                                        class="w-full"
                                        :disabled="!canUpdate || isCurrentSelection(plan)"
                                    >
                                        {{ getCtaLabel(planKey as string, plan) }}
                                    </Button>
                                </Link>

                                <!-- Features List -->
                                <div class="space-y-3 pt-4 border-t">
                                    <div class="flex items-start gap-3">
                                        <Check class="h-5 w-5 text-primary mt-0.5 shrink-0" />
                                        <div class="flex-1">
                                            <span class="text-sm font-medium">Team Members:</span>
                                            <span class="text-sm text-muted-foreground ml-1">
                                                {{ formatFeature(plan.limits.members) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <Check class="h-5 w-5 text-primary mt-0.5 shrink-0" />
                                        <div class="flex-1">
                                            <span class="text-sm font-medium">Designs:</span>
                                            <span class="text-sm text-muted-foreground ml-1">
                                                {{ formatFeature(plan.limits.designs) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <Check class="h-5 w-5 text-primary mt-0.5 shrink-0" />
                                        <div class="flex-1">
                                            <span class="text-sm font-medium">Certificates/Month:</span>
                                            <span class="text-sm text-muted-foreground ml-1">
                                                {{ formatFeature(plan.limits.certificates_per_month) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <Check class="h-5 w-5 text-primary mt-0.5 shrink-0" />
                                        <div class="flex-1">
                                            <span class="text-sm font-medium">Custom Branding:</span>
                                            <span class="text-sm text-muted-foreground ml-1">
                                                {{ formatFeature(plan.limits.custom_branding) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <Check class="h-5 w-5 text-primary mt-0.5 shrink-0" />
                                        <div class="flex-1">
                                            <span class="text-sm font-medium">Priority Support:</span>
                                            <span class="text-sm text-muted-foreground ml-1">
                                                {{ formatFeature(plan.limits.priority_support) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
