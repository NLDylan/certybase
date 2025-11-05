<script setup lang="ts">
import OrganizationController from '@/actions/App/Http/Controllers/Organizations/OrganizationController';
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
    };
}

const props = defineProps<Props>();
const page = usePage();
const organization = computed(() => page.props.organization as Props['organization'] | null);
const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Organization' },
    { title: 'Settings' },
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
                            <div class="space-y-6">
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

                                <div class="grid gap-2">
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

