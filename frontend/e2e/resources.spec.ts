import { test, expect } from '@playwright/test'
import { selectOption } from './helpers'

test.describe('Recursos CRUD', () => {
  test('crear evento', async ({ page }) => {
    const stamp = Date.now()
    await page.goto('/events/create')
    await selectOption(page, 'Tour *')
    await page.getByLabel('Nombre *', { exact: true }).fill(`E2E Evento ${stamp}`)
    await page.getByLabel('Slug *', { exact: true }).fill(`e2e-evento-${stamp}`)
    await page.getByLabel('Ciudad', { exact: true }).fill('Lima')
    await page.getByLabel('Capacidad *', { exact: true }).fill('100')
    await page.getByRole('button', { name: 'Guardar' }).click()
    await expect(page.getByText('Evento creado')).toBeVisible()
    await expect(page).toHaveURL(/\/events$/)
  })

  test('crear tipo de ticket', async ({ page }) => {
    const stamp = Date.now()
    await page.goto('/ticket-types/create')
    await selectOption(page, 'Tour *')
    await page.getByLabel('Nombre *', { exact: true }).fill(`E2E Tipo ${stamp}`)
    await page.getByLabel('Slug *', { exact: true }).fill(`e2e-tipo-${stamp}`)
    await page.getByLabel('Precio *', { exact: true }).fill('50')
    await page.getByRole('button', { name: 'Guardar' }).click()
    await expect(page.getByText(/Tipo de ticket creado/)).toBeVisible()
    await expect(page).toHaveURL(/\/ticket-types$/)
  })

  test('crear usuario', async ({ page }) => {
    const stamp = Date.now()
    await page.goto('/users/create')
    await page.getByLabel('Nombre *', { exact: true }).fill(`E2E User ${stamp}`)
    await page.getByLabel('Email *', { exact: true }).fill(`e2e_user_${stamp}@qrtickets.test`)
    await selectOption(page, 'Rol *', 'admin')
    await page.getByLabel('Contraseña *', { exact: true }).fill('password123')
    await page.getByLabel('Confirmar contraseña *', { exact: true }).fill('password123')
    await page.getByRole('button', { name: 'Guardar' }).click()
    await expect(page.getByText(/creado/i)).toBeVisible()
  })

  test('crear API client revela el secret una vez', async ({ page }) => {
    const stamp = Date.now()
    await page.goto('/api-clients/create')
    await page.getByLabel('Nombre *', { exact: true }).fill(`E2E Client ${stamp}`)
    await page.getByLabel('client_id *', { exact: true }).fill(`e2e-client-${stamp}`)
    await selectOption(page, 'Scopes *', 'tickets:read')
    await page.keyboard.press('Escape')
    await page.getByRole('button', { name: 'Guardar' }).click()
    await expect(page.getByText(/no se volverá a mostrar|no podrás ver/i)).toBeVisible()
  })
})
