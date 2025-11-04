<script setup lang="ts">
import { ref, watch } from 'vue'
import { useEditorStore } from '@/stores/editor'
import * as fabric from 'fabric'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Popover,
  PopoverTrigger,
  PopoverContent,
} from '@/components/ui/popover'

const editorStore = useEditorStore()

const shadowEnabled = ref(false)
const shadowOffsetX = ref(0)
const shadowOffsetY = ref(0)
const shadowBlur = ref(0)
const shadowColor = ref('#000000')

watch(
  () => editorStore.selectedObject,
  (newVal) => {
    if (newVal) {
      const object = newVal as fabric.Object
      // Update shadow state
      const shadow = object.shadow as fabric.Shadow | null
      if (shadow) {
        shadowEnabled.value = true
        shadowOffsetX.value = shadow.offsetX || 0
        shadowOffsetY.value = shadow.offsetY || 0
        shadowBlur.value = shadow.blur || 0
        shadowColor.value = shadow.color || '#000000'
      } else {
        shadowEnabled.value = false
        shadowOffsetX.value = 0
        shadowOffsetY.value = 0
        shadowBlur.value = 0
        shadowColor.value = '#000000'
      }
    } else {
      // No object selected, reset shadow state
      shadowEnabled.value = false
      shadowOffsetX.value = 0
      shadowOffsetY.value = 0
      shadowBlur.value = 0
      shadowColor.value = '#000000'
    }
  },
  { immediate: true }
)

function applyShadow() {
  shadowEnabled.value = true
  editorStore.setObjectShadow({
    enable: true,
    offsetX: shadowOffsetX.value,
    offsetY: shadowOffsetY.value,
    blur: shadowBlur.value,
    color: shadowColor.value,
  })
}

function removeShadow() {
  shadowEnabled.value = false
  editorStore.setObjectShadow({ enable: false })
  // Reset local state
  shadowOffsetX.value = 0
  shadowOffsetY.value = 0
  shadowBlur.value = 0
  shadowColor.value = '#000000'
}
</script>

<template>
  <!-- Shadow Popover -->
  <Popover>
    <PopoverTrigger as-child>
      <Button class="h-8 w-auto px-3" size="sm" variant="outline">
        Shadow
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-56 p-3 space-y-2">
      <div class="space-y-2">
        <div class="space-y-1">
          <label class="text-xs">Offset X</label>
          <Input type="number" v-model.number="shadowOffsetX" @update:model-value="applyShadow"
            class="w-full h-8 text-xs" />
        </div>
        <div class="space-y-1">
          <label class="text-xs">Offset Y</label>
          <Input type="number" v-model.number="shadowOffsetY" @update:model-value="applyShadow"
            class="w-full h-8 text-xs" />
        </div>
        <div class="space-y-1">
          <label class="text-xs">Blur</label>
          <Input type="number" v-model.number="shadowBlur" @update:model-value="applyShadow"
            class="w-full h-8 text-xs" />
        </div>
        <div class="space-y-1">
          <label class="text-xs">Color</label>
          <input type="color" class="w-full h-7 p-0 border rounded" v-model="shadowColor" @input="applyShadow" />
        </div>
      </div>
      <Button variant="outline" size="sm" class="w-full" @click="removeShadow">Remove Shadow</Button>
    </PopoverContent>
  </Popover>
</template>
