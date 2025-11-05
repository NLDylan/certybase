import { defineStore } from 'pinia'
import { markRaw, createApp, h } from 'vue'
import * as fabric from 'fabric'
import { router } from '@inertiajs/vue3'
import CanvasHistory from '@/lib/CanvasHistory'
import { useVariableStore } from './variables'
import { scanForVariables } from '@/lib/variables'

interface EditorState {
  canvas: fabric.Canvas | null
  selectedObject: fabric.Object | null
  designId: string | null
  downloadFormat: string
  canvasHistory: CanvasHistory | null
  canUndo: boolean
  canRedo: boolean
  isSaving: boolean
  lastSavedAt: Date | null
  saveError: string | null
}

export const useEditorStore = defineStore('editor', {
  state: (): EditorState => ({
    canvas: null,
    selectedObject: null,
    designId: null,
    downloadFormat: 'json',
    canvasHistory: null,
    canUndo: false,
    canRedo: false,
    isSaving: false,
    lastSavedAt: null,
    saveError: null,
  }),
  actions: {
    setCanvas(canvasInstance: fabric.Canvas) {
      this.canvas = canvasInstance
      this.canvas.on('selection:created', this.updateSelectedObject)
      this.canvas.on('selection:updated', this.updateSelectedObject)
      this.canvas.on('selection:cleared', this.clearSelectedObject)
      this.canvasHistory = new CanvasHistory(
        canvasInstance,
        this.updateHistoryStatus
      )
      this.updateHistoryStatus()
    },

    updateHistoryStatus() {
      if (this.canvasHistory) {
        this.canUndo = this.canvasHistory.canUndo()
        this.canRedo = this.canvasHistory.canRedo()
      }
    },

    setDesignId(id: string) {
      this.designId = id
    },

    async updateCanvasData() {
      if (!this.canvas || !this.designId) {
        return
      }

      if (this.isSaving) {
        return // Prevent concurrent saves
      }

      try {
        this.isSaving = true
        this.saveError = null

        // Extract canvas data
        const canvasData = this.canvas.toObject([
          'id',
          'name',
          'selectable',
          'evented',
          'lockMovementX',
          'lockMovementY',
          'lockRotation',
          'lockScalingX',
          'lockScalingY',
          'lockSkewingX',
          'lockSkewingY',
          'template', // Include template for variables
        ])

        // Scan for variables and extract them
        scanForVariables(this.canvas)
        const variableStore = useVariableStore()
        const detectedVariables = variableStore.detectedVariables

        // Save to database via Inertia router
        router.put(
          `/designs/${this.designId}`,
          {
            design_data: canvasData,
            variables: detectedVariables,
            _method: 'PUT',
          },
          {
            preserveState: true,
            preserveScroll: true,
            only: [], // Don't reload any props, just save
            onSuccess: () => {
              console.log('Design saved successfully')
              this.lastSavedAt = new Date()
              this.saveError = null
            },
            onError: (errors) => {
              console.error('Error saving design:', errors)
              this.saveError = 'Failed to save'
            },
            onFinish: () => {
              this.isSaving = false
            },
          }
        )
      } catch (error) {
        console.error('Error updating canvas data:', error)
        this.isSaving = false
        this.saveError = 'Failed to save'
      }
    },

    updateSelectedObject(e: { selected?: fabric.Object[] }) {
      if (e.selected && e.selected.length > 0) {
        this.selectedObject = markRaw(e.selected[0])
        console.log('Selected object type:', this.selectedObject.type)
      }
    },

    clearSelectedObject() {
      this.selectedObject = null
    },

    deleteSelectedObject() {
      if (this.canvas) {
        const activeObjects = this.canvas.getActiveObjects()
        if (activeObjects.length) {
          this.canvas.remove(...activeObjects)
          this.canvas.discardActiveObject()
          this.canvas.renderAll()
        }
      }
    },

    moveSelectedObject(dx: number, dy: number) {
      if (this.selectedObject && this.canvas) {
        this.selectedObject.left = (this.selectedObject.left || 0) + dx
        this.selectedObject.top = (this.selectedObject.top || 0) + dy
        this.selectedObject.setCoords()
        this.canvas.renderAll()
        this.canvas?.fire('object:modified', {
          target: this.selectedObject as fabric.FabricObject,
        })
      }
    },

    getNewObjectPosition() {
      if (this.canvas) {
        const canvasCenter = this.canvas.getCenterPoint()
        return { left: canvasCenter.x, top: canvasCenter.y }
      }
      return { left: 0, top: 0 } // Fallback if canvas is not available
    },

    addText() {
      if (this.canvas) {
        const { left, top } = this.getNewObjectPosition()
        const text = new fabric.IText('Your Text Here', {
          left,
          top,
          fontFamily: 'Arial',
          fill: '#000000',
        })
        this.canvas.add(text)
        this.canvas.setActiveObject(text)
        this.canvas.renderAll()
        this.selectedObject = markRaw(text)
      }
    },

    async addLucideIcon(iconName: string) {
      if (!this.canvas) {
        return
      }
      try {
        const { left, top } = this.getNewObjectPosition()
        const iconComponent = (await import('lucide-vue-next'))[iconName]

        if (!iconComponent) {
          console.error(`Icon component ${iconName} not found.`)
          return
        }

        const div = document.createElement('div')
        const app = createApp({
          render: () => h(iconComponent as any, { size: 60, color: '#000000' }),
        })
        app.mount(div)
        const svgString = div.innerHTML
        app.unmount()

        if (!svgString) {
          console.error('Failed to generate SVG string from component.')
          return
        }

        const { objects } = await fabric.loadSVGFromString(svgString)

        if (!objects || objects.length === 0) {
          console.error('Fabric.js failed to parse the SVG string.')
          return
        }

        const group = new fabric.Group(objects as any, {
          left,
          top,
        })

        this.canvas.add(group)
        this.canvas.setActiveObject(group)
        this.canvas.renderAll()
        this.selectedObject = markRaw(group)
      } catch (error) {
        console.error(`Failed to add icon ${iconName}:`, error)
      }
    },

    async addImage(url: string) {
      if (this.canvas) {
        try {
          const { left, top } = this.getNewObjectPosition()
          const img = await fabric.FabricImage.fromURL(url, {
            crossOrigin: 'anonymous', // Important for loading images from external URLs
          })

          if (!img) {
            console.error('Failed to load image from URL:', url)
            return
          }
          img.set({
            left,
            top,
            scaleX: 0.5,
            scaleY: 0.5,
          })
          this.canvas?.add(img)
          this.canvas?.setActiveObject(img)
          this.canvas?.renderAll()
          this.selectedObject = markRaw(img)
        } catch (err) {
          console.error('Error loading image:', err)
        }
      }
    },

    async addShape(url: string) {
      if (!this.canvas) {
        return
      }
      try {
        const response = await fetch(url)
        if (!response.ok) {
          throw new Error(`Failed to fetch SVG: ${response.statusText}`)
        }
        const svgString = await response.text()

        const { left, top } = this.getNewObjectPosition()

        const { objects } = await fabric.loadSVGFromString(svgString)

        if (!objects || objects.length === 0) {
          console.error('Fabric.js failed to parse the SVG string.')
          return
        }

        const group = new fabric.Group(objects as any, {
          left,
          top,
        })

        group.scaleToWidth(100)

        this.canvas.add(group)
        this.canvas.setActiveObject(group)
        this.canvas.renderAll()
        this.selectedObject = markRaw(group)
      } catch (error) {
        console.error(`Failed to add shape from ${url}:`, error)
      }
    },

    addRectangle() {
      if (this.canvas) {
        const { left, top } = this.getNewObjectPosition()
        const rect = new fabric.Rect({
          left,
          top,
          fill: '#ff0000',
          width: 60,
          height: 60,
        })
        this.canvas.add(rect)
        this.canvas.setActiveObject(rect)
        this.canvas.renderAll()
        this.selectedObject = markRaw(rect)
      }
    },

    addCircle() {
      if (this.canvas) {
        const { left, top } = this.getNewObjectPosition()
        const circle = new fabric.Circle({
          left,
          top,
          fill: '#00ff00',
          radius: 30,
        })
        this.canvas.add(circle)
        this.canvas.setActiveObject(circle)
        this.canvas.renderAll()
        this.selectedObject = markRaw(circle)
      }
    },

    addEllipse() {
      if (this.canvas) {
        const { left, top } = this.getNewObjectPosition()
        const ellipse = new fabric.Ellipse({
          left,
          top,
          fill: '#0000ff',
          rx: 40,
          ry: 20,
        })
        this.canvas.add(ellipse)
        this.canvas.setActiveObject(ellipse)
        this.canvas.renderAll()
        this.selectedObject = markRaw(ellipse)
      }
    },

    addTriangle() {
      if (this.canvas) {
        const { left, top } = this.getNewObjectPosition()
        const triangle = new fabric.Triangle({
          left,
          top,
          fill: '#ffff00',
          width: 60,
          height: 60,
        })
        this.canvas.add(triangle)
        this.canvas.setActiveObject(triangle)
        this.canvas.renderAll()
        this.selectedObject = markRaw(triangle)
      }
    },

    addLine() {
      if (this.canvas) {
        const { left, top } = this.getNewObjectPosition()
        const line = new fabric.Line([50, 50, 200, 50], {
          left,
          top,
          stroke: '#000000',
          strokeWidth: 2,
        })
        this.canvas.add(line)
        this.canvas.setActiveObject(line)
        this.canvas.renderAll()
        this.selectedObject = markRaw(line)
      }
    },

    bringToFront() {
      if (this.canvas) {
        const activeObject = this.canvas.getActiveObject()
        if (activeObject) {
          this.canvas.bringObjectToFront(activeObject)
          this.canvas.renderAll()
          this.canvas?.fire('object:modified', { target: activeObject })
        }
      }
    },

    sendToBack() {
      if (this.canvas) {
        const activeObject = this.canvas.getActiveObject()
        if (activeObject) {
          this.canvas.sendObjectToBack(activeObject)
          this.canvas.renderAll()
          this.canvas?.fire('object:modified', { target: activeObject })
        }
      }
    },

    bringForward() {
      if (this.canvas) {
        const activeObject = this.canvas.getActiveObject()
        if (activeObject) {
          this.canvas.bringObjectForward(activeObject)
          this.canvas.renderAll()
          this.canvas?.fire('object:modified', { target: activeObject })
        }
      }
    },

    sendBackward() {
      if (this.canvas) {
        const activeObject = this.canvas.getActiveObject()
        if (activeObject) {
          this.canvas.sendObjectBackwards(activeObject)
          this.canvas.renderAll()
          this.canvas?.fire('object:modified', { target: activeObject })
        }
      }
    },

    setFillColor(color: string) {
      if (this.canvas) {
        const activeObject = this.canvas.getActiveObject()
        if (activeObject) {
          activeObject.set('fill', color)
          this.canvas.renderAll()
          this.canvas?.fire('object:modified', { target: activeObject })
        }
      }
    },

    setCanvasColor(color: string) {
      if (this.canvas) {
        this.canvas.set('backgroundColor', color)
        this.canvas.renderAll()
        this.canvas?.fire('object:modified', {
          target: this.canvas.getActiveObject() as fabric.FabricObject,
        })
      }
    },

    setOpacity(opacity: number) {
      if (this.canvas) {
        const activeObject = this.canvas.getActiveObject()
        if (activeObject) {
          activeObject.set('opacity', opacity / 100)
          this.canvas.renderAll()
          this.canvas?.fire('object:modified', { target: activeObject })
        }
      }
    },

    // Text-specific actions
    setTextFontFamily(fontFamily: string) {
      if (this.selectedObject && this.selectedObject.type === 'i-text') {
        const textObject = this.selectedObject as fabric.IText
        textObject.set({ fontFamily })
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', { target: textObject })
      }
    },

    setTextFontSize(fontSize: number) {
      if (this.selectedObject && this.selectedObject.type === 'i-text') {
        const textObject = this.selectedObject as fabric.IText
        textObject.set({ fontSize })
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', { target: textObject })
      }
    },

    setTextFontWeight(fontWeight: string) {
      if (this.selectedObject && this.selectedObject.type === 'i-text') {
        const textObject = this.selectedObject as fabric.IText
        textObject.set({ fontWeight })
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', { target: textObject })
      }
    },

    toggleTextFontStyle(style: 'normal' | 'italic') {
      if (this.selectedObject && this.selectedObject.type === 'i-text') {
        const textObject = this.selectedObject as fabric.IText
        const newStyle = textObject.fontStyle === style ? 'normal' : style
        textObject.set({ fontStyle: newStyle })
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', { target: textObject })
      }
    },

    toggleTextDecoration(decoration: 'underline' | 'line-through') {
      if (this.selectedObject && this.selectedObject.type === 'i-text') {
        const textObject = this.selectedObject as fabric.IText
        if (decoration === 'underline') {
          textObject.set({ underline: !textObject.underline })
        } else if (decoration === 'line-through') {
          textObject.set({ linethrough: !textObject.linethrough })
        }
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', { target: textObject })
      }
    },

    // Text Layout
    setTextAlign(textAlign: 'left' | 'center' | 'right' | 'justify') {
      if (this.selectedObject && this.selectedObject.type === 'i-text') {
        const textObject = this.selectedObject as fabric.IText
        textObject.set({ textAlign })
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', { target: textObject })
      }
    },

    setTextLineHeight(lineHeight: number) {
      if (this.selectedObject && this.selectedObject.type === 'i-text') {
        const textObject = this.selectedObject as fabric.IText
        textObject.set({ lineHeight })
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', { target: textObject })
      }
    },

    setTextCharSpacing(charSpacing: number) {
      if (this.selectedObject && this.selectedObject.type === 'i-text') {
        const textObject = this.selectedObject as fabric.IText
        textObject.set({ charSpacing })
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', { target: textObject })
      }
    },

    setTextDirection(direction: 'ltr' | 'rtl') {
      if (this.selectedObject && this.selectedObject.type === 'i-text') {
        const textObject = this.selectedObject as fabric.IText
        textObject.set({ direction })
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', { target: textObject })
      }
    },

    // Text Styling
    setTextColor(color: string) {
      if (this.selectedObject && this.selectedObject.type === 'i-text') {
        const textObject = this.selectedObject as fabric.IText
        textObject.set({ fill: color })
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', { target: textObject })
      }
    },

    setTextBackgroundColor(backgroundColor: string) {
      if (this.selectedObject && this.selectedObject.type === 'i-text') {
        const textObject = this.selectedObject as fabric.IText
        textObject.set({ textBackgroundColor: backgroundColor })
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', { target: textObject })
      }
    },

    setTextLineBackgroundColor(lineBackgroundColor: string) {
      if (this.selectedObject && this.selectedObject.type === 'i-text') {
        const textObject = this.selectedObject as fabric.IText
        // Fabric.js does not have a direct 'lineBackgroundColor' property for IText.
        // This might require custom rendering or per-line objects.
        // For now, I'll set it as a custom property or use textBackgroundColor if applicable.
        // Assuming 'textBackgroundColor' is what's intended for the entire text block.
        // If per-line background is strictly needed, it's a more complex feature.
        // For this task, I will apply it to the whole text block.
        textObject.set({ textBackgroundColor: lineBackgroundColor })
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', { target: textObject })
      }
    },

    capitalizeSelectedText() {
      if (this.selectedObject && this.selectedObject.type === 'i-text') {
        const textObject = this.selectedObject as fabric.IText
        if (textObject.text) {
          textObject.set(
            'text',
            textObject.text
              .split(' ')
              .map(
                (word) =>
                  word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
              )
              .join(' ')
          )
          this.canvas?.renderAll()
          this.canvas?.fire('object:modified', { target: textObject })
        }
      }
    },

    uppercaseSelectedText() {
      if (this.selectedObject && this.selectedObject.type === 'i-text') {
        const textObject = this.selectedObject as fabric.IText
        if (textObject.text) {
          textObject.set('text', textObject.text.toUpperCase())
          this.canvas?.renderAll()
          this.canvas?.fire('object:modified', { target: textObject })
        }
      }
    },

    lowercaseSelectedText() {
      if (this.selectedObject && this.selectedObject.type === 'i-text') {
        const textObject = this.selectedObject as fabric.IText
        if (textObject.text) {
          textObject.set('text', textObject.text.toLowerCase())
          this.canvas?.renderAll()
          this.canvas?.fire('object:modified', { target: textObject })
        }
      }
    },

    downloadCanvas(format: string) {
      if (!this.canvas) {
        console.error('Canvas not initialized.')
        return
      }

      this.downloadFormat = format

      let dataUrl: string | undefined
      let filename = `canvas.${format}`

      switch (format) {
        case 'json':
          const json = this.canvas.toJSON()
          const blob = new Blob([JSON.stringify(json, null, 2)], {
            type: 'application/json',
          })
          dataUrl = URL.createObjectURL(blob)
          break
        case 'png':
          dataUrl = this.canvas.toDataURL({
            format: 'png',
            quality: 1,
            multiplier: 1,
          })
          break
        case 'jpeg':
          dataUrl = this.canvas.toDataURL({
            format: 'jpeg',
            quality: 0.8,
            multiplier: 1,
          })
          break
        case 'svg':
          dataUrl = this.canvas.toSVG()
          filename = `canvas.svg`
          const svgBlob = new Blob([dataUrl], { type: 'image/svg+xml' })
          dataUrl = URL.createObjectURL(svgBlob)
          break
        default:
          console.error('Unsupported format:', format)
          return
      }

      const link = document.createElement('a')
      link.href = dataUrl
      link.download = filename
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)

      if (format === 'json' || format === 'svg') {
        URL.revokeObjectURL(dataUrl)
      }
    },

    async undo() {
      if (this.canvasHistory) {
        await this.canvasHistory.undo()
      }
    },

    async redo() {
      if (this.canvasHistory) {
        await this.canvasHistory.redo()
      }
    },

    setObjectShadow(shadowOptions: {
      enable: boolean
      offsetX?: number
      offsetY?: number
      blur?: number
      color?: string
    }) {
      if (this.selectedObject) {
        if (shadowOptions.enable) {
          const currentShadow = this.selectedObject.shadow as fabric.Shadow
          this.selectedObject.set({
            shadow: new fabric.Shadow({
              color:
                shadowOptions.color ||
                currentShadow?.color ||
                'rgba(0,0,0,0.5)',
              offsetX: shadowOptions.offsetX ?? (currentShadow?.offsetX || 0),
              offsetY: shadowOptions.offsetY ?? (currentShadow?.offsetY || 0),
              blur: shadowOptions.blur ?? (currentShadow?.blur || 0),
            }),
          })
        } else {
          this.selectedObject.set({ shadow: null })
        }
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', {
          target: this.selectedObject as fabric.FabricObject,
        })
      }
    },

    // Shape/SVG specific actions
    setStrokeColor(color: string) {
      if (
        this.selectedObject &&
        (this.selectedObject.type === 'rect' ||
          this.selectedObject.type === 'circle' ||
          this.selectedObject.type === 'ellipse' ||
          this.selectedObject.type === 'triangle' ||
          this.selectedObject.type === 'line' ||
          this.selectedObject.type === 'path' ||
          this.selectedObject.type === 'group') // Added group for SVG
      ) {
        this.selectedObject.set({ stroke: color })
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', {
          target: this.selectedObject as fabric.FabricObject,
        })
      }
    },

    setStrokeWidth(width: number) {
      if (
        this.selectedObject &&
        (this.selectedObject.type === 'rect' ||
          this.selectedObject.type === 'circle' ||
          this.selectedObject.type === 'ellipse' ||
          this.selectedObject.type === 'triangle' ||
          this.selectedObject.type === 'line' ||
          this.selectedObject.type === 'path' ||
          this.selectedObject.type === 'group') // Added group for SVG
      ) {
        this.selectedObject.set({ strokeWidth: width })
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', {
          target: this.selectedObject as fabric.FabricObject,
        })
      }
    },

    setWidth(width: number) {
      if (this.selectedObject) {
        this.selectedObject.set({
          width: width / (this.selectedObject.scaleX || 1),
        })
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', {
          target: this.selectedObject as fabric.FabricObject,
        })
      }
    },

    setHeight(height: number) {
      if (this.selectedObject) {
        this.selectedObject.set({
          height: height / (this.selectedObject.scaleY || 1),
        })
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', {
          target: this.selectedObject as fabric.FabricObject,
        })
      }
    },

    setImageWidth(width: number) {
      if (this.selectedObject && this.selectedObject.type === 'image') {
        const imageObject = this.selectedObject as fabric.FabricImage
        imageObject.set({ width: width / (imageObject.scaleX || 1) })
        imageObject.setCoords()
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', { target: imageObject })
      }
    },

    setImageHeight(height: number) {
      if (this.selectedObject && this.selectedObject.type === 'image') {
        const imageObject = this.selectedObject as fabric.FabricImage
        imageObject.set({ height: height / (imageObject.scaleY || 1) })
        imageObject.setCoords()
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', { target: imageObject })
      }
    },

    // Image-specific actions
    applyImageFilter(filterType: string, value: any) {
      if (this.selectedObject && this.selectedObject.type === 'image') {
        const imageObject = this.selectedObject as fabric.FabricImage
        let filter: any = null // Use any for filter type due to type definition issues

        // Remove existing filter of the same type if it's a single-instance filter
        imageObject.filters = (imageObject.filters || []).filter((f: any) => {
          if (f && f.type === filterType) {
            return false // Remove this filter
          }
          return true
        })

        switch (filterType) {
          case 'Brightness':
            filter = new fabric.filters.Brightness({
              brightness: value / 100,
            })
            break
          case 'Contrast':
            filter = new fabric.filters.Contrast({
              contrast: value / 100,
            })
            break
          case 'Saturation':
            filter = new fabric.filters.Saturation({
              saturation: value / 100,
            })
            break
          case 'Grayscale':
            if (value > 0) filter = new fabric.filters.Grayscale()
            break
          case 'Sepia':
            if (value > 0) filter = new fabric.filters.Sepia()
            break
          case 'Blur':
            filter = new fabric.filters.Blur({ blur: value })
            break
          case 'Pixelate':
            filter = new fabric.filters.Pixelate({
              blocksize: value,
            })
            break
          case 'Noise':
            filter = new fabric.filters.Noise({ noise: value })
            break
          case 'Invert':
            if (value) filter = new fabric.filters.Invert()
            break
          case 'RemoveColor': // For "Remove White"
            if (value.enable)
              filter = new fabric.filters.RemoveColor({
                color: value.color || '#FFFFFF',
                distance: value.distance || 0.02,
              })
            break
          case 'BlendColor':
            if (value.enable)
              filter = new fabric.filters.BlendColor({
                color: value.color || '#000000',
                mode: value.mode || 'multiply',
                alpha: value.alpha || 1,
              })
            break
          default:
            break
        }

        if (filter) {
          imageObject.filters.push(filter)
        }
        imageObject.applyFilters()
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', {
          target: this.selectedObject as fabric.FabricObject,
        })
      }
    },

    resetImageFilters() {
      if (this.selectedObject && this.selectedObject.type === 'image') {
        const imageObject = this.selectedObject as fabric.FabricImage
        imageObject.filters = []
        imageObject.applyFilters()
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', {
          target: this.selectedObject as fabric.FabricObject,
        })
      }
    },

    setImageFitOption(option: 'fill' | 'fit' | 'stretch' | 'crop') {
      if (this.selectedObject && this.selectedObject.type === 'image') {
        const imageObject = this.selectedObject as fabric.FabricImage
        const canvasWidth = this.canvas?.width || 0
        const canvasHeight = this.canvas?.height || 0

        if (!imageObject.getOriginalSize) {
          console.warn('Image object does not have getOriginalSize method.')
          return
        }

        const originalWidth = imageObject.getOriginalSize().width
        const originalHeight = imageObject.getOriginalSize().height

        if (originalWidth === 0 || originalHeight === 0) {
          console.warn('Original image dimensions are zero.')
          return
        }

        imageObject.set({
          scaleX: 1,
          scaleY: 1,
          cropX: 0,
          cropY: 0,
          width: originalWidth,
          height: originalHeight,
        })

        switch (option) {
          case 'fill':
            imageObject.scaleToWidth(canvasWidth)
            if (imageObject.getScaledHeight() < canvasHeight) {
              imageObject.scaleToHeight(canvasHeight)
            }
            break
          case 'fit':
            imageObject.scaleToWidth(canvasWidth)
            if (imageObject.getScaledHeight() > canvasHeight) {
              imageObject.scaleToHeight(canvasHeight)
            }
            break
          case 'stretch':
            imageObject.set({
              scaleX: canvasWidth / originalWidth,
              scaleY: canvasHeight / originalHeight,
            })
            break
          case 'crop':
            // This is a placeholder. Actual cropping requires user interaction.
            // For now, it might just center the image or do nothing specific.
            // A more advanced implementation would involve setting cropX, cropY, width, height.
            imageObject.scaleToWidth(canvasWidth)
            if (imageObject.getScaledHeight() > canvasHeight) {
              imageObject.scaleToHeight(canvasHeight)
            }
            // Center the image within the canvas after scaling
            imageObject.set({
              left: (canvasWidth - imageObject.getScaledWidth()) / 2,
              top: (canvasHeight - imageObject.getScaledHeight()) / 2,
            })
            break
        }
        imageObject.setCoords()
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', {
          target: this.selectedObject as fabric.FabricObject,
        })
      }
    },

    setImageClipPath(clipPathType: 'none' | 'circle' | 'rect' | 'ellipse') {
      if (this.selectedObject && this.selectedObject.type === 'image') {
        const imageObject = this.selectedObject as fabric.FabricImage
        let clipPath: fabric.Object | null = null

        const imageCenter = imageObject.getCenterPoint()
        const imageWidth = imageObject.getScaledWidth()
        const imageHeight = imageObject.getScaledHeight()

        switch (clipPathType) {
          case 'circle':
            clipPath = new fabric.Circle({
              radius: Math.min(imageWidth, imageHeight) / 2,
              originX: 'center',
              originY: 'center',
              absolutePositioned: true,
            })
            break
          case 'rect':
            clipPath = new fabric.Rect({
              width: imageWidth,
              height: imageHeight,
              originX: 'center',
              originY: 'center',
              absolutePositioned: true,
            })
            break
          case 'ellipse':
            clipPath = new fabric.Ellipse({
              rx: imageWidth / 2,
              ry: imageHeight / 2,
              originX: 'center',
              originY: 'center',
              absolutePositioned: true,
            })
            break
          case 'none':
          default:
            clipPath = null
            break
        }

        if (clipPath) {
          // Position the clipPath relative to the image's center
          clipPath.left = imageCenter.x
          clipPath.top = imageCenter.y
        }

        imageObject.set({ clipPath })
        this.canvas?.renderAll()
        this.canvas?.fire('object:modified', {
          target: this.selectedObject as fabric.FabricObject,
        })
      }
    },
  },
})
  ; ('')
