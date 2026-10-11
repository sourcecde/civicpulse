import { isAxiosError } from 'axios'

export interface ApiError {
  /** HTTP status, or 0 when the API could not be reached. */
  status: number
  message: string
  /** Laravel validation errors keyed by field (422 only). */
  fieldErrors: Record<string, string[]>
}

const STATUS_MESSAGES: Record<number, string> = {
  401: 'Your session has expired. Please sign in again.',
  403: 'You are not allowed to perform this action.',
  404: 'The requested record was not found.',
  429: 'Too many attempts. Please wait a minute and try again.',
}

/** Normalises Axios/Laravel errors into one shape the UI can render. */
export function toApiError(error: unknown): ApiError {
  if (isAxiosError(error)) {
    if (!error.response) {
      return {
        status: 0,
        message: 'Cannot reach the CivicPulse API. Check that the backend is running and VITE_API_BASE_URL is correct.',
        fieldErrors: {},
      }
    }

    const { status, data } = error.response
    const body = (data ?? {}) as { message?: unknown; errors?: unknown }
    const fieldErrors = isFieldErrors(body.errors) ? body.errors : {}

    let message = STATUS_MESSAGES[status]
    if (status === 422) {
      message = firstFieldError(fieldErrors) ?? (typeof body.message === 'string' ? body.message : 'The request was invalid.')
    } else if (!message) {
      message = status >= 500 ? 'The server encountered an error. Please try again.' : `Request failed (${status}).`
    }

    return { status, message, fieldErrors }
  }

  return { status: 0, message: error instanceof Error ? error.message : 'Unexpected error.', fieldErrors: {} }
}

function isFieldErrors(value: unknown): value is Record<string, string[]> {
  return typeof value === 'object' && value !== null && !Array.isArray(value)
}

function firstFieldError(fieldErrors: Record<string, string[]>): string | undefined {
  return Object.values(fieldErrors)[0]?.[0]
}
