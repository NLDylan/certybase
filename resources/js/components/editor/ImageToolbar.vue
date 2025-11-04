<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useEditorStore } from '@/stores/editor'
import * as fabric from 'fabric'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
  DialogFooter,
} from '@/components/ui/dialog'
import { toast } from 'vue-sonner'
import ImageCropDialog from '@/components/ImageCropDialog.vue'

const editorStore = useEditorStore()

const isImageSelected = computed(() => {
  return (
    editorStore.selectedObject && editorStore.selectedObject.type === 'image'
  )
})

const imageFitOption = ref('Default') // 'fill', 'fit', 'stretch', 'crop'
const imageFilters = ref({
  brightness: 0,
  contrast: 0,
  saturation: 0,
  grayscale: 0,
  sepia: 0,
  blur: 0,
  pixelate: 0,
  noise: 0,
  invert: false,
  removeWhite: { enable: false, color: '#FFFFFF', distance: 0.02 },
  blendColor: { enable: false, color: '#000000', mode: 'multiply', alpha: 1 },
})

const isImageEditingDialogOpen = ref(false)
const isCropDialogOpen = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)
const previewImage = ref<string | null>(null)
const imageSrcForCrop = ref<string | null>(null)

function updatePreview() {
  if (
    editorStore.selectedObject &&
    editorStore.selectedObject.type === 'image'
  ) {
    const imageObject = editorStore.selectedObject as fabric.Image
    previewImage.value = imageObject.toDataURL({
      format: 'png',
      quality: 0.8,
    })
  }
}

watch(isImageEditingDialogOpen, (isOpen) => {
  if (isOpen) {
    updatePreview()
  }
})

function handleCropImage() {
  if (
    editorStore.selectedObject &&
    editorStore.selectedObject.type === 'image'
  ) {
    const imageObject = editorStore.selectedObject as fabric.Image
    imageSrcForCrop.value = imageObject.toDataURL({
      format: 'png',
      quality: 1,
    })
    isCropDialogOpen.value = true
  }
}

function handleImageCropped(croppedDataUrl: string) {
  if (
    editorStore.selectedObject &&
    editorStore.selectedObject.type === 'image'
  ) {
    const imageObject = editorStore.selectedObject as fabric.Image
    imageObject.setSrc(croppedDataUrl).then(() => {
      if (editorStore.canvas) {
        imageObject.setCoords()
        editorStore.canvas.renderAll()
        editorStore.canvas.fire('object:modified', { target: imageObject })
        toast.success('Image cropped successfully!')
      }
    })
  }
  isCropDialogOpen.value = false
}

watch(
  () => editorStore.selectedObject,
  (newVal) => {
    if (newVal && newVal.type === 'image') {
      const imageObject = newVal as fabric.Image
      // Reset filters to default when a new image is selected or selection changes
      imageFilters.value = {
        brightness: 0,
        contrast: 0,
        saturation: 0,
        grayscale: 0,
        sepia: 0,
        blur: 0,
        pixelate: 0,
        noise: 0,
        invert: false,
        removeWhite: { enable: false, color: '#FFFFFF', distance: 0.02 },
        blendColor: {
          enable: false,
          color: '#000000',
          mode: 'multiply',
          alpha: 1,
        },
      }

      // Attempt to read current filters from the object
      imageObject.filters.forEach((filter: any) => {
        if (!filter) return
        switch (filter.type) {
          case 'Brightness':
            imageFilters.value.brightness = (filter.brightness || 0) * 100
            break
          case 'Contrast':
            imageFilters.value.contrast = (filter.contrast || 0) * 100
            break
          case 'Saturation':
            imageFilters.value.saturation = (filter.saturation || 0) * 100
            break
          case 'Grayscale':
            imageFilters.value.grayscale = 100 // Grayscale is usually 0 or 100
            break
          case 'Sepia':
            imageFilters.value.sepia = 100 // Sepia is usually 0 or 100
            break
          case 'Blur':
            imageFilters.value.blur = filter.blur || 0
            break
          case 'Pixelate':
            imageFilters.value.pixelate = filter.blocksize || 0
            break
          case 'Noise':
            imageFilters.value.noise = filter.noise || 0
            break
          case 'Invert':
            imageFilters.value.invert = true
            break
          case 'RemoveColor':
            imageFilters.value.removeWhite = {
              enable: true,
              color: filter.color || '#FFFFFF',
              distance: filter.distance || 0.02,
            }
            break
          case 'BlendColor':
            imageFilters.value.blendColor = {
              enable: true,
              color: filter.color || '#000000',
              mode: filter.mode || 'multiply',
              alpha: filter.alpha || 1,
            }
            break
        }
      })
    }
  },
  { immediate: true }
)

