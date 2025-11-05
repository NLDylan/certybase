<template>
    <div class="min-h-screen relative bg-zinc-50 dark:bg-background text-foreground overflow-hidden">
        <!-- Back Button -->
        <div class="absolute top-4 left-4 z-20">
            <Button class="h-10 w-10 p-0 rounded-full shadow-md bg-white text-foreground hover:bg-gray-100"
                variant="ghost" @click="router.visit('/designs')">
                <ArrowLeftIcon class="h-5 w-5" />
            </Button>
        </div>

        <!-- User Profile + Autosave Status -->
        <div class="absolute top-4 right-4 z-20 flex items-center gap-3">
            <div
                class="rounded-full bg-white text-foreground border border-border shadow-sm px-3 h-10 inline-flex items-center text-xs">
                <span v-if="editorStore.isSaving" class="inline-flex items-center gap-1 text-muted-foreground">
                    <Loader2Icon class="h-3.5 w-3.5 animate-spin" /> Saving...
                </span>
                <span v-else-if="editorStore.saveError" class="inline-flex items-center gap-1 text-red-600">
                    Save failed
                </span>
                <span v-else class="inline-flex items-center gap-1 text-muted-foreground">
                    <CheckIcon class="h-3.5 w-3.5 text-emerald-600" />
                    Saved
                    <span v-if="editorStore.lastSavedAt">· {{ new Date(editorStore.lastSavedAt).toLocaleTimeString()
                        }}</span>
                </span>
            </div>
            <UserMenu />
        </div>

        <!-- Top Notch Floating Bar -->
        <div class="absolute top-4 left-1/2 transform -translate-x-1/2 z-20">
            <div
                class="bg-muted rounded-full px-2 py-1 flex items-center space-x-1 shadow-md border border-border h-12">
                <!-- Add Text -->
                <Button class="h-8 w-8 p-0" size="icon" variant="ghost" @click="editorStore.addText()">
                    <TypeIcon class="h-4 w-4" />
                </Button>

                <!-- Add Image -->
                <ImageUploadDialog />

                <!-- Shapes Popover -->
                <Popover>
                    <PopoverTrigger as-child>
                        <Button class="h-8 w-8 p-0" size="icon" variant="ghost">
                            <ShapesIcon class="h-4 w-4" />
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent class="w-40 flex-wrap p-1.5 flex gap-2">
                        <Button variant="ghost" class="w-fit px-2 py-1.5 text-xs flex items-center gap-2 justify-start"
                            @click="editorStore.addRectangle()">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                                <rect x="4" y="4" width="16" height="16" />
                            </svg>
                        </Button>
                        <Button variant="ghost" class="w-fit px-2 py-1.5 text-xs flex items-center gap-2 justify-start"
                            @click="editorStore.addCircle()">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                                <circle cx="12" cy="12" r="8" />
                            </svg>
                        </Button>
                        <Button variant="ghost" class="w-fit px-2 py-1.5 text-xs flex items-center gap-2 justify-start"
                            @click="editorStore.addEllipse()">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                                <ellipse cx="12" cy="12" rx="9" ry="6" />
                            </svg>
                        </Button>
                        <Button variant="ghost" class="w-fit px-2 py-1.5 text-xs flex items-center gap-2 justify-start"
                            @click="editorStore.addTriangle()">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                                <polygon points="12,4 4,20 20,20" />
                            </svg>
                        </Button>
                        <Button variant="ghost" class="w-fit px-2 py-1.5 text-xs flex items-center gap-2 justify-start"
                            @click="editorStore.addLine()">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                                <line x1="4" y1="20" x2="20" y2="4" stroke="currentColor" stroke-width="2" />
                            </svg>
                        </Button>
                    </PopoverContent>
                </Popover>

                <!-- Arrange Popover -->
                <Popover>
                    <PopoverTrigger as-child>
                        <Button class="h-8 w-8 p-0" size="icon" variant="ghost">
                            <LayersIcon class="h-4 w-4" />
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent class="w-48 p-1.5 space-y-1">
                        <Button variant="ghost" class="w-full px-2 py-1.5 text-xs flex items-center gap-2 justify-start"
                            @click="editorStore.bringToFront()">
                            Bring to Front
                        </Button>
                        <Button variant="ghost" class="w-full px-2 py-1.5 text-xs flex items-center gap-2 justify-start"
                            @click="editorStore.sendToBack()">
                            Send to Back
                        </Button>
                        <Button variant="ghost" class="w-full px-2 py-1.5 text-xs flex items-center gap-2 justify-start"
                            @click="editorStore.bringForward()">
                            Bring Forward
                        </Button>
                        <Button variant="ghost" class="w-full px-2 py-1.5 text-xs flex items-center gap-2 justify-start"
                            @click="editorStore.sendBackward()">
                            Send Backward
                        </Button>
                    </PopoverContent>
                </Popover>

                <!-- Style Popover -->
                <Popover>
                    <PopoverTrigger as-child>
                        <Button class="h-8 w-8 p-0" size="icon" variant="ghost">
                            <PaintbrushIcon class="h-4 w-4" />
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent class="w-56 p-3 space-y-2">
                        <div class="space-y-1">
                            <label class="text-xs">Fill Color</label>
                            <input type="color" class="w-full h-7 p-0 border rounded" @input="
                                editorStore.setCanvasColor(
                                    ($event.target as HTMLInputElement).value
                                )
                                " />
                        </div>
                    </PopoverContent>
                </Popover>
                <!-- Browse Shapes Sheet -->
                <BrowseSheet />

                <!-- Variable Popover -->
                <Popover>
                    <PopoverTrigger as-child>
                        <Button class="h-8 px-3" size="sm" variant="outline">
                            Variable
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent class="w-64 p-0">
                        <VariableToolbar @apply="applyVariablesWrapper" />
                    </PopoverContent>
                </Popover>
                <!-- Context-specific Toolbars -->
                <CommonToolbar v-if="editorStore.selectedObject" />

                <!-- Download Button -->
                <Button class="h-8 w-8 p-0" size="icon" variant="ghost" @click="editorStore.undo()"
                    :disabled="!editorStore.canUndo">
                    <UndoIcon class="h-4 w-4" />
                </Button>
                <Button class="h-8 w-8 p-0" size="icon" variant="ghost" @click="editorStore.redo()"
                    :disabled="!editorStore.canRedo">
                    <RedoIcon class="h-4 w-4" />
                </Button>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button class="h-8 pr-2 pl-3" size="sm" variant="default">
                            Download
                            <ChevronDownIcon class="w-4 h-4 ml-2" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent class="w-48 p-1.5 space-y-1">
                        <DropdownMenuItem @click="editorStore.downloadCanvas('json')">
                            JSON
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="editorStore.downloadCanvas('png')">
                            PNG
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="editorStore.downloadCanvas('jpeg')">
                            JPEG
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="editorStore.downloadCanvas('svg')">
                            SVG
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <!-- Delete Button -->
                <Button v-if="editorStore.selectedObject" class="h-8 w-8 p-0" size="icon" variant="destructive"
                    @click="editorStore.deleteSelectedObject()">
                    <Trash2Icon class="h-4 w-4" />
                </Button>

            </div>
        </div>

        <!-- Canvas -->
        <main
            class="flex-1 container max-w-full mx-auto h-full w-full py-24 flex justify-center items-center overflow-hidden"
            @wheel.prevent="handleMouseWheel">
            <div class="flex justify-center items-center" :style="{
                width: canvasWidth + 'px',
                height: canvasHeight + 'px',
                transform: `scale(${zoomLevel})`,
                transformOrigin: 'center center',
            }">
                <div class="w-fit h-fit bg-white border border-border rounded-md">
                    <canvas id="canvas"></canvas>
                </div>
            </div>
        </main>

        <!-- Zoom Controls -->
        <div class="absolute bottom-4 left-4 z-20 flex items-center space-x-1">
            <Button class="h-10 w-10 p-0 rounded-full shadow-md bg-white text-foreground hover:bg-gray-100"
                variant="secondary" @click="zoomOut">
                <ZoomOutIcon class="h-4 w-4" />
            </Button>
            <Button class="h-10 px-3 rounded-full shadow-md text-sm bg-white text-foreground hover:bg-gray-100"
                variant="secondary" @click="resetZoom">
                {{ Math.round(zoomLevel * 100) }}%
            </Button>
            <Button class="h-10 w-10 p-0 rounded-full shadow-md bg-white text-foreground hover:bg-gray-100"
                variant="secondary" @click="zoomIn">
                <ZoomInIcon class="h-4 w-4" />
            </Button>
        </div>

        <!-- Keyboard Shortcuts -->
        <div class="absolute bottom-4 right-4 z-20">
            <Popover>
                <PopoverTrigger as-child>
                    <Button class="h-10 w-10 p-0 rounded-full shadow-md bg-white text-foreground hover:bg-gray-100"
                        variant="secondary">
                        <KeyboardIcon class="h-5 w-5" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent class="w-64 p-3">
                    <div class="grid gap-3">
                        <div class="space-y-1">
                            <h4 class="font-medium leading-none text-sm">
                                Keyboard Shortcuts
                            </h4>
                            <p class="text-xs text-muted-foreground">
                                Boost your productivity with these shortcuts.
                            </p>
                        </div>
                        <div class="grid gap-2 text-xs">
                            <div class="grid grid-cols-[1fr_auto] items-center gap-4">
                                <span class="text-muted-foreground">Undo</span>
                                <kbd
                                    class="px-1.5 py-0.5 text-xs font-semibold text-muted-foreground bg-muted rounded-md">Ctrl
                                    + Z</kbd>
                            </div>
                            <div class="grid grid-cols-[1fr_auto] items-center gap-4">
                                <span class="text-muted-foreground">Redo</span>
                                <kbd
                                    class="px-1.5 py-0.5 text-xs font-semibold text-muted-foreground bg-muted rounded-md">Ctrl
                                    + Y</kbd>
                            </div>
                            <div class="grid grid-cols-[1fr_auto] items-center gap-4">
                                <span class="text-muted-foreground">Redo (Alternative)</span>
                                <kbd
                                    class="px-1.5 py-0.5 text-xs font-semibold text-muted-foreground bg-muted rounded-md">Ctrl
                                    + Shift + Z</kbd>
                            </div>
                            <div class="grid grid-cols-[1fr_auto] items-center gap-4">
                                <span class="text-muted-foreground">Duplicate Object</span>
                                <kbd
                                    class="px-1.5 py-0.5 text-xs font-semibold text-muted-foreground bg-muted rounded-md">Ctrl
                                    + D</kbd>
                            </div>
                            <div class="grid grid-cols-[1fr_auto] items-center gap-4">
                                <span class="text-muted-foreground">Delete Object</span>
                                <kbd
                                    class="px-1.5 py-0.5 text-xs font-semibold text-muted-foreground bg-muted rounded-md">Delete
                                    / Backspace</kbd>
                            </div>
                            <div class="grid grid-cols-[1fr_auto] items-center gap-4">
                                <span class="text-muted-foreground">Deselect All</span>
                                <kbd
                                    class="px-1.5 py-0.5 text-xs font-semibold text-muted-foreground bg-muted rounded-md">Escape</kbd>
                            </div>
                            <div class="grid grid-cols-[1fr_auto] items-center gap-4">
                                <span class="text-muted-foreground">Select All</span>
                                <kbd
                                    class="px-1.5 py-0.5 text-xs font-semibold text-muted-foreground bg-muted rounded-md">Ctrl
                                    + A</kbd>
                            </div>
                            <div class="grid grid-cols-[1fr_auto] items-center gap-4">
                                <span class="text-muted-foreground">Move Up</span>
                                <kbd
                                    class="px-1.5 py-0.5 text-xs font-semibold text-muted-foreground bg-muted rounded-md">↑</kbd>
                            </div>
                            <div class="grid grid-cols-[1fr_auto] items-center gap-4">
                                <span class="text-muted-foreground">Move Down</span>
                                <kbd
                                    class="px-1.5 py-0.5 text-xs font-semibold text-muted-foreground bg-muted rounded-md">↓</kbd>
                            </div>
                            <div class="grid grid-cols-[1fr_auto] items-center gap-4">
                                <span class="text-muted-foreground">Move Left</span>
                                <kbd
                                    class="px-1.5 py-0.5 text-xs font-semibold text-muted-foreground bg-muted rounded-md">←</kbd>
                            </div>
                            <div class="grid grid-cols-[1fr_auto] items-center gap-4">
                                <span class="text-muted-foreground">Move Right</span>
                                <kbd
                                    class="px-1.5 py-0.5 text-xs font-semibold text-muted-foreground bg-muted rounded-md">→</kbd>
                            </div>
                        </div>
                    </div>
                </PopoverContent>
            </Popover>
        </div>
        <TextEditingTools />
        <ShapeEditingTools />
        <ImageEditingTools />
    </div>
