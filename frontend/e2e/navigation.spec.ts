import { test, expect } from '@playwright/test'
import { collectConsoleErrors, realErrors } from './helpers'

const LIST_ROUTES = [
  '/dashboard',
  '/tours',
  '/events',
  '/ticket-types',
  '/orders',
  '/tickets',
  '/scans',
  '/audit-logs',
  '/api-clients',
  '/users',
]

const FORM_ROUTES = [
  '/tours/create',
  '/events/create',
  '/ticket-types/create',
  '/api-clients/create',
  '/users/create',
]

test.describe('Navegación admin', () => {
  test('el menú lateral lista todas las secciones', async ({ page }) => {
    await page.goto('/dashboard')
    const nav = page.locator('nav')
    for (const label of [
      'Dashboard',
      'Tours',
      'Eventos',
      'Tipos de Ticket',
      'Órdenes',
      'Tickets',
      'Escaneos',
      'Logs de auditoría',
      'API Clients',
      'Usuarios',
    ]) {
      await expect(nav.getByText(label, { exact: true })).toBeVisible()
    }
  })

  for (const route of [...LIST_ROUTES, ...FORM_ROUTES]) {
    test(`${route} renderiza sin errores ni stubs`, async ({ page }) => {
      const errors = collectConsoleErrors(page)
      await page.goto(route)
      await page.waitForLoadState('networkidle')
      await expect(page).toHaveURL(new RegExp(route.replace(/\//g, '\\/')))
      // No debe quedar ningún stub "Pendiente:"
      await expect(page.getByText(/Pendiente:/)).toHaveCount(0)
      // No debe haber errores de consola reales
      expect(realErrors(errors), `errores de consola en ${route}`).toEqual([])
    })
  }
})
