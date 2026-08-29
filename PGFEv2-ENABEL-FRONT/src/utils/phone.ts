/**
 * Normalize common DRC phone formats to +243XXXXXXXXX.
 * Accepts: +243812345678, 243 812 345 678, 0812 345 678
 * Returns null for empty input; returns cleaned string if format is unrecognized.
 */
export function normalizeDrcPhone(phone: string | null | undefined): string | null {
  const raw = (phone || '').trim()
  if (!raw) return null

  const clean = raw.replace(/[\s\-().]/g, '')

  if (/^\+243\d{9}$/.test(clean)) return clean
  if (/^243\d{9}$/.test(clean)) return `+${clean}`
  if (/^0\d{9}$/.test(clean)) return `+243${clean.slice(1)}`

  return clean
}

export const DRC_PHONE_PLACEHOLDER = '+243812345678 ou 0812345678'
export const DRC_PHONE_HINT =
  'Optionnel. Formats acceptés : +243XXXXXXXXX ou 0XXXXXXXXX (espaces autorisés).'
