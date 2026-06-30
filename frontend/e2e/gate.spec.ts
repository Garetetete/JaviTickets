import { test, expect } from '@playwright/test'
import { selectOption } from './helpers'

test.describe('Gate — usuario de puerta', () => {
  test.use({ storageState: 'e2e/.auth/gate.json' })

  test('ve la pantalla de validación; el botón está deshabilitado sin token', async ({ page }) => {
    await page.goto('/validate')
    await expect(page).toHaveURL(/\/validate/)
    await expect(page.getByRole('button', { name: 'Validar' })).toBeDisabled()
  })
})

test.describe('Gate — validación (admin)', () => {
  test.use({ storageState: 'e2e/.auth/admin.json' })

  test('un QR inválido muestra resultado rojo', async ({ page }) => {
    await page.goto('/validate')
    await selectOption(page, 'Evento a validar')
    await page.getByRole('textbox', { name: /QR token/ }).fill('token-invalido-xyz')
    await page.getByRole('button', { name: 'Validar' }).click()
    await expect(page.getByText(/INVÁLIDO|QR inválido/i).first()).toBeVisible()
  })
})
