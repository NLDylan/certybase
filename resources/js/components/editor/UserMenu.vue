<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button variant="ghost" class="relative size-10 rounded-full p-1">
        <Avatar class="size-8 overflow-hidden rounded-full">
          <AvatarImage v-if="user?.avatar" :src="user.avatar" :alt="user?.name" />
          <AvatarFallback class="rounded-full font-semibold">
            {{ user ? (user.initials || getInitials(user.name)) : '?' }}
          </AvatarFallback>
        </Avatar>
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent class="w-56" align="end">
      <DropdownMenuLabel class="font-normal">
        <div v-if="user" class="flex flex-col space-y-1">
          <p class="text-sm font-medium leading-none">{{ user.name }}</p>
          <p class="text-xs leading-none text-muted-foreground">
            {{ user.email }}
          </p>
        </div>
        <div v-else class="flex flex-col space-y-1">
          <p class="text-sm font-medium leading-none">Not logged in</p>
        </div>
      </DropdownMenuLabel>
      <DropdownMenuSeparator />
      <DropdownMenuItem :as-child="true">
        <Link :href="dashboard.url()" class="block w-full">
        <HomeIcon class="mr-2 h-4 w-4" />
        <span>Dashboard</span>
        </Link>
      </DropdownMenuItem>
      <DropdownMenuItem :as-child="true">
        <Link :href="profileEdit.url()" class="block w-full">
        <SettingsIcon class="mr-2 h-4 w-4" />
        <span>Settings</span>
        </Link>
      </DropdownMenuItem>
      <DropdownMenuItem v-if="organization" :as-child="true">
        <Link :href="organizationSettingsUrl" class="block w-full">
        <Building2Icon class="mr-2 h-4 w-4" />
        <span>Organization</span>
        </Link>
      </DropdownMenuItem>
      <DropdownMenuSeparator />
      <DropdownMenuItem :as-child="true">
        <Link :href="logout.url()" method="post" class="block w-full" @click="handleLogout">
        <LogOutIcon class="mr-2 h-4 w-4" />
        <span>Log out</span>
        </Link>
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import {
  UserIcon,
  HomeIcon,
  SettingsIcon,
  LogOutIcon,
  Building2 as Building2Icon,
} from 'lucide-vue-next'
import { logout, dashboard } from '@/routes'
import { edit as profileEdit } from '@/routes/profile'
import { useInitials } from '@/composables/useInitials'
import { computed } from 'vue'

const page = usePage()
const user = computed(() => page.props.auth.user)
const organization = computed(() => page.props.organization as { id: string; name: string } | null)
const organizationSettingsUrl = computed(() =>
  organization.value ? '/organization/settings' : null
)

const { getInitials } = useInitials()

const handleLogout = () => {
  router.flushAll()
}
</script>
