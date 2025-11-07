<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import InputError from '@/components/InputError.vue';
import type { BreadcrumbItem } from '@/types';
import type { Design } from '@/types/models';

type StatusOption = {
    value: string;
    label: string;
};

interface DesignFormPayload extends Design {
    status_label?: string;
    organization?: {
        id: string;
        name: string;
    } | null;
    created_at: string | null;
    updated_at: string | null;
}

interface Props {
    design: DesignFormPayload;
    statusOptions: StatusOption[];
    can: {
        update: boolean;
        publish: boolean;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Designs',
        href: '/designs',
    },
    {
        title: props.design.name,
        href: `/designs/${props.design.id}`,
    },
    {
        title: 'Edit details',
        href: `/designs/${props.design.id}/details`,
    },
];

const canUpdate = computed(() => props.can.update === true);
const name = ref<string>(props.design.name);
const description = ref<string>(props.design.description ?? '');
const selectedStatus = ref<string>(props.design.status);

watch(
    () => props.design.status,
    (next) => {
        selectedStatus.value = next;
    }
);

watch(
    () => props.design.name,
    (next) => {
        name.value = next;
    }
);

watch(
    () => props.design.description,
    (next) => {
        description.value = next ?? '';
    }
);

const actionUrl = computed(() => `/designs/${props.design.id}`);
const showUrl = computed(() => `/designs/${props.design.id}`);
const editorUrl = computed(() => `/editor/${props.design.id}`);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${props.design.name}`" />

        <div class="flex w-full flex-col gap-6 px-4 pb-12 pt-6 lg:px-12 xl:px-16">
            <div class="flex flex-col gap-3 justify-between md:flex-row md:items-center">
                <div>
                    <h1 class="text-2xl font-semibold">Edit design details</h1>
                    <p class="text-sm text-muted-foreground">
                        Update the metadata for this design. Content changes happen in the visual editor.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="showUrl">
                        <Button variant="outline">
                            Back to Overview
                        </Button>
                    </Link>
                    <Link :href="editorUrl">
                        <Button variant="secondary">
                            Open Editor
                        </Button>
                    </Link>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Design metadata</CardTitle>
                    <CardDescription>
                        These details appear across the platform and help your team identify this design.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        :action="actionUrl"
                        method="put"
                        class="space-y-6"
                        setDefaultsOnSuccess
                        :preserve-scroll="true"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        <div class="grid gap-4">
                            <div class="grid gap-2">
                                <Label for="name">Title</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    v-model="name"
                                    :disabled="!canUpdate || processing"
                                    required
                                    autocomplete="off"
                                    placeholder="Certificate of Excellence"
                                />
                                <InputError :message="errors.name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="description">Description</Label>
                                <textarea
                                    id="description"
                                    name="description"
                                    v-model="description"
                                    :disabled="!canUpdate || processing"
                                    rows="4"
                                    class="flex min-h-[120px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    placeholder="Provide context for your team about where and when to use this design"
                                />
                                <InputError :message="errors.description" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="status">Status</Label>
                                <select
                                    id="status"
                                    v-model="selectedStatus"
                                    name="status"
                                    :disabled="!canUpdate || processing"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option
                                        v-for="option in props.statusOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                                <InputError :message="errors.status" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <Button :disabled="!canUpdate || processing">
                                Save changes
                            </Button>
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
                                    Saved.
                                </p>
                            </Transition>
                        </div>
                    </Form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Quick reference</CardTitle>
                    <CardDescription>
                        Helpful metadata to keep track of changes.
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium uppercase text-muted-foreground">Current status</p>
                        <p class="text-sm text-foreground">{{ props.design.status_label ?? props.design.status }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase text-muted-foreground">Last updated</p>
                        <p class="text-sm text-foreground">
                            {{ props.design.updated_at ? new Date(props.design.updated_at).toLocaleString() : '—' }}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>