function handleReplaceImage() {
  if (fileInput.value) {
    fileInput.value.click()
  }
}

function onFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    const file = target.files[0]
    if (!file.type.startsWith('image/') && file.type !== 'image/svg+xml') {
      toast.error(
        'Invalid file type. Please upload an image (e.g., JPG, PNG, SVG).'
      )
      return
    }
    const reader = new FileReader()
    reader.onload = (e) => {
      if (
        e.target?.result &&
        editorStore.selectedObject &&
        editorStore.selectedObject.type === 'image'
      ) {
        const imageObject = editorStore.selectedObject as fabric.Image
        imageObject.setSrc(e.target.result as string).then(() => {
          editorStore.canvas?.renderAll()
          toast.success('Image replaced successfully!')
        })
      }
    }
    reader.readAsDataURL(file)
  }
}

function applyFilter(filterType: string, value: any) {
  editorStore.applyImageFilter(filterType, value)
  setTimeout(updatePreview, 50)
}

function resetFilters() {
  editorStore.resetImageFilters()
  imageFilters.value = {
    brightness: 0,
    contrast: 0,
    saturation: 0,
    grayscale: 0,
    sepia: 0,
    blur: 0,
    pixelate: 0,
    noise: 0,
    invert: false,
    removeWhite: { enable: false, color: '#FFFFFF', distance: 0.02 },
    blendColor: { enable: false, color: '#000000', mode: 'multiply', alpha: 1 },
  }
  setTimeout(updatePreview, 50)
}

function applyImageFit(option: 'fill' | 'fit' | 'stretch' | 'crop') {
  editorStore.setImageFitOption(option)
  imageFitOption.value = option
}

function handleImageEditingDialogClose() {
  // Revert changes if cancel is clicked, or apply if apply is clicked
  // For simplicity, current implementation applies changes live.
  // A more complex implementation would involve a temporary canvas or deep cloning.
  isImageEditingDialogOpen.value = false
}

function applyClipPath(type: 'none' | 'circle' | 'rect' | 'ellipse') {
  editorStore.setImageClipPath(type)
  toast.success(`Clip path set to ${type}.`)
}
</script>

