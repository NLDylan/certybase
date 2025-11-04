<template>
  <div
    v-if="
      editorStore.selectedObject &&
      editorStore.selectedObject.type === 'i-text'
    "
    class="absolute top-1/2 left-4 transform -translate-y-1/2 z-20 bg-muted rounded-md p-2 shadow-md border border-border flex flex-col space-y-2"
  >
    <TextToolbar />
  </div>
</template>

<script setup lang="ts">
import TextToolbar from '@/components/editor/TextToolbar.vue'
import { useEditorStore } from '@/stores/editor'

const editorStore = useEditorStore()
</script>

<style scoped>
/* Add any specific styling for the floating container here if needed */
</style>
