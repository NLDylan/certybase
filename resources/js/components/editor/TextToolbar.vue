<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useEditorStore } from '@/stores/editor'
import * as fabric from 'fabric'
import WebFont from 'webfontloader'
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  AlignLeftIcon,
  AlignCenterIcon,
  AlignRightIcon,
  AlignJustifyIcon,
  CaseSensitiveIcon,
  ItalicIcon,
  UnderlineIcon,
  StrikethroughIcon,
  CaseUpperIcon,
  CaseLowerIcon,
} from 'lucide-vue-next'
import { googleFonts } from '@/lib/fonts'

const editorStore = useEditorStore()

const isTextSelected = computed(() => {
  return (
    editorStore.selectedObject && editorStore.selectedObject.type === 'i-text'
  )
})

const currentFontFamily = ref('Arial')
const currentFontSize = ref(16)
const currentFontWeight = ref('400')
const currentFontStyle = ref('normal')
const currentTextDecoration = ref('')
const currentTextAlign = ref('left')
const currentLineHeight = ref(1.2)
const currentCharSpacing = ref(0)
const currentTextColor = ref('#000000')
const currentTextBackgroundColor = ref('')

const systemFonts = [
  { name: 'Arial', category: 'Sans-serif' },
  { name: 'Times New Roman', category: 'Serif' },
  { name: 'Courier New', category: 'Monospace' },
  { name: 'Georgia', category: 'Serif' },
  { name: 'Verdana', category: 'Sans-serif' },
  { name: 'Impact', category: 'Display' },
  { name: 'Brush Script MT', category: 'Handwriting' },
]

const fonts = [...systemFonts, ...googleFonts]
const loadedFonts = new Set<string>()

function loadAndApplyFont(fontFamily: string) {
  currentFontFamily.value = fontFamily

  const isSystemFont = systemFonts.some((f) => f.name === fontFamily)
  if (isSystemFont) {
    editorStore.setTextFontFamily(fontFamily)
    editorStore.canvas?.renderAll()
    return
  }

  const applyFontToCanvas = () => {
    editorStore.setTextFontFamily(fontFamily)
    setTimeout(() => {
      editorStore.canvas?.renderAll()
    }, 50)
  }

  if (loadedFonts.has(fontFamily)) {
    applyFontToCanvas()
    return
  }

  WebFont.load({
    google: {
      families: [fontFamily],
    },
    active: () => {
      loadedFonts.add(fontFamily)
      applyFontToCanvas()
    },
    inactive: () => {
      console.warn(`Failed to load font: ${fontFamily}`)
    },
  })
}

watch(
  () => editorStore.selectedObject,
  (newVal) => {
    if (newVal && newVal.type === 'i-text') {
      const textObject = newVal as fabric.IText
      const fontFamily = textObject.fontFamily || 'Arial'

      currentFontFamily.value = fontFamily
      currentFontSize.value = textObject.fontSize || 16
      currentFontWeight.value = textObject.fontWeight?.toString() || '400'
      currentFontStyle.value = textObject.fontStyle || 'normal'
      const decorations: string[] = []
      if (textObject.underline) decorations.push('underline')
      if (textObject.linethrough) decorations.push('line-through')
      if (textObject.overline) decorations.push('overline')
      currentTextDecoration.value = decorations.join(' ')

      currentTextAlign.value = textObject.textAlign || 'left'
      currentLineHeight.value = textObject.lineHeight || 1.2
      currentCharSpacing.value = textObject.charSpacing || 0
      currentTextColor.value = textObject.fill?.toString() || '#000000'
      currentTextBackgroundColor.value = textObject.textBackgroundColor || ''

      loadAndApplyFont(fontFamily)
    }
  },
  { immediate: true }
)

watch(currentLineHeight, (newVal) => {
  editorStore.setTextLineHeight(newVal)
})

watch(currentCharSpacing, (newVal) => {
  editorStore.setTextCharSpacing(newVal)
})

function applyFontSize(size: number) {
  editorStore.setTextFontSize(size)
  currentFontSize.value = size
}

function applyFontWeight(weight: string) {
  editorStore.setTextFontWeight(weight)
  currentFontWeight.value = weight
}

