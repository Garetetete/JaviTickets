import { defineConfig, devices } from '@playwright/test'

/**
 * E2E del panel admin contra el backend real (debe estar corriendo en
 * VITE_API_URL, p. ej. Docker en http://localhost:8090). El dev server de Vite
 * se levanta automáticamente.
 */
export default defineConfig({
  testDir: './e2e',
  fullyParallel: false,
  workers: 1,
  retries: 1,
  reporter: [['list'], ['html', { open: 'never' }]],
  // Backend en Docker (Windows) responde lento; timeouts holgados para evitar
  // falsos negativos por latencia del API.
  timeout: 90_000,
  expect: { timeout: 20_000 },
  use: {
    baseURL: 'http://localhost:4173',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    // Con DEMO=1: graba video de cada test y abre el navegador en cámara lenta
    // para poder ver los clicks (`DEMO=1 npx playwright test ... --headed`).
    video: process.env.DEMO ? 'on' : 'off',
    launchOptions: process.env.DEMO ? { slowMo: 700 } : {},
  },
  projects: [
    { name: 'setup', testMatch: /auth\.setup\.ts/ },
    {
      name: 'chromium',
      testIgnore: /auth\.setup\.ts/,
      dependencies: ['setup'],
      use: { ...devices['Desktop Chrome'], storageState: 'e2e/.auth/admin.json' },
    },
  ],
  // Se testea contra el BUILD de producción servido con `vite preview`:
  // es instantáneo (sin compilación al vuelo) y estable, a diferencia del dev server.
  webServer: {
    command: 'npm run build && npm run preview -- --port 4173 --strictPort',
    url: 'http://localhost:4173',
    reuseExistingServer: false,
    timeout: 180_000,
  },
})
