import { useEditorStore } from '@/stores/editor'
import * as fabric from 'fabric'
import { ActiveSelection } from 'fabric'

type EditorStore = ReturnType<typeof useEditorStore>

interface Keybinding {
  key: string
  ctrlOrMeta?: boolean
  action: (
    event: KeyboardEvent,
    editorStore: EditorStore,
    canvas: fabric.Canvas
  ) => void
}

const handleDelete = (event: KeyboardEvent, store: EditorStore) => {
  store.deleteSelectedObject()
  event.preventDefault()
}

const handleEscape = (event: KeyboardEvent, store: EditorStore) => {
  if (store.canvas) {
    store.canvas.discardActiveObject()
    store.canvas.renderAll()
    store.clearSelectedObject()
  }
  event.preventDefault()
}

const handleDuplicate = (event: KeyboardEvent, store: EditorStore) => {
  event.preventDefault()
  if (store.canvas) {
    const activeObject = store.canvas.getActiveObject()
    if (activeObject) {
      ;(async () => {
        const clonedObject = await activeObject.clone()
        clonedObject.set({
          left: (clonedObject.left || 0) + 10,
          top: (clonedObject.top || 0) + 10,
          evented: true,
        })
        store.canvas?.discardActiveObject()
        store.canvas?.add(clonedObject)
        store.canvas?.setActiveObject(clonedObject)
        store.canvas?.requestRenderAll()
      })()
    }
  }
}

const handleSelectAll = (event: KeyboardEvent, store: EditorStore) => {
  if (store.canvas) {
    store.canvas.discardActiveObject()
    const allObjects = store.canvas.getObjects()
    if (allObjects.length > 0) {
      const activeSelection = new ActiveSelection(allObjects, {
        canvas: store.canvas as fabric.Canvas,
      })
      store.canvas.setActiveObject(activeSelection)
      store.canvas.renderAll()
      store.updateSelectedObject({ selected: [activeSelection] })
    }
  }
  event.preventDefault()
}

const handleArrowMove = (
  event: KeyboardEvent,
  store: EditorStore,
  dx: number,
  dy: number
) => {
  if (store.selectedObject) {
    store.moveSelectedObject(dx, dy)
    event.preventDefault()
  }
}

export function setupKeybindings(
  editorStore: EditorStore,
  _canvas: fabric.Canvas
) {
  const handleKeyDown = (event: KeyboardEvent) => {
    const target = event.target as HTMLElement

    // Ignore keyboard shortcuts if the user is typing in an input field
    if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA') {
      return
    }

    const keybindings: Keybinding[] = [
      { key: 'Delete', action: handleDelete },
      { key: 'Backspace', action: handleDelete },
      { key: 'Escape', action: handleEscape },
      { key: 'd', ctrlOrMeta: true, action: handleDuplicate },
      { key: 'a', ctrlOrMeta: true, action: handleSelectAll },
      {
        key: 'z',
        ctrlOrMeta: true,
        action: (event, store) => {
          if (!event.shiftKey) {
            store.undo()
          } else {
            store.redo()
          }
          event.preventDefault()
        },
      },
      {
        key: 'y',
        ctrlOrMeta: true,
        action: (event, store) => {
          store.redo()
          event.preventDefault()
        },
      },
      {
        key: 'ArrowUp',
        action: (event, store, _canvas) => handleArrowMove(event, store, 0, -1),
      },
      {
        key: 'ArrowDown',
        action: (event, store, _canvas) => handleArrowMove(event, store, 0, 1),
      },
      {
        key: 'ArrowLeft',
        action: (event, store, _canvas) => handleArrowMove(event, store, -1, 0),
      },
      {
        key: 'ArrowRight',
        action: (event, store, _canvas) => handleArrowMove(event, store, 1, 0),
      },
    ]

    for (const binding of keybindings) {
      const isModifierMatch = binding.ctrlOrMeta
        ? event.ctrlKey || event.metaKey
        : true

      if (event.key === binding.key && isModifierMatch) {
        binding.action(event, editorStore, _canvas)
        return
      }
    }
  }

  window.addEventListener('keydown', handleKeyDown)

  return () => {
    window.removeEventListener('keydown', handleKeyDown)
  }
}