function toggleFontStyle(style: 'normal' | 'italic') {
  editorStore.toggleTextFontStyle(style)
  if (editorStore.selectedObject?.type === 'i-text') {
    currentFontStyle.value =
      (editorStore.selectedObject as fabric.IText).fontStyle || 'normal'
  }
}

function toggleTextDecoration(decoration: 'underline' | 'line-through') {
  editorStore.toggleTextDecoration(decoration)
  if (editorStore.selectedObject?.type === 'i-text') {
    const textObject = editorStore.selectedObject as fabric.IText
    const decorations: string[] = []
    if (textObject.underline) decorations.push('underline')
    if (textObject.linethrough) decorations.push('line-through')
    if (textObject.overline) decorations.push('overline')
    currentTextDecoration.value = decorations.join(' ')
  }
}

function applyTextAlign(textAlign: 'left' | 'center' | 'right' | 'justify') {
  editorStore.setTextAlign(textAlign)
  currentTextAlign.value = textAlign
}

function applyTextColor(color: string) {
  editorStore.setTextColor(color)
  currentTextColor.value = color
}

function applyTextBackgroundColor(backgroundColor: string) {
  editorStore.setTextBackgroundColor(backgroundColor)
  currentTextBackgroundColor.value = backgroundColor
}

function capitalizeText() {
  editorStore.capitalizeSelectedText()
}

function uppercaseText() {
  editorStore.uppercaseSelectedText()
}

function lowercaseText() {
  editorStore.lowercaseSelectedText()
}
</script>

