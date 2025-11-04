<script setup lang="ts">
import OrganizationInvitationController from '@/actions/App/Http/Controllers/Organizations/OrganizationInvitationController';
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import AppLayout from '@/layouts/AppLayout.vue';
import BlankLayout from '@/layouts/BlankLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Building2, Check, X } from 'lucide-vue-next';
import { login } from '@/routes';
import { computed } from 'vue';

interface Props {
    invitation: {
        token: string;
        organization: {
            id: string;
            name: string;
        };
        invited_role?: string;
        invited_at?: string;
        is_current_user?: boolean;
    };
}

const props = defineProps<Props>();
const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Invitation',
        href: '#',
    },
];
</script>

<template>
    <BlankLayout>
        <Head title="Organization Invitation" />

        <div class="flex min-h-screen items-center justify-center p-4">
            <Card class="w-full max-w-md">
                <CardHeader class="text-center">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                        <Building2 class="h-6 w-6 text-primary" />
                    </div>
                    <CardTitle>You've been invited!</CardTitle>
                    <CardDescription>
                        You've been invited to join an organization
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-6">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3 rounded-lg border p-4">
                            <Avatar class="h-10 w-10">
                                <AvatarFallback>
                                    {{ props.invitation.organization.name.substring(0, 2).toUpperCase() }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="flex-1">
                                <p class="font-medium">{{ props.invitation.organization.name }}</p>
                                <p v-if="props.invitation.invited_role" class="text-sm text-muted-foreground">
                                    Role: {{ props.invitation.invited_role }}
                                </p>
                            </div>
                        </div>

                        <div v-if="props.invitation.invited_at" class="text-sm text-muted-foreground">
                            Invited on {{ new Date(props.invitation.invited_at).toLocaleDateString() }}
                        </div>
                    </div>

                    <div v-if="!isAuthenticated" class="space-y-4">
                        <p class="text-center text-sm text-muted-foreground">
                            Please log in to accept this invitation
                        </p>
                        <Link :href="login()" class="block">
                            <Button class="w-full">Log In</Button>
                        </Link>
                    </div>

                    <div v-else-if="props.invitation.is_current_user" class="space-y-4">
                        <Form
                            v-bind="OrganizationInvitationController.accept.form({ token: props.invitation.token })"
                            v-slot="{ processing }"
                            class="space-y-2"
                        >
                            <Button type="submit" class="w-full" :disabled="processing">
                                <Check class="mr-2 h-4 w-4" />
                                Accept Invitation
                            </Button>
                        </Form>

                        <Form
                            v-bind="OrganizationInvitationController.decline.form({ token: props.invitation.token })"
                            v-slot="{ processing }"
                        >
                            <Button
                                type="submit"
                                variant="outline"
                                class="w-full"
                                :disabled="processing"
                            >
                                <X class="mr-2 h-4 w-4" />
                                Decline
                            </Button>
                        </Form>
                    </div>

                    <div v-else class="text-center text-sm text-muted-foreground">
                        <p>This invitation is for a different account.</p>
                        <p class="mt-2">Please log in with the correct account.</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </BlankLayout>
</template>

