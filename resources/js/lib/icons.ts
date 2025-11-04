import * as icons from 'lucide-vue-next'

export const iconList = Object.keys(icons).map((name) => ({
  name,
  component: (icons as any)[name],
}))
