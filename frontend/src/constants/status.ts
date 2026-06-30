/** Mapas de estado -> { label, color } usados por los chips en toda la app. */

export const ORDER_STATUS_MAP: Record<string, { label: string; color: string }> = {
  pending_payment: { label: 'Pago pendiente', color: 'warning' },
  pending_verification: { label: 'Por verificar', color: 'amber-darken-2' },
  verified: { label: 'Verificada', color: 'success' },
  rejected: { label: 'Rechazada', color: 'error' },
  expired: { label: 'Expirada', color: 'grey' },
}

export const TICKET_STATUS_MAP: Record<string, { label: string; color: string }> = {
  active: { label: 'Activo', color: 'success' },
  issued: { label: 'Emitido', color: 'success' },
  used: { label: 'Usado', color: 'info' },
  void: { label: 'Anulado', color: 'grey' },
  expired: { label: 'Expirado', color: 'warning' },
}

export const SCAN_RESULT_MAP: Record<string, { label: string; color: string }> = {
  valid: { label: 'Válido', color: 'success' },
  already_used: { label: 'Ya usado', color: 'warning' },
  invalid_signature: { label: 'Firma inválida', color: 'error' },
  not_found: { label: 'No encontrado', color: 'error' },
  wrong_event: { label: 'Evento incorrecto', color: 'error' },
  void: { label: 'Anulado', color: 'grey' },
  not_paid: { label: 'No pagado', color: 'error' },
}

export const SEATING_TYPE_MAP: Record<string, { label: string; color: string }> = {
  general: { label: 'General', color: 'blue-grey' },
  seated: { label: 'Numerado', color: 'indigo' },
}

export const ROLE_MAP: Record<string, { label: string; color: string }> = {
  admin: { label: 'Admin', color: 'primary' },
  gate: { label: 'Puerta', color: 'success' },
}

/** Motivos de validación en puerta, traducidos para el operador. */
export const VALIDATE_REASON_LABELS: Record<string, string> = {
  already_used: 'El ticket ya fue usado',
  not_found: 'Ticket no encontrado',
  void: 'Ticket anulado',
  expired: 'Ticket expirado',
  invalid: 'QR inválido o firma incorrecta',
  wrong_event: 'El ticket no pertenece a este evento',
  not_paid: 'La orden no está pagada',
}
