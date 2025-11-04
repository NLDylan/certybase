import { ref } from 'vue'

const VITE_UNSPLASH_ACCESS_KEY = import.meta.env.VITE_UNSPLASH_ACCESS_KEY

export function useUnsplash() {
  const images = ref<any[]>([])
  const loading = ref(false)
  const page = ref(1)
  const totalPages = ref(0)
  const query = ref('certificate image background')

  async function searchImages(newSearch = false) {
    if (loading.value) return
    if (newSearch) {
      page.value = 1
      images.value = []
      totalPages.value = 0
    }

    if (page.value > totalPages.value && totalPages.value !== 0) return

    loading.value = true
    try {
      const response = await fetch(
        `https://api.unsplash.com/search/photos?query=${query.value}&page=${page.value}&per_page=20&client_id=${VITE_UNSPLASH_ACCESS_KEY}`
      )
      const data = await response.json()
      if (newSearch) {
        images.value = data.results
      } else {
        images.value.push(...data.results)
      }
      totalPages.value = data.total_pages
      page.value++
    } catch (error) {
      console.error('Error fetching images from Unsplash:', error)
    } finally {
      loading.value = false
    }
  }

  return {
    images,
    loading,
    query,
    searchImages,
  }
}
