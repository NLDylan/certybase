<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Separator } from '@/components/ui/separator'
import { Button } from '@/components/ui/button'

interface Org {
  id: string
  name: string
  status?: string
}

const page = usePage()
const organizations = computed<Org[]>(() => (page.props.organizations as Org[]) ?? [])

function switchTo(id: string) {
  router.post(`/organizations/${id}/switch`)
}
</script>

<template>
  <Head title="Choose an organization" />

  <AppLayout>
    <div class="flex w-full items-center justify-center p-6">
      <Card class="w-full max-w-xl">
        <CardHeader>
          <CardTitle class="text-center">Select an organization</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="organizations.length" class="flex flex-col">
            <template v-for="(org, index) in organizations" :key="org.id">
              <button
                type="button"
                class="flex items-center justify-between rounded-md p-3 text-left hover:bg-accent"
                @click="switchTo(org.id)"
              >
                <div class="flex items-center gap-3">
                  <Avatar class="h-9 w-9">
                    <AvatarFallback>{{ org.name.substring(0, 2).toUpperCase() }}</AvatarFallback>
                  </Avatar>
                  <div class="flex flex-col">
                    <span class="font-medium">{{ org.name }}</span>
                    <span class="text-xs text-muted-foreground">Click to switch</span>
                  </div>
                </div>
                <Button variant="secondary" size="sm">Open</Button>
              </button>
              <Separator v-if="index < organizations.length - 1" class="my-1" />
            </template>
          </div>
          <div v-else class="text-center text-sm text-muted-foreground">
            You are not a member of any organizations yet.
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
