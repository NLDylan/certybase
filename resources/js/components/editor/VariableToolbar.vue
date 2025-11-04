<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useVariableStore } from '@/stores/variables'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardFooter,
} from '@/components/ui/card'
import { toast } from 'vue-sonner'

const variableStore = useVariableStore()
const { detectedVariables, variableValues, variableCount } =
  storeToRefs(variableStore)

const emit = defineEmits(['apply', 'reset'])

function handleApply() {
  emit('apply')
  toast('Changes Applied')
}

function handleReset() {
  variableStore.resetValues()
  emit('apply') // Also apply the reset to the canvas
  toast({
    title: 'Variables Reset',
    description: 'Variable values have been reset to their defaults.',
  })
}
</script>

<template>
  <Card class="w-80">
    <CardHeader>
      <CardTitle>Variables ({{ variableCount }})</CardTitle>
    </CardHeader>
    <CardContent class="space-y-4">
      <div v-if="variableCount === 0" class="text-muted-foreground text-sm">
        No variables detected. Use <code v-pre>{{ variable_name }}</code> in a
        text box to create one.
      </div>

      <div
        v-for="variable in detectedVariables"
        :key="variable"
        class="space-y-2"
      >
        <Label :for="variable">{{ variable }}</Label>
        <Input
          :id="variable"
          v-model="variableValues[variable]"
          @update:model-value="
            (value) => variableStore.setVariableValue(variable, String(value))
          "
        />
      </div>
    </CardContent>
    <CardFooter class="flex justify-between">
      <Button
        variant="outline"
        @click="handleReset"
        :disabled="variableCount === 0"
        >Reset</Button
      >
      <Button @click="handleApply" :disabled="variableCount === 0"
        >Apply Changes</Button
      >
    </CardFooter>
  </Card>
</template>
