<template>
  <div
    v-if="
      editorStore.selectedObject &&
      [
        'rect',
        'circle',
        'ellipse',
        'triangle',
        'line',
        'path',
        'group',
      ].includes(editorStore.selectedObject.type)
    "
    class="absolute top-24 left-4 z-20 bg-muted rounded-md p-2 shadow-md border border-border flex flex-col space-y-2"
  >
    <ShapeToolbar />
  </div>
</template>

<script setup lang="ts">
import ShapeToolbar from '@/components/ShapeToolbar.vue'
import { useEditorStore } from '@/stores/editor'

const editorStore = useEditorStore()
</script>

<style scoped>
/* Add any specific styling for the floating container here if needed */
</style>
