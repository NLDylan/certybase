<script setup lang="ts">
import { ref, watch } from 'vue'
import { Cropper } from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'

const props = defineProps<{
  open: boolean
  imageSrc: string | null
}>()

const emit = defineEmits(['update:open', 'cropped'])

const dialogOpen = ref(props.open)
const cropperRef = ref<any>(null)

watch(
  () => props.open,
  (newVal) => {
    dialogOpen.value = newVal
  }
)

watch(dialogOpen, (newVal) => {
  emit('update:open', newVal)
})

function cropImage() {
  if (cropperRef.value) {
    const { canvas } = cropperRef.value.getResult()
    if (canvas) {
      emit('cropped', canvas.toDataURL())
      dialogOpen.value = false
    }
  }
}

function onCropperReady() {
  if (cropperRef.value) {
    const cropper = cropperRef.value
    const canvas = cropper.getCanvas()
    if (canvas) {
      cropper.setCoordinates(canvas)
    }
  }
}
</script>

<template>
  <Dialog v-model:open="dialogOpen">
    <DialogContent
      class="sm:max-w-screen-lg md:max-w-screen-xl p-6 rounded-xl space-y-5 bg-background text-foreground"
    >
      <DialogHeader>
        <DialogTitle>Crop Image</DialogTitle>
        <DialogDescription>Adjust the crop area and apply.</DialogDescription>
      </DialogHeader>

      <div
        class="flex justify-center items-center min-h-[400px] max-h-[calc(100vh-15rem)]"
      >
        <Cropper
          v-if="props.imageSrc"
          ref="cropperRef"
          class="cropper"
          :src="props.imageSrc"
          :stencil-props="{}"
          @ready="onCropperReady"
        />
        <p v-else class="text-sm text-muted-foreground">
          Loading image for cropping...
        </p>
      </div>

      <DialogFooter class="mt-4">
        <Button
          class="h-8 px-4 text-xs"
          variant="outline"
          @click="dialogOpen = false"
        >
          Cancel
        </Button>
        <Button class="h-8 px-4 text-xs" @click="cropImage">
          Apply Crop
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<style>
.cropper {
  max-height: 100%;
  max-width: 100%;
  background: #ddd;
}
</style>
