import { expect, type Page } from '@playwright/test'

export const ADMIN = { email: 'admin@qrtickets.test', password: 'password' }
export const GATE = { email: 'gate@qrtickets.test', password: 'password' }

/** Inicia sesión por la UI y espera la redirección por rol. */
export async function login(page: Page, who = ADMIN) {
  await page.goto('/login')
  await page.getByLabel('Email', { exact: true }).fill(who.email)
  await page.getByLabel('Contraseña', { exact: true }).fill(who.password)
  await page.getByRole('button', { name: 'Ingresar' }).click()
  // admin -> /dashboard, gate -> /validate
  await expect(page).toHaveURL(/\/(dashboard|validate)/, { timeout: 20_000 })
}

/** Captura errores de consola del navegador para detectar fallos silenciosos. */
export function collectConsoleErrors(page: Page): string[] {
  const errors: string[] = []
  page.on('console', (msg) => {
    if (msg.type() === 'error') errors.push(msg.text())
  })
  page.on('pageerror', (err) => errors.push(String(err)))
  return errors
}

/**
 * Abre un v-select de Vuetify y elige una opción. El click directo en el input
 * lo intercepta un div interno de Vuetify, por eso se usa `force`.
 */
export async function selectOption(page: Page, label: string, optionText?: string) {
  await page.getByLabel(label, { exact: true }).click({ force: true })
  const option = optionText
    ? page.getByRole('option', { name: optionText })
    : page.getByRole('option').first()
  await option.click()
}

/** Errores de consola ruidosos pero inofensivos que ignoramos. */
export function realErrors(errors: string[]): string[] {
  return errors.filter(
    (e) =>
      !/favicon/i.test(e) &&
      !/Failed to load resource.*favicon/i.test(e) &&
      !/\[Vue warn\]/i.test(e),
  )
}
