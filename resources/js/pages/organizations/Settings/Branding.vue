<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import SettingsLayout from '@/layouts/organizations/SettingsLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import type { BreadcrumbItemType } from '@/types'
import { toast } from 'vue-sonner'

interface OrgProps {
  id: string
  name: string
  icon_url?: string | null
  logo_url?: string | null
  has_growth_plan?: boolean
}

const page = usePage()
const organization = computed(() => page.props.organization as OrgProps | null)

const breadcrumbs: BreadcrumbItemType[] = [
  { title: 'Organization' },
  { title: 'Settings' },
  { title: 'Branding' },
]

function getCsrfToken(): string {
  const name = 'XSRF-TOKEN'
  const value = `; ${document.cookie}`
  const parts = value.split(`; ${name}=`)
  if (parts.length === 2) {
    return decodeURIComponent(parts.pop()?.split(';').shift() || '')
  }
  return ''
}

const uploadingIcon = ref(false)
const uploadingLogo = ref(false)

async function uploadToCollection(file: File, collection: 'icon' | 'logo') {
  if (!organization.value) return

  try {
    const isIcon = collection === 'icon'
    isIcon ? (uploadingIcon.value = true) : (uploadingLogo.value = true)

    const fd = new FormData()
    fd.append('model_type', 'organization')
    fd.append('model_id', organization.value.id)
    fd.append('collection', collection)
    fd.append('file', file)

    const res = await fetch('/media', {
      method: 'POST',
      headers: { 'X-XSRF-TOKEN': getCsrfToken(), Accept: 'application/json' },
      credentials: 'same-origin',
      body: fd,
    })

    if (!res.ok) {
      const data = await res.json().catch(() => ({}))
      throw new Error(data.message || data.error || `Failed to upload ${collection}`)
    }

    // Force reload to get fresh URLs
    window.location.reload()
  } catch (e: any) {
    toast.error(e.message || 'Upload failed')
  } finally {
    uploadingIcon.value = false
    uploadingLogo.value = false
  }
}

async function clearCollection(collection: 'icon' | 'logo') {
  if (!organization.value) return

  try {
    const res = await fetch('/media', {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': getCsrfToken(),
        Accept: 'application/json',
      },
      credentials: 'same-origin',
      body: JSON.stringify({ model_type: 'organization', model_id: organization.value.id, collection }),
    })
    if (!res.ok) {
      const data = await res.json().catch(() => ({}))
      throw new Error(data.message || data.error || `Failed to remove ${collection}`)
    }
    window.location.reload()
  } catch (e: any) {
    toast.error(e.message || 'Remove failed')
  }
}

function onIconFileChange(e: Event) {
  const input = e.target as HTMLInputElement
  if (input.files && input.files[0]) {
    uploadToCollection(input.files[0], 'icon')
  }
}

function onLogoFileChange(e: Event) {
  const input = e.target as HTMLInputElement
  if (input.files && input.files[0]) {
    uploadToCollection(input.files[0], 'logo')
  }
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Organization Branding" />

    <SettingsLayout>
      <div class="flex flex-col space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Organization Icon</CardTitle>
            <CardDescription>
              Square 1:1, 512×512 recommended, ≤2MB. PNG, JPG, or WEBP.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div class="flex items-center gap-6">
              <div class="size-16 overflow-hidden rounded-lg border bg-neutral-100 flex items-center justify-center">
                <img
                  v-if="organization?.icon_url"
                  :src="organization?.icon_url"
                  alt="Icon preview"
                  class="h-full w-full object-cover"
                />
                <span v-else class="text-xs text-neutral-500">No icon</span>
              </div>
              <div class="space-y-2">
                <div class="flex items-center gap-3">
                  <Input type="file" accept="image/png,image/jpeg,image/webp" @change="onIconFileChange" />
                  <Button variant="secondary" :disabled="uploadingIcon || !organization?.icon_url" @click="clearCollection('icon')">
                    Remove
                  </Button>
                </div>
                <p class="text-xs text-muted-foreground">Used in menus and small avatars.</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Organization Logo</CardTitle>
            <CardDescription>
              Wide 3–4:1, 1600×400 recommended, ≤2MB. PNG, JPG, or WEBP.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div class="flex items-center gap-6">
              <div class="h-12 w-56 overflow-hidden rounded-md border bg-neutral-100 flex items-center justify-center">
                <img
                  v-if="organization?.logo_url"
                  :src="organization?.logo_url"
                  alt="Logo preview"
                  class="h-full w-full object-contain"
                />
                <span v-else class="text-xs text-neutral-500">No logo</span>
              </div>
              <div class="space-y-2">
                <div class="flex items-center gap-3">
                  <Input type="file" accept="image/png,image/jpeg,image/webp" @change="onLogoFileChange" />
                  <Button variant="secondary" :disabled="uploadingLogo || !organization?.logo_url" @click="clearCollection('logo')">
                    Remove
                  </Button>
                </div>
                <p class="text-xs text-muted-foreground">Shown next to name for Growth plan.</p>
                <p v-if="!organization?.has_growth_plan" class="text-xs text-amber-600">
                  Logo display is a Growth plan feature.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
