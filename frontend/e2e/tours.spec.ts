import { test, expect } from '@playwright/test'

test.describe('Tours — CRUD completo', () => {
  test('crear → editar → eliminar → restaurar', async ({ page }) => {
    const stamp = Date.now()
    const name = `E2E Tour ${stamp}`
    const slug = `e2e-tour-${stamp}`

    // Crear
    await page.goto('/tours')
    await page.getByRole('button', { name: 'Nuevo tour' }).click()
    await expect(page).toHaveURL(/\/tours\/create/)
    await page.getByLabel('Nombre *', { exact: true }).fill(name)
    await page.getByLabel('Slug *', { exact: true }).fill(slug)
    await page.getByLabel('Artista *', { exact: true }).fill('Artista E2E')
    await page.getByRole('button', { name: 'Guardar' }).click()
    await expect(page.getByText('Tour creado')).toBeVisible()
    await expect(page).toHaveURL(/\/tours$/)

    // Buscar la fila creada
    await page.getByRole('textbox', { name: 'Buscar por nombre' }).fill(name)
    const row = page.getByRole('row', { name: new RegExp(name) })
    await expect(row).toBeVisible()

    // Editar
    await row.locator('button:has(.mdi-pencil)').click()
    await expect(page).toHaveURL(/\/tours\/\d+\/edit/)
    await page.getByLabel('Artista *', { exact: true }).fill('Artista Editado')
    await page.getByRole('button', { name: 'Guardar' }).click()
    await expect(page.getByText('Tour actualizado')).toBeVisible()

    // Eliminar (con confirmación)
    await page.getByRole('textbox', { name: 'Buscar por nombre' }).fill(name)
    const row2 = page.getByRole('row', { name: new RegExp(name) })
    await row2.locator('button:has(.mdi-delete)').click()
    await page.getByRole('button', { name: 'Eliminar' }).click()
    await expect(page.getByText('Tour eliminado')).toBeVisible()

    // Restaurar (ver eliminados)
    await page.getByLabel('Mostrar eliminados').check()
    await page.getByRole('textbox', { name: 'Buscar por nombre' }).fill(name)
    const row3 = page.getByRole('row', { name: new RegExp(name) })
    await expect(row3).toBeVisible()
    await row3.locator('button:has(.mdi-restore)').click()
    await expect(page.getByText('Tour restaurado')).toBeVisible()
  })

  test('validación: crear sin campos requeridos muestra errores', async ({ page }) => {
    await page.goto('/tours/create')
    await page.getByRole('button', { name: 'Guardar' }).click()
    // El backend responde 422 y los errores se mapean a los campos
    await expect(page.getByText(/El campo|required|requerido/i).first()).toBeVisible()
  })
})
