import { ref } from 'vue'

const VITE_UNSPLASH_ACCESS_KEY = import.meta.env.VITE_UNSPLASH_ACCESS_KEY

export function useUnsplash() {
  const images = ref<any[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const page = ref(1)
  const totalPages = ref(0)
  const query = ref('certificate image background')

  async function searchImages(newSearch = false) {
    if (loading.value) return

    // Check if API key is configured
    if (!VITE_UNSPLASH_ACCESS_KEY) {
      error.value = 'Unsplash Access Key is not configured'
      loading.value = false
      return
    }

    if (newSearch) {
      page.value = 1
      images.value = []
      totalPages.value = 0
      error.value = null
    }

    if (page.value > totalPages.value && totalPages.value !== 0) return

    loading.value = true
    error.value = null

    try {
      const response = await fetch(
        `https://api.unsplash.com/search/photos?query=${query.value}&page=${page.value}&per_page=20&client_id=${VITE_UNSPLASH_ACCESS_KEY}`
      )

      if (!response.ok) {
        if (response.status === 401) {
          error.value = 'Unsplash Access Key is invalid or expired. Make sure you\'re using your Access Key (not Secret Key) and it\'s configured correctly.'
        } else if (response.status === 403) {
          error.value = 'Unsplash API access forbidden'
        } else {
          error.value = `Failed to load images: ${response.statusText}`
        }
        loading.value = false
        return
      }

      const data = await response.json()

      if (!data.results || !Array.isArray(data.results)) {
        error.value = 'Invalid response from Unsplash API'
        loading.value = false
        return
      }

      if (newSearch) {
        images.value = data.results
      } else {
        images.value.push(...data.results)
      }
      totalPages.value = data.total_pages || 0
      page.value++
    } catch (error) {
      console.error('Error fetching images from Unsplash:', error)
      error.value = 'Failed to load images. Please check your connection.'
    } finally {
      loading.value = false
    }
  }

  return {
    images,
    loading,
    error,
    query,
    searchImages,
  }
}
