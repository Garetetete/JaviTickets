import client from './client'
import type { ValidateResult } from '@/types/api'

/** Validación en puerta: POST /tickets/validate (guard admin, rol gate|admin). */
export function validateTicket(qrToken: string, eventId?: number) {
  return client
    .post<ValidateResult>('/tickets/validate', { qr_token: qrToken, event_id: eventId })
    .then((r) => r.data)
}
