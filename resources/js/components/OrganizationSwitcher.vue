<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Org = {
    id: string
    name: string
    status?: string
};

const page = usePage();

const organizations = computed<Org[]>(() => (page.props.organizations as Org[]) ?? []);
const currentOrganization = computed<Org | null>(
    () => (page.props.organization as Org | null) ?? null,
);

const selectedId = ref<string | undefined>(currentOrganization.value?.id);

function onChange(value: string) {
    if (!value) return;
    selectedId.value = value;
    router.post(`/organizations/${value}/switch`, {}, { preserveScroll: true });
}
</script>

<template>
    <div v-if="organizations.length" class="ml-4 w-[240px]">
        <Select :model-value="selectedId" @update:model-value="onChange">
            <SelectTrigger class="h-9 w-full">
                <SelectValue :placeholder="currentOrganization?.name ?? 'Select organization'" />
            </SelectTrigger>
            <SelectContent>
                <SelectGroup>
                    <SelectLabel>Organizations</SelectLabel>
                    <SelectItem
                        v-for="org in organizations"
                        :key="org.id"
                        :value="org.id"
                    >
                        {{ org.name }}
                    </SelectItem>
                </SelectGroup>
            </SelectContent>
        </Select>
    </div>
    <div v-else class="ml-4">
        <a
            href="/organizations/create"
            class="text-sm text-neutral-600 hover:underline dark:text-neutral-300"
        >
            Create organization
        </a>
    </div>
</template>


