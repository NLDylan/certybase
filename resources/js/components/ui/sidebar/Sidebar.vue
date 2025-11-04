<script setup lang="ts">
import type { SidebarProps } from "."
import { cn } from "@/lib/utils"
import { computed } from "vue"
import { Sheet, SheetContent } from '@/components/ui/sheet'
import { SIDEBAR_WIDTH, SIDEBAR_WIDTH_MOBILE, SIDEBAR_WIDTH_ICON, useSidebar } from "./utils"

defineOptions({
  inheritAttrs: false,
})

const props = withDefaults(defineProps<SidebarProps>(), {
  side: "left",
  variant: "sidebar",
  collapsible: "offcanvas",
})

const { isMobile, state, openMobile, setOpenMobile } = useSidebar()

const spacerStyle = computed(() => {
  if (state.value === 'collapsed' && props.collapsible === 'icon') {
    if (props.variant === 'floating' || props.variant === 'inset') {
      return { width: `calc(${SIDEBAR_WIDTH_ICON} + 1rem)` }
    }
    // For sidebar variant, match the sidebar container width exactly (border is included in border-box)
    return { width: SIDEBAR_WIDTH_ICON }
  }
  if (props.collapsible === 'offcanvas' && state.value === 'collapsed') {
    return { width: '0' }
  }
  return { width: SIDEBAR_WIDTH }
})

const sidebarContainerStyle = computed(() => {
  if (state.value === 'collapsed' && props.collapsible === 'icon') {
    if (props.variant === 'floating' || props.variant === 'inset') {
      return { width: `calc(${SIDEBAR_WIDTH_ICON} + 1rem + 2px)` } // +2px for padding
    }
    // For sidebar variant, use icon width (border is handled by border-r class)
    return { width: SIDEBAR_WIDTH_ICON }
  }
  // When expanded, use full sidebar width
  return { width: SIDEBAR_WIDTH }
})
</script>

<template>
  <div
    v-if="collapsible === 'none'"
    :class="cn('flex h-full w-[--sidebar-width] flex-col bg-sidebar text-sidebar-foreground', props.class)"
    v-bind="$attrs"
  >
    <slot />
  </div>

  <Sheet v-else-if="isMobile" :open="openMobile" v-bind="$attrs" @update:open="setOpenMobile">
    <SheetContent
      data-sidebar="sidebar"
      data-mobile="true"
      :side="side"
      class="w-[--sidebar-width] bg-sidebar p-0 text-sidebar-foreground [&>button]:hidden"
      :style="{
        '--sidebar-width': SIDEBAR_WIDTH_MOBILE,
      }"
    >
      <div class="flex h-full w-full flex-col">
        <slot />
      </div>
    </SheetContent>
  </Sheet>

  <div
    v-else class="group peer hidden md:block"
    :data-state="state"
    :data-collapsible="state === 'collapsed' ? collapsible : ''"
    :data-variant="variant"
    :data-side="side"
  >
    <!-- This is what handles the sidebar gap on desktop  -->
    <div
      :class="cn(
        'duration-200 relative h-svh bg-transparent transition-[width] ease-linear',
        'group-data-[side=right]:rotate-180',
      )"
      :style="spacerStyle"
    />
    <div
      :class="cn(
        'duration-200 fixed inset-y-0 z-10 hidden h-svh transition-[left,right,width] ease-linear md:flex',
        side === 'left'
          ? 'left-0 group-data-[collapsible=offcanvas]:left-[calc(var(--sidebar-width)*-1)]'
          : 'right-0 group-data-[collapsible=offcanvas]:right-[calc(var(--sidebar-width)*-1)]',
        // Adjust the padding for floating and inset variants.
        variant === 'floating' || variant === 'inset'
          ? 'p-2'
          : 'group-data-[side=left]:border-r group-data-[side=right]:border-l',
        props.class,
      )"
      :style="sidebarContainerStyle"
      v-bind="$attrs"
    >
      <div
        data-sidebar="sidebar"
        class="flex h-full w-full flex-col text-sidebar-foreground bg-sidebar group-data-[variant=floating]:rounded-lg group-data-[variant=floating]:border group-data-[variant=floating]:border-sidebar-border group-data-[variant=floating]:shadow"
      >
        <slot />
      </div>
    </div>
  </div>
</template>
