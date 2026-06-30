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
  slug: string
  name: string
  country?: string | null
  city?: string | null
  venue?: string | null
  event_date: string | null
  capacity: number | null
  seating_type: 'general' | 'seated'
  is_active?: boolean
}

export interface TicketTypePayload {
  tour_id: number | null
  event_id?: number | null
  slug: string
  name: string
  price: number
  currency: string
  quota: number | null
  is_active?: boolean
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
  webhook_secret?: string | null
  is_active?: boolean
}

export interface UserPayload {
  name: string
  email: string
  password?: string
  password_confirmation?: string
  role: 'admin' | 'gate'
  event_id?: number | null
}

/** Forma REAL de GET /admin/dashboard/metrics?event_id=. */
export interface DashboardMetrics {
  overview: {
    event_id: number
    capacity: number
    issued: number
    used: number
    available: number
    no_show_rate: number
  }
  sales_by_type: Array<{
    ticket_type_id?: number
    name?: string
    sold?: number
    revenue?: number
    [k: string]: unknown
  }>
  revenue: number
  scan_results: Array<{ result?: string; total?: number; [k: string]: unknown }>
}

/** Forma REAL de POST /tickets/validate: { result, ticket }. */
export interface ValidateResult {
  result: string
  ticket: {
    code: string
    holder_name?: string | null
    ticket_type?: string | null
    event?: string | null
    section?: string | null
    seat?: string | null
    used_at?: string | null
  } | null
}
