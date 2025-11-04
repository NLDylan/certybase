<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/organizations/SettingsLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import type { User } from '@/types';

interface Props {
    users: User[];
    organization: {
        id: string;
        name: string;
    };
}

const props = defineProps<Props>();

const removeUser = (userId: string) => {
    if (confirm('Are you sure you want to remove this user from the organization?')) {
        router.delete(`/organization/users/${userId}`);
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Organization Users" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Organization Users</CardTitle>
                        <CardDescription>
                            Manage users who have access to this organization
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="props.users.length === 0" class="text-center py-8 text-muted-foreground">
                            <p>No users found.</p>
                        </div>
                        <div v-else class="space-y-4">
                            <div
                                v-for="user in props.users"
                                :key="user.id"
                                class="flex items-center justify-between p-4 border rounded-lg"
                            >
                                <div class="flex items-center gap-4">
                                    <div>
                                        <p class="font-medium">{{ user.name }}</p>
                                        <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                                    </div>
                                    <Badge variant="secondary">
                                        {{ user.pivot?.invited_role ?? 'Member' }}
                                    </Badge>
                                </div>
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    @click="removeUser(user.id)"
                                >
                                    Remove
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

