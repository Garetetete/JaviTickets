import { fileURLToPath, URL } from 'node:url'
/// <reference types="vitest/config" />
import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import vuetify from 'vite-plugin-vuetify'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    // Autoimporta componentes de Vuetify y estilos bajo demanda.
    vuetify({ autoImport: true }),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    port: 5173,
    host: true,
  },
  test: {
    environment: 'jsdom',
    globals: true,
    include: ['src/**/*.{test,spec}.ts'],
    coverage: {
      provider: 'v8',
      // F-10: cobertura de la lógica reutilizable (composables y stores).
      include: ['src/composables/**', 'src/stores/**'],
      reporter: ['text', 'html'],
    },
  },
})
