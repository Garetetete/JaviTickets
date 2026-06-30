import type { AxiosError } from 'axios'

/** Errores de validación 422 normalizados: campo -> mensajes. */
export type ValidationErrors = Record<string, string[]>

/** AxiosError extendido por el interceptor con los errores de validación 422. */
export interface ApiError extends AxiosError<{ message?: string; errors?: ValidationErrors }> {
  validationErrors?: ValidationErrors
}

/** Payloads de creación/edición por recurso. */
export interface TourPayload {
  name: string
  slug?: string
  artist_name?: string
  description?: string | null
  owner_name?: string | null
  owner_email?: string | null
  is_active?: boolean
}

export interface EventPayload {
  tour_id: number | null
  name: string
  description?: string | null
  date: string
  city: string
  capacity: number | null
  seating_type: 'general' | 'seated'
}

export interface TicketTypePayload {
  event_id: number | null
  name: string
  price: number
  currency: string
  quota: number | null
}

export interface SeatPayload {
  section: string
  row?: string | null
  seat_number: string
}

export interface SeatRangePayload {
  section: string
  row_from: string
  row_to: string
  seats_per_row: number
  prefix?: string
}

export interface ApiClientPayload {
  name: string
  client_id: string
  scopes: string[]
  webhook_url?: string | null
}

export interface UserPayload {
  name: string
  email: string
  password?: string
  password_confirmation?: string
  role: 'admin' | 'gate'
  event_id?: number | null
}

export interface DashboardMetrics {
  total_tickets: number
  tickets_by_status: Record<string, number>
  total_orders: number
  orders_by_status: Record<string, number>
  revenue: { total: number; currency: string }
  occupancy_rate: number
  recent_scans: Array<Record<string, unknown>>
}

export interface ValidateResult {
  valid: boolean
  result?: string
  reason?: string
  ticket?: {
    code: string
    status?: string
    ticket_type?: string
    holder_name?: string
    event?: string
    section?: string | null
    seat?: string | null
    used_at?: string | null
  }
}
