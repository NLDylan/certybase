<script setup lang="ts">
import { ref, watch } from 'vue'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { ImageIcon } from 'lucide-vue-next'
import { useEditorStore } from '@/stores/editor'
import { toast } from 'vue-sonner'

const editorStore = useEditorStore()
const imageUrl = ref('')
const fileInput = ref<HTMLInputElement | null>(null)
const isDialogOpen = ref(false)
const selectedFile = ref<File | null>(null)
const isUploading = ref(false)

// Watch for the dialog to close and reset the state
watch(isDialogOpen, (isOpen) => {
  if (!isOpen) {
    // Reset state when dialog closes
    selectedFile.value = null
    imageUrl.value = ''
    if (fileInput.value) {
      fileInput.value.value = ''
    }
  }
})

function isValidImageUrl(url: string): boolean {
  const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg']
  const lowerCaseUrl = url.toLowerCase()
  return imageExtensions.some((ext) => lowerCaseUrl.endsWith(ext))
}

function getCsrfToken(): string {
  // Get CSRF token from cookie (Laravel sets XSRF-TOKEN cookie)
  const name = 'XSRF-TOKEN'
  const value = `; ${document.cookie}`
  const parts = value.split(`; ${name}=`)
  if (parts.length === 2) {
    return decodeURIComponent(parts.pop()?.split(';').shift() || '')
  }
  return ''
}

async function handleAddImageFromLink() {
  if (!imageUrl.value) {
    toast.error('Please enter an image URL.')
    return
  }
  if (!isValidImageUrl(imageUrl.value)) {
    toast.error(
      'Invalid image URL. Please provide a direct link to an image (e.g., .jpg, .png, .svg).'
    )
    return
  }

  if (!editorStore.designId) {
    toast.error('Design ID is missing. Please refresh the page.')
    return
  }

  try {
    isUploading.value = true
    const response = await fetch(`/designs/${editorStore.designId}/images/download`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
      },
      credentials: 'same-origin',
      body: JSON.stringify({ url: imageUrl.value }),
    })

    if (!response.ok) {
      let errorMessage = 'Failed to download image'
      try {
        const errorData = await response.json()
        errorMessage = errorData.error || errorData.message || errorMessage
      } catch {
        // If response isn't JSON, use status text
        errorMessage = `${response.status}: ${response.statusText}`
      }
      throw new Error(errorMessage)
    }

    const data = await response.json()
    editorStore.addImage(data.url)
    isDialogOpen.value = false
    toast.success('Image added successfully!')
  } catch (error: any) {
    console.error('Error downloading image:', error)
    toast.error(error.message || 'Failed to download image')
  } finally {
    isUploading.value = false
  }
}

function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    selectedFile.value = target.files[0]
  } else {
    selectedFile.value = null
  }
}

async function handleUploadImage() {
  if (!selectedFile.value) {
    toast.error('Please select an image to upload.')
    return
  }

  if (!editorStore.designId) {
    toast.error('Design ID is missing. Please refresh the page.')
    return
  }

  const file = selectedFile.value

  if (!file.type.startsWith('image/') && file.type !== 'image/svg+xml') {
    toast.error(
      'Invalid file type. Please upload an image (e.g., JPG, PNG, SVG).'
    )
    return
  }

  try {
    isUploading.value = true
    const formData = new FormData()
    formData.append('image', file)

    const response = await fetch(`/designs/${editorStore.designId}/images/upload`, {
      method: 'POST',
      headers: {
        'X-XSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
      },
      credentials: 'same-origin',
      body: formData,
    })

    if (!response.ok) {
      let errorMessage = 'Failed to upload image'
      try {
        const errorData = await response.json()
        errorMessage = errorData.error || errorData.message || errorMessage
      } catch {
        // If response isn't JSON, use status text
        errorMessage = `${response.status}: ${response.statusText}`
      }
      throw new Error(errorMessage)
    }

    const data = await response.json()
    editorStore.addImage(data.url)
    isDialogOpen.value = false
    toast.success('Image uploaded successfully!')
  } catch (error: any) {
    console.error('Error uploading image:', error)
    toast.error(error.message || 'Failed to upload image')
  } finally {
    isUploading.value = false
  }
}
</script>

<template>
  <Popover v-model:open="isDialogOpen">
    <PopoverTrigger as-child>
      <Button class="h-8 w-8 p-0" size="icon" variant="ghost">
        <ImageIcon class="h-4 w-4" />
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-80 p-4 space-y-4 bg-background text-foreground">
      <div class="space-y-1">
        <h3 class="text-base">Add Image</h3>
        <p class="text-xs text-muted-foreground">
          Upload an image or add one from a link.
        </p>
      </div>

      <Tabs default-value="upload" class="w-full">
        <TabsList class="grid w-full grid-cols-2 text-sm">
          <TabsTrigger value="upload">Upload Image</TabsTrigger>
          <TabsTrigger value="link">Image from Link</TabsTrigger>
        </TabsList>
        <div class="space-y-4 pt-4">
          <TabsContent value="upload" class="space-y-4">
            <div class="grid gap-2">
              <label for="picture" class="text-sm font-medium">
                Upload your image
              </label>
              <Input id="picture" type="file" ref="fileInput" class="h-9 text-sm" @change="handleFileChange" />
            </div>
            <Button class="h-9 px-4 text-sm" @click="handleUploadImage" :disabled="isUploading">
              {{ isUploading ? 'Uploading...' : 'Upload' }}
            </Button>
          </TabsContent>

          <TabsContent value="link" class="space-y-4">
            <div class="grid gap-2">
              <label for="image-url" class="text-sm font-medium">
                Image URL
              </label>
              <Input id="image-url" v-model="imageUrl" placeholder="https://example.com/image.jpg"
                class="h-9 text-sm" />
            </div>
            <Button class="h-9 px-4 text-sm" @click="handleAddImageFromLink" :disabled="isUploading">
              {{ isUploading ? 'Downloading...' : 'Add Image' }}
            </Button>
          </TabsContent>
        </div>
      </Tabs>
    </PopoverContent>
  </Popover>
</template>
