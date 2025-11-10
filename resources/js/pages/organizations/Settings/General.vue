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
        address_line1?: string | null;
        address_line2?: string | null;
        address_city?: string | null;
        address_state?: string | null;
        address_postal_code?: string | null;
        address_country?: string | null;
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
                        <Form v-bind="OrganizationController.update.form()" class="space-y-6" setDefaultsOnSuccess
                            v-slot="{ errors, processing, recentlySuccessful }">
                            <div class="space-y-8">
                                <section class="space-y-4">
                                    <div>
                                        <h3 class="text-sm font-semibold">
                                            Organization Profile
                                        </h3>
                                        <p class="text-sm text-muted-foreground">
                                            Basic details used across certificates and dashboards.
                                        </p>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="name">Organization Name</Label>
                                        <Input id="name" name="name" :default-value="props.organization.name" required
                                            autocomplete="organization" placeholder="Acme Inc." />
                                        <InputError :message="errors.name" />
                                    </div>
                                </section>

                                <Separator />

                                <section class="space-y-4">
                                    <div>
                                        <h3 class="text-sm font-semibold">
                                            Contact Details
                                        </h3>
                                        <p class="text-sm text-muted-foreground">
                                            Used when we need to reach your organization.
                                        </p>
                                    </div>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="grid gap-2">
                                            <Label for="email">Email Address</Label>
                                            <Input id="email" type="email" name="email"
                                                :default-value="props.organization.email"
                                                placeholder="contact@example.com" autocomplete="email" />
                                            <InputError :message="errors.email" />
                                            <p class="text-sm text-muted-foreground">
                                                Optional. Used for organization communications.
                                            </p>
                                        </div>

                                        <div class="grid gap-2">
                                            <Label for="phone_number">Phone Number</Label>
                                            <Input id="phone_number" type="tel" name="phone_number"
                                                :default-value="props.organization.phone_number"
                                                placeholder="+1 (555) 123-4567" autocomplete="tel" />
                                            <InputError :message="errors.phone_number" />
                                        </div>

                                        <div class="grid gap-2 md:col-span-2">
                                            <Label for="website">Website</Label>
                                            <Input id="website" type="url" name="website"
                                                :default-value="props.organization.website"
                                                placeholder="https://example.com" autocomplete="url" />
                                            <InputError :message="errors.website" />
                                        </div>
                                    </div>
                                </section>

                                <Separator />

                                <section class="space-y-4">
                                    <div>
                                        <h3 class="text-sm font-semibold">
                                            Compliance
                                        </h3>
                                        <p class="text-sm text-muted-foreground">
                                            Store registration references for invoices and reporting.
                                        </p>
                                    </div>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="grid gap-2">
                                            <Label for="tax_id">Tax ID</Label>
                                            <Input id="tax_id" name="tax_id"
                                                :default-value="props.organization.tax_id ?? ''"
                                                placeholder="TAX-1234567" autocomplete="off" />
                                            <InputError :message="errors.tax_id" />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="coc_number">Chamber / COC Number</Label>
                                            <Input id="coc_number" name="coc_number"
                                                :default-value="props.organization.coc_number ?? ''"
                                                placeholder="COC-98765" autocomplete="off" />
                                            <InputError :message="errors.coc_number" />
                                        </div>
                                    </div>
                                </section>

                                <Separator />

                                <section class="space-y-4">
                                    <div>
                                        <h3 class="text-sm font-semibold">
                                            Mailing Address
                                        </h3>
                                        <p class="text-sm text-muted-foreground">
                                            Appears on downloadable certificates and invoices.
                                        </p>
                                    </div>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="grid gap-2 md:col-span-2">
                                            <Label for="address_line1">Address Line 1</Label>
                                            <Input id="address_line1" name="address_line1"
                                                :default-value="props.organization.address_line1 ?? ''"
                                                placeholder="123 Compliance Way" autocomplete="address-line1" />
                                            <InputError :message="errors.address_line1" />
                                        </div>
                                        <div class="grid gap-2 md:col-span-2">
                                            <Label for="address_line2">Address Line 2</Label>
                                            <Input id="address_line2" name="address_line2"
                                                :default-value="props.organization.address_line2 ?? ''"
                                                placeholder="Suite 400" autocomplete="address-line2" />
                                            <InputError :message="errors.address_line2" />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="address_city">City</Label>
                                            <Input id="address_city" name="address_city"
                                                :default-value="props.organization.address_city ?? ''"
                                                placeholder="Metropolis" autocomplete="address-level2" />
                                            <InputError :message="errors.address_city" />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="address_state">State / Province</Label>
                                            <Input id="address_state" name="address_state"
                                                :default-value="props.organization.address_state ?? ''" placeholder="NY"
                                                autocomplete="address-level1" />
                                            <InputError :message="errors.address_state" />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="address_postal_code">Postal Code</Label>
                                            <Input id="address_postal_code" name="address_postal_code"
                                                :default-value="props.organization.address_postal_code ?? ''"
                                                placeholder="12345" autocomplete="postal-code" />
                                            <InputError :message="errors.address_postal_code" />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="address_country">Country</Label>
                                            <Input id="address_country" name="address_country"
                                                :default-value="props.organization.address_country ?? ''"
                                                placeholder="US" autocomplete="country" class="uppercase" />
                                            <InputError :message="errors.address_country" />
                                            <p class="text-sm text-muted-foreground">
                                                Use the 2-letter ISO country code (e.g., US, NL).
                                            </p>
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <div class="flex items-center gap-4">
                                <Button :disabled="processing" data-test="update-organization-button">
                                    Save Changes
                                </Button>

                                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                                    <p v-show="recentlySuccessful" class="text-sm text-neutral-600">
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
