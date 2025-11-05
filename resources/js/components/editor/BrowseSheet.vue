<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { debounce } from 'lodash'
import { VList } from 'virtua/vue'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from '@/components/ui/sheet'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { iconList } from '@/lib/icons'
import { shapeList } from '@/lib/shapes'
import { useEditorStore } from '@/stores/editor'
import { useUnsplash } from '@/lib/unsplash'
import { Skeleton } from '@/components/ui/skeleton'

const editorStore = useEditorStore()

// Icon search state
const query = ref('')
const debouncedQuery = ref('')
const itemsPerRow = 4
const itemHeight = 80

// Image search from our new composable
const { images, loading, error, query: imageQuery, searchImages } = useUnsplash()
const imageScrollContainer = ref<HTMLElement | null>(null)

watch(
  query,
  debounce((val: string) => {
    debouncedQuery.value = val
  }, 300)
)

watch(
  imageQuery,
  debounce(() => {
    searchImages(true)
  }, 500)
)

const filteredIcons = computed(() =>
  iconList.filter((icon) =>
    icon.name.toLowerCase().includes(debouncedQuery.value.toLowerCase())
  )
)

const gridData = computed(() => {
  const chunks = []
  const icons = filteredIcons.value

  for (let i = 0; i < icons.length; i += itemsPerRow) {
    const chunk = icons.slice(i, i + itemsPerRow)
    while (chunk.length < itemsPerRow && i + chunk.length < icons.length) {
      chunk.push({ name: '', component: null } as any)
    }
    chunks.push(chunk.filter(Boolean))
  }

  return chunks
})

function addIconToCanvas(iconName: string) {
  editorStore.addLucideIcon(iconName)
}

function addImageToCanvas(url: string) {
  editorStore.addImage(url)
}

function addShapeToCanvas(url: string) {
  editorStore.addShape(url)
}

const handleScroll = () => {
  const el = imageScrollContainer.value
  if (el) {
    const isAtBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 200 // 200px threshold
    if (isAtBottom && !loading.value) {
      searchImages()
    }
  }
}

onMounted(() => {
  searchImages(true)
})

watch(imageScrollContainer, (newEl) => {
  if (newEl) {
    newEl.addEventListener('scroll', handleScroll)
  }
})

onUnmounted(() => {
  imageScrollContainer.value?.removeEventListener('scroll', handleScroll)
})
</script>

<template>
  <Sheet :modal="false">
    <SheetTrigger as-child>
      <Button class="h-8 px-3" size="sm" variant="outline"> Assets </Button>
    </SheetTrigger>
    <SheetContent side="right" class="w-[300px] sm:w-[400px] p-0 rounded-none h-full flex flex-col">
      <SheetHeader class="p-4 border-b shrink-0">
        <SheetTitle>Browse</SheetTitle>
      </SheetHeader>

      <div ref="imageScrollContainer" class="pt-0 p-4 flex-grow overflow-y-auto">
        <Tabs default-value="icons" class="w-full h-full flex flex-col">
          <TabsList class="grid w-full grid-cols-3 shrink-0">
            <TabsTrigger value="icons">Icons</TabsTrigger>
            <TabsTrigger value="images">Images</TabsTrigger>
            <TabsTrigger value="shapes">Shapes</TabsTrigger>
          </TabsList>

          <TabsContent value="icons" class="mt-4 flex-grow">
            <Input v-model="query" placeholder="Search icons..." class="mb-4" />
            <VList :data="gridData" :item-size="itemHeight" class="h-[calc(100%-52px)]">
              <template #default="{ item }">
                <div class="grid grid-cols-4 gap-2 px-2" :style="{ height: itemHeight + 'px' }">
                  <div v-for="icon in item" :key="icon.name"
                    class="aspect-square p-2 border rounded hover:opacity-60 hover:scale-90 cursor-pointer flex justify-center items-center transition-colors"
                    @click="addIconToCanvas(icon.name)" :title="icon.name">
                    <component :is="icon.component" :size="24" />
                  </div>
                </div>
              </template>
            </VList>
          </TabsContent>

          <TabsContent value="images" class="mt-4 flex-grow flex flex-col">
            <Input v-model="imageQuery" placeholder="Search images..." class="mb-4 shrink-0" />
            <div class="flex-grow">
              <div v-if="error" class="flex flex-col justify-center items-center h-full p-4 text-center">
                <p class="text-sm text-red-600 mb-2">{{ error }}</p>
                <p class="text-xs text-muted-foreground">
                  Add <code class="px-1 py-0.5 bg-muted rounded text-xs">VITE_UNSPLASH_ACCESS_KEY=your_access_key</code>
                  to your
                  .env file
                </p>
                <p class="text-xs text-muted-foreground mt-1">
                  Note: Use your <strong>Access Key</strong> (not Secret Key) for client-side requests
                </p>
              </div>
              <div v-else-if="loading && (!images || images.length === 0)"
                class="flex justify-center items-center h-full">
                <p>Loading images...</p>
              </div>
              <div v-else-if="!images || images.length === 0" class="flex justify-center items-center h-full">
                <p>No images found. Try a different search.</p>
              </div>
              <div v-else class="grid grid-cols-2 gap-4 p-1">
                <div v-for="image in images" :key="image.id"
                  class="aspect-w-1 aspect-h-1 border rounded-lg overflow-hidden cursor-pointer hover:opacity-80 transition-opacity"
                  @click="addImageToCanvas(image.urls.regular)">
                  <img :src="image.urls.thumb" :alt="image.alt_description" class="w-full h-full object-cover" />
                </div>
              </div>
              <div v-if="loading && images && images.length > 0" class="grid grid-cols-2 gap-4 p-1">
                <Skeleton v-for="n in 4" :key="n" class="aspect-w-1 aspect-h-1 rounded-lg" />
              </div>
            </div>
          </TabsContent>

          <TabsContent value="shapes" class="mt-4">
            <div class="grid grid-cols-4 gap-2 p-1">
              <div v-for="shape in shapeList" :key="shape"
                class="aspect-square p-2 border rounded hover:opacity-60 hover:scale-90 cursor-pointer flex justify-center items-center transition-colors"
                @click="addShapeToCanvas(shape)">
                <img :src="shape" class="w-full h-full" />
              </div>
            </div>
          </TabsContent>
        </Tabs>
      </div>
    </SheetContent>
  </Sheet>
</template>