<template>
  <div v-if="isTextSelected" class="flex flex-col space-y-2 p-2">
    <!-- Font Family -->
    <div class="space-y-1">
      <Label class="text-xs">Font Family</Label>
      <Select
        :model-value="currentFontFamily"
        @update:model-value="loadAndApplyFont($event as string)"
      >
        <SelectTrigger class="w-full h-8 text-xs">
          <SelectValue placeholder="Select a font" />
        </SelectTrigger>
        <SelectContent>
          <SelectGroup>
            <SelectItem
              v-for="font in fonts"
              :key="font.name"
              :value="font.name"
            >
              <span :style="{ fontFamily: font.name }">{{ font.name }}</span>
            </SelectItem>
          </SelectGroup>
        </SelectContent>
      </Select>
    </div>

    <!-- Font Size -->
    <div class="space-y-1">
      <Label class="text-xs">Font Size</Label>
      <div class="flex items-center">
        <Input
          type="number"
          :model-value="currentFontSize"
          @update:model-value="applyFontSize(Number($event))"
          min="1"
          max="500"
          class="w-16 h-8 text-xs rounded-r-none focus:z-10"
        />
        <Select
          :model-value="currentFontSize.toString()"
          @update:model-value="applyFontSize(Number($event))"
        >
          <SelectTrigger class="w-12 h-8 rounded-l-none -ml-px">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem
              v-for="size in [8, 12, 16, 24, 32, 48, 64, 96, 128]"
              :key="size"
              :value="size.toString()"
            >
              {{ size }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>
    </div>

    <!-- Font Weight -->
    <div class="space-y-1">
      <Label class="text-xs">Font Weight</Label>
      <Select
        v-model="currentFontWeight"
        @update:model-value="applyFontWeight($event as string)"
      >
        <SelectTrigger class="w-full h-8 text-xs">
          <SelectValue placeholder="Weight" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="100">Thin</SelectItem>
          <SelectItem value="200">Extra Light</SelectItem>
          <SelectItem value="300">Light</SelectItem>
          <SelectItem value="400">Normal</SelectItem>
          <SelectItem value="500">Medium</SelectItem>
          <SelectItem value="600">Semi Bold</SelectItem>
          <SelectItem value="700">Bold</SelectItem>
          <SelectItem value="800">Extra Bold</SelectItem>
          <SelectItem value="900">Black</SelectItem>
        </SelectContent>
      </Select>
    </div>

    <!-- Text Color -->
    <div class="space-y-1">
      <Label class="text-xs">Text Color</Label>
      <Input
        type="color"
        class="w-full h-8 p-1"
        :model-value="currentTextColor"
        @update:model-value="applyTextColor($event as string)"
      />
    </div>

    <!-- Text Background -->
    <div class="space-y-1">
      <Label class="text-xs">Text Background</Label>
      <Input
        type="color"
        class="w-full h-8 p-1"
        :model-value="currentTextBackgroundColor"
        @update:model-value="applyTextBackgroundColor($event as string)"
      />
    </div>

    <!-- Line Height -->
    <div class="space-y-1">
      <Label class="text-xs">Line Height</Label>
      <div class="flex items-center space-x-2">
        <Input
          type="number"
          v-model.number="currentLineHeight"
          min="0.5"
          max="3.0"
          step="0.1"
          class="w-20 h-8 text-xs"
        />
        <input
          type="range"
          v-model.number="currentLineHeight"
          min="0.5"
          max="3.0"
          step="0.1"
          class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
        />
      </div>
    </div>

    <!-- Letter Spacing -->
    <div class="space-y-1">
      <Label class="text-xs">Letter Spacing</Label>
      <div class="flex items-center space-x-2">
        <Input
          type="number"
          v-model.number="currentCharSpacing"
          min="-100"
          max="200"
          class="w-20 h-8 text-xs"
        />
        <input
          type="range"
          v-model.number="currentCharSpacing"
          min="-100"
          max="200"
          class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
        />
      </div>
    </div>

    <!-- Font Style & Decoration -->
    <div class="space-y-1">
      <Label class="text-xs">Style</Label>
      <div class="flex items-center space-x-1">
        <Button
          class="h-8 w-8 p-0"
          size="icon"
          :variant="currentFontStyle === 'italic' ? 'secondary' : 'ghost'"
          @click="toggleFontStyle('italic')"
        >
          <ItalicIcon class="h-4 w-4" />
        </Button>
        <Button
          class="h-8 w-8 p-0"
          size="icon"
          :variant="
            currentTextDecoration.includes('underline') ? 'secondary' : 'ghost'
          "
          @click="toggleTextDecoration('underline')"
        >
          <UnderlineIcon class="h-4 w-4" />
        </Button>
        <Button
          class="h-8 w-8 p-0"
          size="icon"
          :variant="
            currentTextDecoration.includes('line-through')
              ? 'secondary'
              : 'ghost'
          "
          @click="toggleTextDecoration('line-through')"
        >
          <StrikethroughIcon class="h-4 w-4" />
        </Button>
      </div>
    </div>

    <!-- Text Align -->
    <div class="space-y-1">
      <Label class="text-xs">Alignment</Label>
      <div class="flex items-center space-x-1">
        <Button
          class="h-8 w-8 p-0"
          size="icon"
          :variant="currentTextAlign === 'left' ? 'secondary' : 'ghost'"
          @click="applyTextAlign('left')"
        >
          <AlignLeftIcon class="h-4 w-4" />
        </Button>
        <Button
          class="h-8 w-8 p-0"
          size="icon"
          :variant="currentTextAlign === 'center' ? 'secondary' : 'ghost'"
          @click="applyTextAlign('center')"
        >
          <AlignCenterIcon class="h-4 w-4" />
        </Button>
        <Button
          class="h-8 w-8 p-0"
          size="icon"
          :variant="currentTextAlign === 'right' ? 'secondary' : 'ghost'"
          @click="applyTextAlign('right')"
        >
          <AlignRightIcon class="h-4 w-4" />
        </Button>
        <Button
          class="h-8 w-8 p-0"
          size="icon"
          :variant="currentTextAlign === 'justify' ? 'secondary' : 'ghost'"
          @click="applyTextAlign('justify')"
        >
          <AlignJustifyIcon class="h-4 w-4" />
        </Button>
      </div>
    </div>

    <!-- Text Case -->
    <div class="space-y-1">
      <Label class="text-xs">Text Case</Label>
      <div class="flex items-center space-x-1">
        <Button
          class="h-8 w-8 p-0"
          size="icon"
          variant="ghost"
          @click="capitalizeText"
        >
          <CaseSensitiveIcon class="h-4 w-4" />
        </Button>
        <Button
          class="h-8 w-8 p-0"
          size="icon"
          variant="ghost"
          @click="uppercaseText"
        >
          <CaseUpperIcon class="h-4 w-4" />
        </Button>
        <Button
          class="h-8 w-8 p-0"
          size="icon"
          variant="ghost"
          @click="lowercaseText"
        >
          <CaseLowerIcon class="h-4 w-4" />
        </Button>
      </div>
    </div>
  </div>
</template>
