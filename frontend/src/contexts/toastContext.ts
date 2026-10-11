import { createContext } from 'react'

export type ToastTone = 'success' | 'error' | 'info'

export interface Toast {
  id: number
  tone: ToastTone
  title: string
  description?: string
}

export interface ToastContextValue {
  notify: (tone: ToastTone, title: string, description?: string) => void
}

export const ToastContext = createContext<ToastContextValue | null>(null)
