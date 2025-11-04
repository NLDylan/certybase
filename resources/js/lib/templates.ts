import { ref } from 'vue'

export interface Template {
  name: string
  image: string
  jsonPath: string
}

export const templates: Template[] = [
  {
    name: 'Certificate 1',
    image: '/templates/certificate1.png',
    jsonPath: '/templates/certificate1.json',
  },
  {
    name: 'Certificate 2',
    image: '/templates/certificate2.png',
    jsonPath: '/templates/certificate2.json',
  },
]

export const selectedTemplate = ref<Template | null>(null)
