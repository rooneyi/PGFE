/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_API_BASE_URL?: string
  readonly VITE_SANCTUM_BASE_URL?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}

// Allow TypeScript to understand Vue SFC imports
declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  // eslint-disable-next-line @typescript-eslint/ban-types
  const component: DefineComponent<{}, {}, any>
  export default component
}
