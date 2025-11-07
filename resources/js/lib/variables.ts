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

const VARIABLE_REGEX = /{{\s*([\w.-]+)\s*}}/g
const VARIABLE_EXISTS_REGEX = /{{\s*[\w.-]+\s*}}/
const TEXT_OBJECT_TYPES = new Set(['i-text', 'textbox', 'text'])

function isTextObject(obj: fabric.Object | CustomTextbox | undefined): obj is CustomTextbox {
  if (!obj) {
    return false
  }

  const type = (obj.type ?? '').toString().toLowerCase()

  return TEXT_OBJECT_TYPES.has(type)
}

function hasVariableSyntax(content?: string | null): boolean {
  if (typeof content !== 'string') {
    return false
  }

  return VARIABLE_EXISTS_REGEX.test(content)
}

function resolveTemplateSource(textObj: CustomTextbox): string {
  const currentText = typeof textObj.text === 'string' ? textObj.text : ''
  const templateText = typeof textObj.template === 'string' ? textObj.template : ''

  if (!templateText) {
    textObj.template = currentText
    return currentText
  }

  if (hasVariableSyntax(templateText)) {
    return templateText
  }

  if (hasVariableSyntax(currentText)) {
    textObj.template = currentText
    return currentText
  }

  return templateText || currentText
}

function extractVariables(text: string): string[] {
  const matches: string[] = []
  const regex = new RegExp(VARIABLE_REGEX.source, 'g')
  let match: RegExpExecArray | null

  while ((match = regex.exec(text)) !== null) {
    matches.push(match[1])
  }

  return matches
}

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
    if (isTextObject(obj)) {
      const textObj = obj as CustomTextbox
      const text = resolveTemplateSource(textObj)
      allVariables.push(...extractVariables(text))
    }
  })
  variableStore.setDetectedVariables(allVariables)
}

export function renderTemplate(
  template: string,
  values: Record<string, string>
): string {
  return template.replace(VARIABLE_REGEX, (match, key) => {
    const value = values[key]
    if (value === '' || value === null || value === undefined) {
      return match
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
    if (isTextObject(obj)) {
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
  if (isTextObject(textObj)) {
    // Update the template with the latest text content
    textObj.template = textObj.text
    scanForVariables(canvas)
    // No need to call applyVariables here, as the user will do that manually
  }
}
