<script setup lang="ts">
import OrganizationController from '@/actions/App/Http/Controllers/Organizations/OrganizationController';
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/organizations/SettingsLayout.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    organization: {
        id: string;
        name: string;
        email?: string;
        phone_number?: string;
        website?: string;
        tax_id?: string | null;
        coc_number?: string | null;
        postal_address?: string | null;
    };
}

const props = defineProps<Props>();
const page = usePage();
const organization = computed(() => page.props.organization as Props['organization'] | null);
const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Organization', href: '/organization/settings' },
    { title: 'Settings', href: '/organization/settings' },
    { title: 'General' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Organization Settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Organization Details</CardTitle>
                        <CardDescription>
                            Update your organization's details and contact information
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            v-bind="OrganizationController.update.form()"
                            class="space-y-6"
                            setDefaultsOnSuccess
                            v-slot="{ errors, processing, recentlySuccessful }"
                        >
                            <div class="space-y-8">
                                <section class="space-y-4">
                                    <div>
                                        <h3 class="text-sm font-semibold text-muted-foreground">Organization Profile</h3>
                                        <p class="text-sm text-muted-foreground">
                                            Basic details used across certificates and dashboards.
                                        </p>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="name">Organization Name</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            :default-value="props.organization.name"
                                            required
                                            autocomplete="organization"
                                            placeholder="Acme Inc."
                                        />
                                        <InputError :message="errors.name" />
                                    </div>
                                </section>

                                <Separator />

                                <section class="space-y-4">
                                    <div>
                                        <h3 class="text-sm font-semibold text-muted-foreground">Contact Details</h3>
                                        <p class="text-sm text-muted-foreground">
                                            Used when we need to reach your organization.
                                        </p>
                                    </div>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="grid gap-2">
                                            <Label for="email">Email Address</Label>
                                            <Input
                                                id="email"
                                                type="email"
                                                name="email"
                                                :default-value="props.organization.email"
                                                placeholder="contact@example.com"
                                                autocomplete="email"
                                            />
                                            <InputError :message="errors.email" />
                                            <p class="text-sm text-muted-foreground">
                                                Optional. Used for organization communications.
                                            </p>
                                        </div>

                                        <div class="grid gap-2">
                                            <Label for="phone_number">Phone Number</Label>
                                            <Input
                                                id="phone_number"
                                                type="tel"
                                                name="phone_number"
                                                :default-value="props.organization.phone_number"
                                                placeholder="+1 (555) 123-4567"
                                                autocomplete="tel"
                                            />
                                            <InputError :message="errors.phone_number" />
                                        </div>

                                        <div class="grid gap-2 md:col-span-2">
                                            <Label for="website">Website</Label>
                                            <Input
                                                id="website"
                                                type="url"
                                                name="website"
                                                :default-value="props.organization.website"
                                                placeholder="https://example.com"
                                                autocomplete="url"
                                            />
                                            <InputError :message="errors.website" />
                                        </div>
                                    </div>
                                </section>

                                <Separator />

                                <section class="space-y-4">
                                    <div>
                                        <h3 class="text-sm font-semibold text-muted-foreground">Compliance</h3>
                                        <p class="text-sm text-muted-foreground">
                                            Store registration references for invoices and reporting.
                                        </p>
                                    </div>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="grid gap-2">
                                            <Label for="tax_id">Tax ID</Label>
                                            <Input
                                                id="tax_id"
                                                name="tax_id"
                                                :default-value="props.organization.tax_id ?? ''"
                                                placeholder="TAX-1234567"
                                                autocomplete="off"
                                            />
                                            <InputError :message="errors.tax_id" />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="coc_number">Chamber / COC Number</Label>
                                            <Input
                                                id="coc_number"
                                                name="coc_number"
                                                :default-value="props.organization.coc_number ?? ''"
                                                placeholder="COC-98765"
                                                autocomplete="off"
                                            />
                                            <InputError :message="errors.coc_number" />
                                        </div>
                                    </div>
                                </section>

                                <Separator />

                                <section class="space-y-4">
                                    <div>
                                        <h3 class="text-sm font-semibold text-muted-foreground">Mailing Address</h3>
                                        <p class="text-sm text-muted-foreground">
                                            Appears on downloadable certificates and invoices.
                                        </p>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="postal_address">Postal Address</Label>
                                        <textarea
                                            id="postal_address"
                                            name="postal_address"
                                            rows="4"
                                            :value="props.organization.postal_address ?? ''"
                                            placeholder="123 Example Street&#10;Suite 200&#10;City, State ZIP"
                                            class="flex min-h-[120px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        />
                                        <InputError :message="errors.postal_address" />
                                    </div>
                                </section>
                            </div>

                            <div class="flex items-center gap-4">
                                <Button
                                    :disabled="processing"
                                    data-test="update-organization-button"
                                >
                                    Save Changes
                                </Button>

                                <Transition
                                    enter-active-class="transition ease-in-out"
                                    enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out"
                                    leave-to-class="opacity-0"
                                >
                                    <p
                                        v-show="recentlySuccessful"
                                        class="text-sm text-neutral-600"
                                    >
                                        Saved.
                                    </p>
                                </Transition>
                            </div>
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

