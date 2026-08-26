import path from 'node:path'
import tailwindcss from '@tailwindcss/vite'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

const laravelPublic = path.resolve(__dirname, '../PGFEv2-ENABEL/public')

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue(), tailwindcss(), vueDevTools()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  // Build directement dans Laravel public/ → même domaine que l'API
  build: {
    outDir: laravelPublic,
    emptyOutDir: false,
  },
})
