<script setup lang="ts">
import OrganizationController from '@/actions/App/Http/Controllers/Organizations/OrganizationController';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Building2, Users, CreditCard, Settings } from 'lucide-vue-next';
import { dashboard } from '@/routes';

interface Props {
    organization: {
        id: string;
        name: string;
        description?: string;
        email?: string;
        phone_number?: string;
        website?: string;
        status: string;
        has_active_subscription?: boolean;
        users?: Array<{
            id: string;
            name: string;
            email: string;
            pivot: {
                status: string;
                invited_role?: string;
            };
        }>;
        designs?: Array<{
            id: string;
            name: string;
        }>;
        campaigns?: Array<{
            id: string;
            name: string;
        }>;
        certificates?: Array<{
            id: string;
            recipient_name: string;
        }>;
    };
}

const props = defineProps<Props>();
const page = usePage();
const currentOrganization = computed(() => page.props.organization as Props['organization'] | null);

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: props.organization.name,
        href: '#',
    },
];

const activeTab = ref('general');
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`${props.organization.name} - Organization`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Avatar class="h-12 w-12">
                        <AvatarFallback>
                            {{ props.organization.name.substring(0, 2).toUpperCase() }}
                        </AvatarFallback>
                    </Avatar>
                    <div>
                        <h1 class="text-2xl font-semibold">{{ props.organization.name }}</h1>
                        <p v-if="props.organization.description" class="text-sm text-muted-foreground">
                            {{ props.organization.description }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Badge :variant="props.organization.status === 'active' ? 'default' : 'secondary'">
                        {{ props.organization.status }}
                    </Badge>
                    <Link
                        :href="OrganizationController.show({ organization_id: props.organization.id })"
                        v-slot="{ href }"
                    >
                        <Button variant="outline" :href="href">
                            <Settings class="mr-2 h-4 w-4" />
                            Settings
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Tabs -->
            <Tabs v-model="activeTab" class="w-full">
                <TabsList>
                    <TabsTrigger value="general">
                        <Building2 class="mr-2 h-4 w-4" />
                        General
                    </TabsTrigger>
                    <TabsTrigger value="users">
                        <Users class="mr-2 h-4 w-4" />
                        Users
                        <Badge v-if="props.organization.users?.length" variant="secondary" class="ml-2">
                            {{ props.organization.users.length }}
                        </Badge>
                    </TabsTrigger>
                    <TabsTrigger value="subscription">
                        <CreditCard class="mr-2 h-4 w-4" />
                        Subscription
                    </TabsTrigger>
                </TabsList>

                <!-- General Tab -->
                <TabsContent value="general" class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle class="text-base">Organization Details</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-3">
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Name</p>
                                    <p class="text-sm">{{ props.organization.name }}</p>
                                </div>
                                <div v-if="props.organization.description">
                                    <p class="text-sm font-medium text-muted-foreground">Description</p>
                                    <p class="text-sm">{{ props.organization.description }}</p>
                                </div>
                                <div v-if="props.organization.email">
                                    <p class="text-sm font-medium text-muted-foreground">Email</p>
                                    <p class="text-sm">{{ props.organization.email }}</p>
                                </div>
                                <div v-if="props.organization.phone_number">
                                    <p class="text-sm font-medium text-muted-foreground">Phone</p>
                                    <p class="text-sm">{{ props.organization.phone_number }}</p>
                                </div>
                                <div v-if="props.organization.website">
                                    <p class="text-sm font-medium text-muted-foreground">Website</p>
                                    <a
                                        :href="props.organization.website"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-sm text-primary hover:underline"
                                    >
                                        {{ props.organization.website }}
                                    </a>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle class="text-base">Statistics</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-3">
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Designs</p>
                                    <p class="text-2xl font-semibold">
                                        {{ props.organization.designs?.length ?? 0 }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Campaigns</p>
                                    <p class="text-2xl font-semibold">
                                        {{ props.organization.campaigns?.length ?? 0 }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Certificates</p>
                                    <p class="text-2xl font-semibold">
                                        {{ props.organization.certificates?.length ?? 0 }}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- Users Tab -->
                <TabsContent value="users" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="text-base">Team Members</CardTitle>
                                    <CardDescription>
                                        Manage members of this organization
                                    </CardDescription>
                                </div>
                                <Link
                                    v-if="currentOrganization"
                                    :href="`/organizations/${currentOrganization.id}/users`"
                                >
                                    <Button size="sm">Manage Users</Button>
                                </Link>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div v-if="props.organization.users?.length" class="space-y-2">
                                <div
                                    v-for="user in props.organization.users"
                                    :key="user.id"
                                    class="flex items-center justify-between rounded-md border p-3"
                                >
                                    <div class="flex items-center gap-3">
                                        <Avatar class="h-8 w-8">
                                            <AvatarFallback>
                                                {{ user.name.substring(0, 2).toUpperCase() }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div>
                                            <p class="text-sm font-medium">{{ user.name }}</p>
                                            <p class="text-xs text-muted-foreground">{{ user.email }}</p>
                                        </div>
                                    </div>
                                    <Badge v-if="user.pivot.invited_role" variant="secondary">
                                        {{ user.pivot.invited_role }}
                                    </Badge>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-sm text-muted-foreground">
                                No users found
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Subscription Tab -->
                <TabsContent value="subscription" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="text-base">Subscription</CardTitle>
                                    <CardDescription>
                                        Manage your organization's subscription and billing
                                    </CardDescription>
                                </div>
                                <Link
                                    v-if="currentOrganization"
                                    :href="`/organizations/${currentOrganization.id}/subscription`"
                                >
                                    <Button size="sm">Manage Subscription</Button>
                                </Link>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-sm font-medium text-muted-foreground">Status</p>
                                    <Badge
                                        :variant="props.organization.has_active_subscription ? 'default' : 'secondary'"
                                    >
                                        {{ props.organization.has_active_subscription ? 'Active' : 'No Subscription' }}
                                    </Badge>
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    Subscription management is coming soon.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>

