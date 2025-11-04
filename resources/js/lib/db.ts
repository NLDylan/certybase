export interface Project {
  id: string
  name: string
  template: string
  createdAt: Date
  updatedAt: Date
  canvasData: any
}

// Placeholder client-side store. Replace with API calls to your PostgreSQL backend.
export const db = {
  projects: {
    async get(_id: string): Promise<Project | null> {
      return null
    },
  },
}
