<template>
  <div
    v-if="
      editorStore.selectedObject &&
      editorStore.selectedObject.type === 'image'
    "
    class="absolute top-24 left-4 z-20 bg-muted rounded-md p-2 shadow-md border border-border flex flex-col space-y-2 w-56"
  >
    <ImageToolbar />
  </div>
</template>

<script setup lang="ts">
import ImageToolbar from '@/components/ImageToolbar.vue'
import { useEditorStore } from '@/stores/editor'

const editorStore = useEditorStore()
</script>

<style scoped>
/* Add any specific styling for the floating container here if needed */
</style>
