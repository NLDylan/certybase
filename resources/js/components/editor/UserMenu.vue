<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button
        variant="ghost"
        class="relative h-10 w-10 rounded-full bg-muted"
      >
        <div
          class="w-10 h-10 rounded-full bg-muted flex items-center justify-center"
        >
          <UserIcon class="h-5 w-5 text-muted-foreground" />
        </div>
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent class="w-56" align="end">
      <DropdownMenuLabel class="font-normal">
        <div class="flex flex-col space-y-1">
          <p class="text-sm font-medium leading-none">Anonymous User</p>
          <p class="text-xs leading-none text-muted-foreground">
            user@example.com
          </p>
        </div>
      </DropdownMenuLabel>
      <DropdownMenuSeparator />
      <DropdownMenuItem @click="router.push('/')">
        <HomeIcon class="mr-2 h-4 w-4" />
        <span>Home</span>
      </DropdownMenuItem>
      <DropdownMenuItem>
        <ImageIcon class="mr-2 h-4 w-4" />
        <span>Canvases</span>
      </DropdownMenuItem>
      <DropdownMenuItem>
        <UserCircleIcon class="mr-2 h-4 w-4" />
        <span>Profile</span>
      </DropdownMenuItem>
      <DropdownMenuItem>
        <SettingsIcon class="mr-2 h-4 w-4" />
        <span>Settings</span>
      </DropdownMenuItem>
      <DropdownMenuSeparator />
      <DropdownMenuItem @click="router.push('/')">
        <LogOutIcon class="mr-2 h-4 w-4" />
        <span>Log out</span>
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  UserIcon,
  HomeIcon,
  ImageIcon,
  UserCircleIcon,
  SettingsIcon,
  LogOutIcon,
} from 'lucide-vue-next'

const router = useRouter()
</script>
