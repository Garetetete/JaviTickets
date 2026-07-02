import { test, expect } from '@playwright/test'
import { login, ADMIN, GATE } from './helpers'

// Estos tests ejercitan el flujo de login/logout: requieren sesión limpia.
test.use({ storageState: { cookies: [], origins: [] } })

test.describe('Autenticación', () => {
  test('ruta protegida sin sesión redirige a /login', async ({ page }) => {
    await page.context().clearCookies()
    await page.goto('/tours')
    await expect(page).toHaveURL(/\/login/)
  })

  test('login con credenciales incorrectas muestra error', async ({ page }) => {
    await page.goto('/login')
    await page.getByLabel('Email', { exact: true }).fill('admin@qrtickets.test')
    await page.getByLabel('Contraseña', { exact: true }).fill('mala-clave')
    await page.getByRole('button', { name: 'Ingresar' }).click()
    await expect(page.getByText(/Credenciales incorrectas/i)).toBeVisible()
    await expect(page).toHaveURL(/\/login/)
  })

  test('login admin redirige al dashboard', async ({ page }) => {
    await login(page, ADMIN)
    await expect(page).toHaveURL(/\/dashboard/)
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible()
  })

  test('login gate redirige a /validate', async ({ page }) => {
    await login(page, GATE)
    await expect(page).toHaveURL(/\/validate/)
  })

  test('logout limpia la sesión', async ({ page }) => {
    await login(page, ADMIN)
    await page.locator('header').getByRole('button').last().click() // botón logout
    await expect(page).toHaveURL(/\/login/)
    // volver a una ruta protegida debe re-redirigir
    await page.goto('/tours')
    await expect(page).toHaveURL(/\/login/)
  })

  test('sesión persiste tras recargar', async ({ page }) => {
    await login(page, ADMIN)
    await page.reload()
    await expect(page).not.toHaveURL(/\/login/)
  })
})
