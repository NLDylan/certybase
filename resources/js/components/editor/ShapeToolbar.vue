<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useEditorStore } from '@/stores/editor'
import * as fabric from 'fabric'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

const editorStore = useEditorStore()

const isShapeOrSvgSelected = computed(() => {
  if (!editorStore.selectedObject) return false
  const type = editorStore.selectedObject.type
  return [
    'rect',
    'circle',
    'ellipse',
    'triangle',
    'line',
    'path',
    'group',
  ].includes(type)
})

const currentFillColor = ref('#ff0000')
const currentStrokeColor = ref('#000000')
const currentStrokeWidth = ref(1)
const currentOpacity = ref(100)
const currentWidth = ref(0)
const currentHeight = ref(0)

watch(
  () => editorStore.selectedObject,
  (newVal) => {
    if (newVal && isShapeOrSvgSelected.value) {
      const object = newVal as fabric.Object
      currentFillColor.value = object.fill?.toString() || '#ff0000'
      currentStrokeColor.value = object.stroke?.toString() || '#000000'
      currentStrokeWidth.value = object.strokeWidth || 1
      currentOpacity.value = (object.opacity || 1) * 100
      currentWidth.value = object.width || 0
      currentHeight.value = object.height || 0
    }
  },
  { immediate: true }
)

function applyFillColor(color: string) {
  editorStore.setFillColor(color)
  currentFillColor.value = color
}

function applyStrokeColor(color: string) {
  editorStore.setStrokeColor(color)
  currentStrokeColor.value = color
}

function applyStrokeWidth(width: number) {
  editorStore.setStrokeWidth(width)
  currentStrokeWidth.value = width
}

function applyOpacity(opacity: number) {
  editorStore.setOpacity(opacity)
  currentOpacity.value = opacity
}

function applyWidth(width: number) {
  editorStore.setWidth(width)
  currentWidth.value = width
}

function applyHeight(height: number) {
  editorStore.setHeight(height)
  currentHeight.value = height
}
</script>

<template>
  <div v-if="isShapeOrSvgSelected" class="flex flex-col space-y-2 p-2">
    <!-- Fill Color -->
    <div class="space-y-1">
      <label class="text-xs">Fill</label>
      <div
        class="w-full h-7 rounded-md border border-primary relative overflow-hidden"
      >
        <span
          class="absolute inset-0"
          :style="{ backgroundColor: currentFillColor }"
        ></span>
        <input
          type="color"
          class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
          v-model="currentFillColor"
          @input="applyFillColor(($event.target as HTMLInputElement).value)"
        />
      </div>
    </div>

    <!-- Stroke Color -->
    <div class="space-y-1">
      <label class="text-xs">Stroke</label>
      <div
        class="w-full h-7 rounded-md border border-primary relative overflow-hidden"
      >
        <span
          class="absolute inset-0"
          :style="{ backgroundColor: currentStrokeColor }"
        ></span>
        <input
          type="color"
          class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
          v-model="currentStrokeColor"
          @input="applyStrokeColor(($event.target as HTMLInputElement).value)"
        />
      </div>
    </div>

    <!-- Stroke Width -->
    <div class="space-y-1">
      <Label class="text-xs">Stroke Width</Label>
      <div class="flex items-center space-x-2">
        <Input
          type="number"
          :model-value="currentStrokeWidth"
          @update:model-value="applyStrokeWidth(Number($event))"
          min="0"
          max="10"
          class="w-20 h-8 text-xs"
        />
        <input
          type="range"
          :value="currentStrokeWidth"
          @input="
            applyStrokeWidth(Number(($event.target as HTMLInputElement).value))
          "
          min="0"
          max="10"
          class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
        />
      </div>
    </div>

    <!-- Opacity -->
    <div class="space-y-1">
      <Label class="text-xs">Opacity</Label>
      <div class="flex items-center space-x-2">
        <Input
          type="number"
          :model-value="currentOpacity"
          @update:model-value="applyOpacity(Number($event))"
          min="0"
          max="100"
          class="w-20 h-8 text-xs"
        />
        <input
          type="range"
          min="0"
          max="100"
          class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
          :value="currentOpacity"
          @input="
            applyOpacity(Number(($event.target as HTMLInputElement).value))
          "
        />
      </div>
    </div>

    <!-- Width -->
    <div class="space-y-1">
      <Label class="text-xs">Width</Label>
      <Input
        type="number"
        :model-value="currentWidth"
        @update:model-value="applyWidth(Number($event))"
        min="1"
        class="w-full h-8 text-xs"
      />
    </div>

    <!-- Height -->
    <div class="space-y-1">
      <Label class="text-xs">Height</Label>
      <Input
        type="number"
        :model-value="currentHeight"
        @update:model-value="applyHeight(Number($event))"
        min="1"
        class="w-full h-8 text-xs"
      />
    </div>
  </div>
</template>
