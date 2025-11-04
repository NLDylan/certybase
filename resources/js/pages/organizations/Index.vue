<script setup lang="ts">
import BlankLayout from '@/layouts/BlankLayout.vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Separator } from '@/components/ui/separator'
import { Button } from '@/components/ui/button'
import { logout } from '@/routes'

interface Org {
  id: string
  name: string
  status?: string
}

const page = usePage()
const organizations = computed<Org[]>(() => (page.props.organizations as Org[]) ?? [])
const userEmail = computed<string | undefined>(() => page.props.auth?.user?.email as string | undefined)

function switchTo(id: string) {
  router.post(`/organizations/${id}/switch`)
}
</script>

<template>
  <Head title="Choose an organization" />

  <BlankLayout>
    <div class="fixed left-6 top-6 z-10 flex flex-col gap-2 text-xs text-muted-foreground">
      <Link class="pointer-events-auto w-fit" :href="logout()" as="button">
        <Button size="sm" variant="outline">Sign out</Button>
      </Link>
      <span v-if="userEmail">Logged in as: <span class="font-medium text-foreground">{{ userEmail }}</span></span>
    </div>
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
  </BlankLayout>
</template>