</template>

<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import * as fabric from 'fabric'

interface Design {
    id: string
    name: string
    description?: string | null
    design_data?: any
    variables?: string[]
    settings?: any
    status: string
    organization_id: string
    created_at: string
    updated_at: string
}

interface Props {
    design: Design
}

const props = defineProps<Props>()
import { Button } from '@/components/ui/button'
import {
    Popover,
    PopoverTrigger,
    PopoverContent,
} from '@/components/ui/popover'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { useEditorStore } from '@/stores/editor'
import VariableToolbar from '@/components/editor/VariableToolbar.vue'
import UserMenu from '@/components/editor/UserMenu.vue'
import BrowseSheet from '@/components/editor/BrowseSheet.vue'

// Icons
import {
    TypeIcon,
    ShapesIcon,
    LayersIcon,
    PaintbrushIcon,
    Trash2Icon,
    ChevronDownIcon,
    KeyboardIcon,
    ZoomInIcon,
    ZoomOutIcon,
    ArrowLeftIcon,
} from 'lucide-vue-next'
import ImageUploadDialog from '@/components/editor/ImageUploadDialog.vue'
import CommonToolbar from '@/components/editor/CommonToolbar.vue'
import TextEditingTools from '@/components/editor/TextEditingTools.vue'
import ShapeEditingTools from '@/components/editor/ShapeEditingTools.vue'
import ImageEditingTools from '@/components/editor/ImageEditingTools.vue'
import { FabricObject } from 'fabric'
import {
    scanForVariables,
    applyVariables,
    handleEditingExited,
} from '@/lib/variables'
import { useVariableStore } from '@/stores/variables'
import { UndoIcon, RedoIcon, Loader2Icon, CheckIcon } from 'lucide-vue-next'
import { AlignGuidelines } from 'fabric-guideline-plugin'
import { setupKeybindings } from '@/lib/keybindings'

