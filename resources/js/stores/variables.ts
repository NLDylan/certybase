import { ref, computed } from 'vue'
import { defineStore } from 'pinia'

export const useVariableStore = defineStore('variables', () => {
  // State
  const detectedVariables = ref<string[]>([])
  const variableValues = ref<Record<string, string>>({})

  // Getters
  const variableCount = computed(() => detectedVariables.value.length)

  // Actions
  function setDetectedVariables(variables: string[]) {
    const uniqueVariables = [...new Set(variables)]
    detectedVariables.value = uniqueVariables

    // Preserve existing values, initialize new ones
    const newValues: Record<string, string> = {}
    for (const key of uniqueVariables) {
      newValues[key] = variableValues.value[key] || ''
    }
    variableValues.value = newValues
  }

  function setVariableValue(key: string, value: string) {
    variableValues.value[key] = value
  }

  function resetValues() {
    for (const key in variableValues.value) {
      variableValues.value[key] = ''
    }
  }

  return {
    detectedVariables,
    variableValues,
    variableCount,
    setDetectedVariables,
    setVariableValue,
    resetValues,
  }
})
