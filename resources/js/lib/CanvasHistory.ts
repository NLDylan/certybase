import * as fabric from 'fabric'

class CanvasHistory {
  canvas: fabric.Canvas
  history: any[][]
  historyRedo: any[][]
  _isClearingCanvas: boolean
  onHistoryChange: () => void

  constructor(canvas: fabric.Canvas, onHistoryChange: () => void) {
    this.canvas = canvas
    this.history = []
    this.historyRedo = []
    this._isClearingCanvas = false
    this.onHistoryChange = onHistoryChange

    this._init()
  }

  _init() {
    this._saveCanvasState()
    this.canvas.on('object:added', () => this._saveCanvasState())
    this.canvas.on('object:modified', () => this._saveCanvasState())
    this.canvas.on('object:removed', () => {
      if (!this._isClearingCanvas) {
        this._saveCanvasState()
      }
    })
  }

  _saveCanvasState() {
    const jsonCanvas = structuredClone(this.canvas.toObject().objects)
    this.history.push(jsonCanvas)
    this.historyRedo = []
    this.onHistoryChange()
  }

  _clearCanvas() {
    this._isClearingCanvas = true
    this.canvas.remove(...this.canvas.getObjects())
    this._isClearingCanvas = false
  }

  async undo() {
    if (this.history.length <= 1) return

    this._clearCanvas()

    this.historyRedo.push(this.history.pop()!)
    const lastState = this.history[this.history.length - 1]
    const objects = (await fabric.util.enlivenObjects(
      lastState
    )) as fabric.Object[]

    this._applyState(objects)
    this.onHistoryChange()
  }

  async redo() {
    if (this.historyRedo.length === 0) return

    this._clearCanvas()
    const lastState = this.historyRedo.pop()!
    this.history.push(lastState)

    const objects = (await fabric.util.enlivenObjects(
      lastState
    )) as fabric.Object[]

    this._applyState(objects)
    this.onHistoryChange()
  }

  _applyState(objects: fabric.Object[]) {
    this.canvas.off('object:added')
    this.canvas.off('object:modified')
    this.canvas.off('object:removed')

    objects.forEach((obj) => {
      this.canvas.add(obj)
    })

    this.canvas.on('object:added', () => this._saveCanvasState())
    this.canvas.on('object:modified', () => this._saveCanvasState())
    this.canvas.on('object:removed', () => {
      if (!this._isClearingCanvas) {
        this._saveCanvasState()
      }
    })
    this.canvas.renderAll()
  }

  canUndo(): boolean {
    return this.history.length > 1
  }

  canRedo(): boolean {
    return this.historyRedo.length > 0
  }
}

export default CanvasHistory