const canvasWidth = 1123 / 1.5
const canvasHeight = 794 / 1.5
let canvas: fabric.Canvas
const editorStore = useEditorStore()
const zoomLevel = ref(1)
let cleanupKeybindings: () => void

const zoomIn = () => {
    zoomLevel.value = Math.min(zoomLevel.value + 0.01, 3) // Max zoom 300%
    setCanvasZoom()
}

const zoomOut = () => {
    zoomLevel.value = Math.max(zoomLevel.value - 0.01, 0.5) // Min zoom 50%
    setCanvasZoom()
}

const resetZoom = () => {
    zoomLevel.value = 1
    setCanvasZoom()
}

const setCanvasZoom = () => {
    if (canvas) {
        canvas.setZoom(zoomLevel.value)
        canvas.setWidth(canvasWidth * zoomLevel.value)
        canvas.setHeight(canvasHeight * zoomLevel.value)
        canvas.renderAll()
    }
}

const debounce = (func: Function, delay: number) => {
    let timeoutId: ReturnType<typeof setTimeout>
    return (...args: any[]) => {
        clearTimeout(timeoutId)
        timeoutId = setTimeout(() => {
            func(...args)
        }, delay)
    }
}

const debouncedUpdate = debounce(() => {
    scanForVariables(canvas)
    editorStore.updateCanvasData()
}, 500)

