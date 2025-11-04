/*
  Postinstall fix for fabric-guideline-plugin with Fabric v6.
  Rewrites ESM imports to star-import form expected by Fabric 6.
*/

import { readFileSync, writeFileSync, existsSync } from 'fs'
import { resolve } from 'path'

const root = process.cwd()
const files = [
  'node_modules/fabric-guideline-plugin/dist/index.js',
  'node_modules/fabric-guideline-plugin/dist/module/aligning.d.ts',
]

let changedAny = false

for (const rel of files) {
  const file = resolve(root, rel)
  if (!existsSync(file)) continue
  const src = readFileSync(file, 'utf8')

  let next = src
  next = next.replace(
    /import\s+\{\s*fabric\s+as\s+([A-Za-z_$][0-9A-Za-z_$]*)\s*\}\s+from\s+"fabric";/,
    'import * as $1 from "fabric";'
  )
  next = next.replace(
    /import\s+\{\s*fabric\s*\}\s+from\s+"fabric";/,
    'import * as fabric from "fabric";'
  )

  if (next !== src) {
    writeFileSync(file, next, 'utf8')
    changedAny = true
    // eslint-disable-next-line no-console
    console.log(`[postinstall] patched ${rel}`)
  }
}

if (!changedAny) {
  // eslint-disable-next-line no-console
  console.log('[postinstall] no fabric-guideline-plugin changes needed')
}


