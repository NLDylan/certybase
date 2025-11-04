import * as fabric from 'fabric'
import { useVariableStore } from '@/stores/variables'
import { useEditorStore } from '@/stores/editor'

interface CustomTextbox extends fabric.Textbox {
  template?: string
}

declare module 'fabric' {
  interface IText {
    template?: string
  }
  interface Textbox {
    template?: string
  }
}

export const VARIABLE_REGEX = /{{\s*([\w-]+)\s*}}/g

fabric.Textbox.prototype.toObject = (function (originalToObject) {
  return function (this: fabric.Textbox, propertiesToInclude?: string[]) {
    const props = (propertiesToInclude || []).concat(['template'])
    return originalToObject.call(this, props as any)
  }
})(
  fabric.Textbox.prototype.toObject
) as typeof fabric.Textbox.prototype.toObject

export function scanForVariables(canvas: fabric.Canvas | undefined) {
  if (!canvas) return
  const variableStore = useVariableStore()
  const allVariables: string[] = []
  canvas.getObjects().forEach((obj) => {
    if (obj.type === 'i-text' || obj.type === 'textbox') {
      const textObj = obj as CustomTextbox // Use CustomTextbox
      // If the object has a template, use that for scanning.
      // Otherwise, use its text and set it as the template.
      if (!textObj.template) {
        textObj.template = textObj.text
      }
      const text = textObj.template || ''
      let match
      while ((match = VARIABLE_REGEX.exec(text)) !== null) {
        allVariables.push(match[1])
      }
    }
  })
  variableStore.setDetectedVariables(allVariables)
}

export function renderTemplate(
  template: string,
  values: Record<string, string>
): string {
  return template.replace(VARIABLE_REGEX, (_, key) => {
    const value = values[key]
    if (value === '' || value === null || value === undefined) {
      return `"{{${key}}}"` // Display as "{{variable_name}}"
    }
    return value
  })
}

export function applyVariables(canvas: fabric.Canvas | undefined) {
  if (!canvas) return
  const variableStore = useVariableStore()
  const editorStore = useEditorStore()
  const values = variableStore.variableValues
  canvas.getObjects().forEach((obj) => {
    if (obj.type === 'i-text' || obj.type === 'textbox') {
      const textObj = obj as CustomTextbox // Use CustomTextbox
      if (textObj.template) {
        textObj.set('text', renderTemplate(textObj.template, values))
      }
    }
  })
  canvas.requestRenderAll()
  editorStore.updateCanvasData() // Persist changes
}

export function handleEditingExited(
  options: { target: fabric.Object },
  canvas: fabric.Canvas | undefined
) {
  const textObj = options.target as CustomTextbox // Use CustomTextbox
  if (textObj && (textObj.type === 'i-text' || textObj.type === 'textbox')) {
    // Update the template with the latest text content
    textObj.template = textObj.text
    scanForVariables(canvas)
    // No need to call applyVariables here, as the user will do that manually
  }
}