<template>
  <div v-if="isImageSelected" class="flex flex-col space-y-2 p-2">
    <!-- Fit Options -->
    <Select
      v-model="imageFitOption"
      @update:model-value="
        applyImageFit($event as 'fill' | 'fit' | 'stretch' | 'crop')
      "
    >
      <SelectTrigger class="w-full h-8 text-xs">
        <SelectValue placeholder="Fit" />
      </SelectTrigger>
      <SelectContent>
        <SelectGroup>
          <SelectItem value="none">None</SelectItem>
          <SelectItem value="fill">Fill</SelectItem>
          <SelectItem value="fit">Fit</SelectItem>
          <SelectItem value="stretch">Stretch</SelectItem>
          <SelectItem value="crop">Crop</SelectItem>
        </SelectGroup>
      </SelectContent>
    </Select>

    <Select
      v-model="imageFilters.blendColor.mode"
      @update:model-value="
        applyClipPath($event as 'none' | 'circle' | 'rect' | 'ellipse')
      "
    >
      <SelectTrigger class="w-full h-8 text-xs">
        <SelectValue placeholder="Clip Path" />
      </SelectTrigger>
      <SelectContent>
        <SelectGroup>
          <SelectItem value="none">None</SelectItem>
          <SelectItem value="circle">Circle</SelectItem>
          <SelectItem value="rect">Rectangle</SelectItem>
          <SelectItem value="ellipse">Ellipse</SelectItem>
        </SelectGroup>
      </SelectContent>
    </Select>

    <!-- Edit Image Dialog Trigger -->
    <Dialog v-model:open="isImageEditingDialogOpen">
      <DialogTrigger as-child>
        <Button class="h-8 w-full px-3 text-xs" size="sm"> Edit Image </Button>
      </DialogTrigger>
      <DialogContent
        class="sm:max-w-[600px] p-6 rounded-xl space-y-5 bg-background text-foreground"
      >
        <DialogHeader>
          <DialogTitle>Edit Image</DialogTitle>
          <DialogDescription>
            Apply filters, adjust colors, or crop your image.
          </DialogDescription>
        </DialogHeader>

        <div class="grid grid-cols-2 gap-4">
          <!-- Live Preview -->
          <div
            class="col-span-1 flex items-center justify-center bg-gray-100 border rounded p-2"
          >
            <img
              v-if="previewImage"
              :src="previewImage"
              alt="Image Preview"
              class="max-w-full max-h-full object-contain"
            />
            <p v-else class="text-sm text-muted-foreground">Live Preview</p>
          </div>

          <!-- Filters & Adjustments -->
          <div class.vue="col-span-1 space-y-3">
            <h4 class="text-sm font-semibold">Filters</h4>
            <div class="space-y-2">
              <div class="flex items-center gap-2">
                <label class="text-xs w-20">Brightness</label>
                <Input
                  type="number"
                  v-model.number="imageFilters.brightness"
                  @input="applyFilter('Brightness', imageFilters.brightness)"
                  min="0"
                  max="100"
                  class="w-24 h-8 text-xs"
                />
                <input
                  type="range"
                  v-model.number="imageFilters.brightness"
                  @input="applyFilter('Brightness', imageFilters.brightness)"
                  min="0"
                  max="100"
                  class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                />
              </div>
              <div class="flex items-center gap-2">
                <label class="text-xs w-20">Contrast</label>
                <Input
                  type="number"
                  v-model.number="imageFilters.contrast"
                  @input="applyFilter('Contrast', imageFilters.contrast)"
                  min="0"
                  max="100"
                  class="w-24 h-8 text-xs"
                />
                <input
                  type="range"
                  v-model.number="imageFilters.contrast"
                  @input="applyFilter('Contrast', imageFilters.contrast)"
                  min="0"
                  max="100"
                  class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                />
              </div>
              <div class="flex items-center gap-2">
                <label class="text-xs w-20">Saturation</label>
                <Input
                  type="number"
                  v-model.number="imageFilters.saturation"
                  @input="applyFilter('Saturation', imageFilters.saturation)"
                  min="0"
                  max="100"
                  class="w-24 h-8 text-xs"
                />
                <input
                  type="range"
                  v-model.number="imageFilters.saturation"
                  @input="applyFilter('Saturation', imageFilters.saturation)"
                  min="0"
                  max="100"
                  class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                />
              </div>
              <div class="flex items-center gap-2">
                <label class="text-xs w-20">Grayscale</label>
                <Input
                  type="number"
                  v-model.number="imageFilters.grayscale"
                  @input="applyFilter('Grayscale', imageFilters.grayscale)"
                  min="0"
                  max="100"
                  class="w-24 h-8 text-xs"
                />
                <input
                  type="range"
                  v-model.number="imageFilters.grayscale"
                  @input="applyFilter('Grayscale', imageFilters.grayscale)"
                  min="0"
                  max="100"
                  class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                />
              </div>
              <div class="flex items-center gap-2">
                <label class="text-xs w-20">Sepia</label>
                <Input
                  type="number"
                  v-model.number="imageFilters.sepia"
                  @input="applyFilter('Sepia', imageFilters.sepia)"
                  min="0"
                  max="100"
                  class="w-24 h-8 text-xs"
                />
                <input
                  type="range"
                  v-model.number="imageFilters.sepia"
                  @input="applyFilter('Sepia', imageFilters.sepia)"
                  min="0"
                  max="100"
                  class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                />
              </div>
              <div class="flex items-center gap-2">
                <label class="text-xs w-20">Blur</label>
                <Input
                  type="number"
                  v-model.number="imageFilters.blur"
                  @input="applyFilter('Blur', imageFilters.blur)"
                  min="0"
                  max="20"
                  class="w-24 h-8 text-xs"
                />
                <input
                  type="range"
                  v-model.number="imageFilters.blur"
                  @input="applyFilter('Blur', imageFilters.blur)"
                  min="0"
                  max="20"
                  class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                />
              </div>
              <div class="flex items-center gap-2">
                <label class="text-xs w-20">Pixelate</label>
                <Input
                  type="number"
                  v-model.number="imageFilters.pixelate"
                  @input="applyFilter('Pixelate', imageFilters.pixelate)"
                  min="1"
                  max="20"
                  class="w-24 h-8 text-xs"
                />
                <input
                  type="range"
                  v-model.number="imageFilters.pixelate"
                  @input="applyFilter('Pixelate', imageFilters.pixelate)"
                  min="1"
                  max="20"
                  class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                />
              </div>
              <div class="flex items-center gap-2">
                <label class="text-xs w-20">Noise</label>
                <Input
                  type="number"
                  v-model.number="imageFilters.noise"
                  @input="applyFilter('Noise', imageFilters.noise)"
                  min="0"
                  max="100"
                  class="w-24 h-8 text-xs"
                />
                <input
                  type="range"
                  v-model.number="imageFilters.noise"
                  @input="applyFilter('Noise', imageFilters.noise)"
                  min="0"
                  max="100"
                  class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                />
              </div>
            </div>

            <h4 class="text-sm font-semibold mt-4">Color Adjustments</h4>
            <div class="space-y-2">
              <div class="flex items-center gap-2">
                <input
                  type="checkbox"
                  v-model="imageFilters.invert"
                  @change="applyFilter('Invert', imageFilters.invert)"
                />
                <label class="text-xs">Invert Colors</label>
              </div>
              <div class="flex items-center gap-2">
                <input
                  type="checkbox"
                  v-model="imageFilters.removeWhite.enable"
                  @change="applyFilter('RemoveColor', imageFilters.removeWhite)"
                />
                <label class="text-xs">Remove White</label>
                <input
                  type="color"
                  v-if="imageFilters.removeWhite.enable"
                  v-model="imageFilters.removeWhite.color"
                  @input="applyFilter('RemoveColor', imageFilters.removeWhite)"
                  class="w-16 h-7 p-0 border rounded"
                />
              </div>
              <div class="flex items-center gap-2">
                <input
                  type="checkbox"
                  v-model="imageFilters.blendColor.enable"
                  @change="applyFilter('BlendColor', imageFilters.blendColor)"
                />
                <label class="text-xs">Blend Color</label>
                <input
                  type="color"
                  v-if="imageFilters.blendColor.enable"
                  v-model="imageFilters.blendColor.color"
                  @input="applyFilter('BlendColor', imageFilters.blendColor)"
                  class="w-16 h-7 p-0 border rounded"
                />
                <Select
                  v-if="imageFilters.blendColor.enable"
                  v-model="imageFilters.blendColor.mode"
                  @update:model-value="
                    applyFilter('BlendColor', imageFilters.blendColor)
                  "
                >
                  <SelectTrigger class="w-[100px] h-8 text-xs">
                    <SelectValue placeholder="Mode" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="multiply">Multiply</SelectItem>
                    <SelectItem value="screen">Screen</SelectItem>
                    <SelectItem value="add">Add</SelectItem>
                    <SelectItem value="diff">Difference</SelectItem>
                    <SelectItem value="subtract">Subtract</SelectItem>
                    <SelectItem value="lighten">Lighten</SelectItem>
                    <SelectItem value="darken">Darken</SelectItem>
                    <SelectItem value="overlay">Overlay</SelectItem>
                    <SelectItem value="exclusion">Exclusion</SelectItem>
                    <SelectItem value="tint">Tint</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <Button
              class="h-8 px-4 text-xs mt-4"
              variant="outline"
              @click="resetFilters"
            >
              Reset All Filters
            </Button>
          </div>
        </div>

        <DialogFooter class="mt-4">
          <Button
            class="h-8 px-4 text-xs"
            variant="outline"
            @click="handleImageEditingDialogClose"
          >
            Cancel
          </Button>
          <Button
            class="h-8 px-4 text-xs"
            @click="isImageEditingDialogOpen = false"
          >
            Apply
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <Button class="h-8 w-full px-3 text-xs" size="sm" @click="handleCropImage">
      Crop Image
    </Button>

    <!-- Replace Image -->
    <Button
      class="h-8 w-full px-3 text-xs"
      size="sm"
      @click="handleReplaceImage"
      variant="outline"
    >
      Replace Image
    </Button>
    <input
      type="file"
      ref="fileInput"
      @change="onFileChange"
      class="hidden"
      accept="image/*,image/svg+xml"
    />

    <ImageCropDialog
      :open="isCropDialogOpen"
      :image-src="imageSrcForCrop"
      @update:open="isCropDialogOpen = $event"
      @cropped="handleImageCropped"
    />
  </div>
</template>
