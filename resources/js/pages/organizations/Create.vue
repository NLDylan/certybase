<script setup lang="ts">
import OrganizationController from '@/actions/App/Http/Controllers/Organizations/OrganizationController';
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
                    <div class="space-y-4">
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
                            <Label for="email">Email Address</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
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
                                placeholder="https://example.com"
                                autocomplete="url"
                            />
                            <InputError :message="errors.website" />
                        </div>
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