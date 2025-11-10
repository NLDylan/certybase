<script setup lang="ts">
import OrganizationController from '@/actions/App/Http/Controllers/Organizations/OrganizationController';
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import InputError from '@/components/InputError.vue';
import BlankLayout from '@/layouts/BlankLayout.vue';
import { logout } from '@/routes';

const page = usePage();
const userEmail = computed<string | undefined>(() => page.props.auth?.user?.email as string | undefined);
</script>

<template>
    <Head title="Create Organization" />

    <BlankLayout>
        <div class="fixed left-6 top-6 z-10 flex flex-col gap-2 text-xs text-muted-foreground">
            <Link class="pointer-events-auto w-fit" :href="logout()" as="button">
                <Button size="sm" variant="outline">Sign out</Button>
            </Link>
            <span v-if="userEmail">Logged in as: <span class="font-medium text-foreground">{{ userEmail }}</span></span>
        </div>

        <Card class="w-full max-w-2xl">
            <CardHeader>
                <CardTitle class="text-center">Create Organization</CardTitle>
                <CardDescription class="text-center">
                    Create a new organization to get started
                </CardDescription>
            </CardHeader>

            <CardContent>
                <Form
                    v-bind="OrganizationController.store.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="space-y-8">
                        <section class="space-y-4">
                            <div class="space-y-2">
                                <CardTitle class="text-left text-base font-semibold">
                                    Organization Profile
                                </CardTitle>
                                <p class="text-sm text-muted-foreground">
                                    Start with the basics so team members recognize this organization.
                                </p>
                            </div>
                            <div class="grid gap-4">
                                <div class="grid gap-2">
                                    <Label for="name">Organization Name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        required
                                        autofocus
                                        placeholder="Acme Inc."
                                        autocomplete="organization"
                                    />
                                    <InputError :message="errors.name" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="description">Description</Label>
                                    <textarea
                                        id="description"
                                        name="description"
                                        rows="3"
                                        placeholder="Tell your team what this organization manages."
                                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <InputError :message="errors.description" />
                                </div>
                            </div>
                        </section>

                        <Separator />

                        <section class="space-y-4">
                            <div class="space-y-2">
                                <h3 class="text-sm font-semibold">
                                    Contact Details
                                </h3>
                                <p class="text-sm text-muted-foreground">
                                    Optional information that helps us and your recipients reach the right people.
                                </p>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="email">Email Address</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        placeholder="contact@example.com"
                                        autocomplete="email"
                                    />
                                    <InputError :message="errors.email" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="phone_number">Phone Number</Label>
                                    <Input
                                        id="phone_number"
                                        type="tel"
                                        name="phone_number"
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
                                        placeholder="https://example.com"
                                        autocomplete="url"
                                    />
                                    <InputError :message="errors.website" />
                                </div>
                            </div>
                        </section>

                        <Separator />

                        <section class="space-y-4">
                            <div class="space-y-2">
                                <h3 class="text-sm font-semibold">
                                    Compliance
                                </h3>
                                <p class="text-sm text-muted-foreground">
                                    Store registration details for invoicing and regulation checks.
                                </p>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="tax_id">Tax ID</Label>
                                    <Input
                                        id="tax_id"
                                        name="tax_id"
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
                                        placeholder="COC-98765"
                                        autocomplete="off"
                                    />
                                    <InputError :message="errors.coc_number" />
                                </div>
                            </div>
                        </section>

                        <Separator />

                        <section class="space-y-4">
                            <div class="space-y-2">
                                <h3 class="text-sm font-semibold">
                                    Mailing Address
                                </h3>
                                <p class="text-sm text-muted-foreground">
                                    Appears on certificates, invoices, and compliance exports.
                                </p>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2 md:col-span-2">
                                    <Label for="address_line1">Address Line 1</Label>
                                    <Input
                                        id="address_line1"
                                        name="address_line1"
                                        placeholder="123 Compliance Way"
                                        autocomplete="address-line1"
                                    />
                                    <InputError :message="errors.address_line1" />
                                </div>

                                <div class="grid gap-2 md:col-span-2">
                                    <Label for="address_line2">Address Line 2</Label>
                                    <Input
                                        id="address_line2"
                                        name="address_line2"
                                        placeholder="Suite 400"
                                        autocomplete="address-line2"
                                    />
                                    <InputError :message="errors.address_line2" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="address_city">City</Label>
                                    <Input
                                        id="address_city"
                                        name="address_city"
                                        placeholder="Metropolis"
                                        autocomplete="address-level2"
                                    />
                                    <InputError :message="errors.address_city" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="address_state">State / Province</Label>
                                    <Input
                                        id="address_state"
                                        name="address_state"
                                        placeholder="NY"
                                        autocomplete="address-level1"
                                    />
                                    <InputError :message="errors.address_state" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="address_postal_code">Postal Code</Label>
                                    <Input
                                        id="address_postal_code"
                                        name="address_postal_code"
                                        placeholder="12345"
                                        autocomplete="postal-code"
                                    />
                                    <InputError :message="errors.address_postal_code" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="address_country">Country</Label>
                                    <input type="hidden" name="address_country" value="NL" />
                                    <Input
                                        id="address_country"
                                        name="address_country"
                                        placeholder="NL"
                                        autocomplete="country"
                                        value="NL"
                                        disabled
                                        class="uppercase"
                                    />
                                    <InputError :message="errors.address_country" />
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="flex items-center justify-between pt-4">
                        <Link :href="OrganizationController.index()">
                            <Button variant="outline" type="button">Cancel</Button>
                        </Link>

                        <div class="flex items-center gap-4">
                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p
                                    v-show="recentlySuccessful"
                                    class="text-sm text-muted-foreground"
                                >
                                    Creating...
                                </p>
                            </Transition>
                            <Button :disabled="processing" type="submit">
                                Create Organization
                            </Button>
                        </div>
                    </div>
                </Form>
            </CardContent>
        </Card>
    </BlankLayout>
</template>
