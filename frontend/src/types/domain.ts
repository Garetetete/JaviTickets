/**
 * Tipos de dominio derivados de los modelos de la API (ver docs/specs/db/schema.md
 * y docs/specs/frontend/01-architecture.md §2).
 */

export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface PaginationParams {
  page?: number
  per_page?: number
  sort_by?: string
  sort_dir?: 'asc' | 'desc'
}

export type OrderStatus =
  | 'pending_payment'
  | 'pending_verification'
  | 'verified'
  | 'rejected'
  | 'expired'

export type TicketStatus = 'active' | 'used' | 'void' | 'expired' | 'issued'

export type SeatingType = 'general' | 'seated'

export interface Customer {
  id?: number
  first_name?: string
  last_name?: string
  full_name?: string
  email: string
  phone?: string | null
  document_type?: string | null
  document_number?: string | null
  address?: string | null
  city_residence?: string | null
}

export interface Tour {
  id: number
  name: string
  slug?: string
  artist_name?: string
  description?: string | null
  owner_name?: string | null
  owner_email?: string | null
  is_active?: boolean
  created_at?: string
  deleted_at: string | null
}

export interface Event {
  id: number
  tour_id: number
  tour?: Tour
  name: string
  description?: string | null
  date: string
  city: string
  capacity: number
  seating_type: SeatingType
  created_at?: string
  deleted_at: string | null
}

export interface TicketType {
  id: number
  event_id: number
  event?: Event
  name: string
  slug?: string
  price: number
  currency: string
  quota: number | null
  created_at?: string
  deleted_at: string | null
}

export interface Ticket {
  id: number
  code: string
  status: TicketStatus
  qr_token?: string
  order_id?: number
  event_id?: number
  ticket_type_id?: number
  ticket_type?: TicketType
  event?: Event
  customer?: Customer
  section: string | null
  seat: string | null
  used_at?: string | null
  created_at?: string
  deleted_at: string | null
}

export interface PaymentReceipt {
  id: number
  order_id: number
  original_name?: string | null
  mime_type?: string | null
  uploaded_by?: string | null
  created_at?: string
}

export interface Order {
  id: number
  external_reference: string
  payment_status: OrderStatus
  amount: number
  currency: string
  quantity: number
  payment_method?: string | null
  customer: Customer
  event?: Event
  tickets?: Ticket[]
  receipts?: PaymentReceipt[]
  created_at?: string
  deleted_at: string | null
}

export interface Seat {
  id: number
  event_id: number
  section: string
  row: string | null
  seat_number: string
  label?: string
  is_active?: boolean
  status: 'free' | 'occupied'
  deleted_at?: string | null
}

export interface ScanLog {
  id: number
  ticket_id: number | null
  code?: string | null
  event_id?: number | null
  result: string
  scanned_by: number | null
  gate_user?: AdminUser | null
  ip?: string | null
  device?: string | null
  created_at: string
}

export interface AuditLog {
  id: number
  admin_user_id: number | null
  admin_user?: AdminUser | null
  action: string
  resource: string
  resource_id: number | null
  payload: Record<string, unknown>
  created_at: string
}

export interface ApiClient {
  id: number
  client_id: string
  name: string
  scopes: string[]
  webhook_url?: string | null
  webhook_secret_hint?: string
  is_active?: boolean
  deleted_at: string | null
}

export interface AdminUser {
  id: number
  name: string
  email: string
  role: 'admin' | 'gate'
  event_id?: number | null
  is_active?: boolean
  deleted_at: string | null
}
