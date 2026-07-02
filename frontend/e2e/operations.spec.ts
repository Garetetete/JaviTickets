import { test, expect } from '@playwright/test'
import { selectOption } from './helpers'

test.describe('Operaciones y listados', () => {
  test('dashboard muestra tarjetas de métricas', async ({ page }) => {
    await page.goto('/dashboard')
    // El dashboard hace 2 llamadas secuenciales (eventos + métricas) al backend.
    await expect(page.getByText('Capacidad')).toBeVisible({ timeout: 25_000 })
    await expect(page.getByText('Emitidos')).toBeVisible()
    await expect(page.getByText('Usados')).toBeVisible()
  })

  test('órdenes: el listado se renderiza', async ({ page }) => {
    await page.goto('/orders')
    await expect(page.getByRole('heading', { name: /Órdenes|Ordenes/ })).toBeVisible()
    // Tabla o estado vacío, sin errores
    await expect(page.getByText(/Pendiente:/)).toHaveCount(0)
  })

  test('tickets: exportar CSV descarga un archivo', async ({ page }) => {
    await page.goto('/tickets')
    const [download] = await Promise.all([
      page.waitForEvent('download'),
      page.getByRole('button', { name: /Exportar CSV/i }).click(),
    ])
    expect(download.suggestedFilename()).toMatch(/\.csv$/)
  })

  test('scans: sin evento muestra estado guía; con evento carga', async ({ page }) => {
    await page.goto('/scans')
    await expect(page.getByText(/selecciona|evento/i).first()).toBeVisible()
    await selectOption(page, 'Evento')
    // No debe romperse al elegir evento
    await expect(page.getByText(/Pendiente:/)).toHaveCount(0)
  })

  test('audit logs: el listado se renderiza', async ({ page }) => {
    await page.goto('/audit-logs')
    await expect(page.getByText(/Pendiente:/)).toHaveCount(0)
    // Debe existir la tabla de logs (hay actividad por las acciones admin)
    await expect(page.locator('table')).toBeVisible()
  })
})