const applyVariablesWrapper = () => {
    applyVariables(canvas)
}

const debouncedZoom = debounce((event: WheelEvent) => {
    if (event.ctrlKey || event.metaKey) {
        if (event.deltaY < 0) {
            zoomIn()
        } else {
            zoomOut()
        }
    }
}, 15)

const handleMouseWheel = (event: WheelEvent) => {
    debouncedZoom(event)
}

onMounted(async () => {
    // Use design ID from props instead of parsing URL
    const designId = props.design.id
    if (!designId) {
        console.error('Design ID not found in props')
        return
    }
    editorStore.setDesignId(designId)

    Object.assign(FabricObject.ownDefaults, {
        transparentCorners: false,
        cornerColor: '#007ACC',
        cornerStrokeColor: '',
        borderColor: '#007ACC',
        cornerStyle: 'circle',
        cornerSize: 10,
        borderScaleFactor: 1,
    })

    canvas = new fabric.Canvas('canvas', {
        width: canvasWidth,
        height: canvasHeight,
        backgroundColor: '#ffffff',
        selection: true,
    })

    const guideline = new AlignGuidelines({
        canvas: canvas,
        aligningOptions: {
            lineColor: '#32D10A',
            lineWidth: 2,
            lineMargin: 2,
        },
    })

    guideline.init()

    canvas.preserveObjectStacking = true
    editorStore.setCanvas(canvas)
    setCanvasZoom() // Set initial zoom

    const setupEventListeners = () => {
        canvas.off('object:modified', debouncedUpdate)
        canvas.off('object:added', debouncedUpdate)
        canvas.off('object:removed', debouncedUpdate)
        // Cast to any to bypass TypeScript error if 'editing:exited' is not officially in CanvasEvents
        canvas.off('editing:exited' as any, (options) =>
            handleEditingExited(options, canvas)
        )

        canvas.on('object:modified', debouncedUpdate)
        canvas.on('object:added', debouncedUpdate)
        canvas.on('object:removed', debouncedUpdate)
        // Cast to any to bypass TypeScript error if 'editing:exited' is not officially in CanvasEvents
        canvas.on('editing:exited' as any, (options) =>
            handleEditingExited(options, canvas)
        )
        console.log('Fabric event listeners have been set up.')
    }

    setupEventListeners()

    // Load design data from props
    const designData = props.design.design_data
    const variables = props.design.variables || []

    // Initialize variables store with detected variables
    if (variables.length > 0) {
        const variableStore = useVariableStore()
        variableStore.setDetectedVariables(variables)
    }

    if (designData) {
        canvas.loadFromJSON(designData, () => {
            canvas.getObjects().forEach((obj) => obj.setCoords())
            canvas.requestRenderAll()
            console.log('Canvas loaded from design data')
            scanForVariables(canvas)
            applyVariables(canvas) // Apply stored values on load
        })
    } else {
        console.log('No design data found, starting with empty canvas')
        scanForVariables(canvas) // Also scan for variables on a fresh canvas
    }

    cleanupKeybindings = setupKeybindings(editorStore, canvas)
})

onBeforeUnmount(() => {
    if (cleanupKeybindings) {
        cleanupKeybindings()
    }
})
</script>