import { test as setup, expect } from '@playwright/test'
import fs from 'node:fs'
import path from 'node:path'
import { ADMIN, GATE } from './helpers'

const API = process.env.VITE_API_URL ?? 'http://localhost:8090/api/v1'
const ORIGIN = 'http://localhost:4173'
// Playwright corre con cwd = frontend/; ruta relativa (el proyecto es ESM, sin __dirname).
const AUTH_DIR = path.resolve('e2e/.auth')

/**
 * Autenticación una sola vez vía API: guarda el JWT en un storageState
 * (localStorage `admin_token`) que reutilizan los demás proyectos. Evita
 * loguear por UI en cada test (lento + choca con el throttle del login).
 */
async function saveState(
  request: import('@playwright/test').APIRequestContext,
  creds: { email: string; password: string },
  file: string,
) {
  const res = await request.post(`${API}/admin/login`, { data: creds })
  expect(res.ok(), `login ${creds.email} -> ${res.status()}`).toBeTruthy()
  const token = (await res.json()).access_token as string
  const state = {
    cookies: [],
    origins: [{ origin: ORIGIN, localStorage: [{ name: 'admin_token', value: token }] }],
  }
  fs.mkdirSync(AUTH_DIR, { recursive: true })
  fs.writeFileSync(file, JSON.stringify(state, null, 2))
}

setup('autenticar admin', async ({ request }) => {
  await saveState(request, ADMIN, path.join(AUTH_DIR, 'admin.json'))
})

setup('autenticar gate', async ({ request }) => {
  await saveState(request, GATE, path.join(AUTH_DIR, 'gate.json'))
})
