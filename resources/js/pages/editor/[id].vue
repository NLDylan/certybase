<template>
    <div class="min-h-screen relative bg-background text-foreground overflow-hidden">
        <!-- Back Button -->
        <div class="absolute top-4 left-4 z-20">
            <Button class="h-10 w-10 p-0 rounded-full shadow-md bg-muted text-muted-foreground" variant="ghost"
                @click="router.push('/')">
                <ArrowLeftIcon class="h-5 w-5" />
            </Button>
        </div>

        <!-- User Profile Dropdown -->
        <div class="absolute top-4 right-4 z-20">
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
                <div class="w-fit h-fit bg-white">
                    <canvas id="canvas"></canvas>
                </div>
            </div>
        </main>

        <!-- Zoom Controls -->
        <div class="absolute bottom-4 left-4 z-20 flex items-center space-x-1">
            <Button class="h-10 w-10 p-0 rounded-full shadow-md bg-muted text-muted-foreground" variant="secondary"
                @click="zoomOut">
                <ZoomOutIcon class="h-4 w-4" />
            </Button>
            <Button class="h-10 px-3 rounded-full shadow-md text-sm bg-muted text-muted-foreground" variant="secondary"
                @click="resetZoom">
                {{ Math.round(zoomLevel * 100) }}%
            </Button>
            <Button class="h-10 w-10 p-0 rounded-full shadow-md bg-muted text-muted-foreground" variant="secondary"
                @click="zoomIn">
                <ZoomInIcon class="h-4 w-4" />
            </Button>
        </div>

        <!-- Keyboard Shortcuts -->
        <div class="absolute bottom-4 right-4 z-20">
            <Popover>
                <PopoverTrigger as-child>
                    <Button class="h-10 w-10 p-0 rounded-full shadow-md bg-muted text-muted-foreground"
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
import { useRoute, useRouter } from 'vue-router'
import * as fabric from 'fabric'
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
import VariableToolbar from '@/components/VariableToolbar.vue'
import UserMenu from '@/components/UserMenu.vue'
import BrowseSheet from '@/components/BrowseSheet.vue'

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
import ImageUploadDialog from '@/components/ImageUploadDialog.vue'
import CommonToolbar from '@/components/CommonToolbar.vue'
import TextEditingTools from '@/components/TextEditingTools.vue'
import ShapeEditingTools from '@/components/ShapeEditingTools.vue'
import ImageEditingTools from '@/components/ImageEditingTools.vue'
import { FabricObject } from 'fabric'
import { db } from '@/lib/db'
import { AlignGuidelines } from 'fabric-guideline-plugin'
import { setupKeybindings } from '@/lib/keybindings'
import {
    scanForVariables,
    applyVariables,
    handleEditingExited,
} from '@/lib/variables'
import { UndoIcon, RedoIcon } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
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
    const projectId = route.params.nano_id as string
    if (!projectId) {
        console.error('Project ID not found in route')
        return
    }
    editorStore.setProjectId(projectId)

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

    const project = await db.projects.get(projectId)
    console.log('Got the project id', projectId)
    console.log('Got the project', project)

    if (project && project.canvasData) {
        canvas.loadFromJSON(project.canvasData, () => {
            canvas.getObjects().forEach((obj) => obj.setCoords())
            canvas.requestRenderAll()
            console.log('Canvas loaded from DB, setting up event listeners.')
            scanForVariables(canvas)
            applyVariables(canvas) // Apply stored values on load
            // setupEventListeners()
        })
    } else {
        console.log('No canvas data found, setting up event listeners.')
        scanForVariables(canvas) // Also scan for variables on a fresh canvas
        // setupEventListeners()
    }

    cleanupKeybindings = setupKeybindings(editorStore, canvas)
})

onBeforeUnmount(() => {
    if (cleanupKeybindings) {
        cleanupKeybindings()
    }
})
</script>