import { useEffect, useRef, type ReactNode } from 'react'
import { Spinner } from './Feedback'

/**
 * Modal confirmation built on <dialog>, so focus trapping and Escape handling
 * come from the browser.
 */
export function ConfirmDialog({
  open,
  title,
  children,
  confirmLabel,
  busy,
  onConfirm,
  onCancel,
}: {
  open: boolean
  title: string
  children?: ReactNode
  confirmLabel: string
  busy?: boolean
  onConfirm: () => void
  onCancel: () => void
}) {
  const ref = useRef<HTMLDialogElement>(null)

  useEffect(() => {
    const dialog = ref.current
    if (!dialog) return
    if (open && !dialog.open) dialog.showModal()
    if (!open && dialog.open) dialog.close()
  }, [open])

  return (
    <dialog
      ref={ref}
      onCancel={(event) => {
        event.preventDefault()
        if (!busy) onCancel()
      }}
      className="m-auto w-[calc(100%-2rem)] max-w-md rounded-xl border border-slate-200 bg-white p-0 shadow-xl backdrop:bg-slate-900/40"
    >
      <form
        method="dialog"
        onSubmit={(event) => {
          event.preventDefault()
          onConfirm()
        }}
      >
        <div className="px-6 pt-6">
          <h2 className="text-base font-semibold text-slate-900">{title}</h2>
          <div className="mt-2 text-sm text-slate-600">{children}</div>
        </div>
        <div className="mt-6 flex justify-end gap-2 rounded-b-xl border-t border-slate-100 bg-slate-50 px-6 py-3">
          <button type="button" className="btn-secondary" onClick={onCancel} disabled={busy}>
            Cancel
          </button>
          <button type="submit" className="btn-primary" disabled={busy} autoFocus>
            {busy && <Spinner className="size-4 text-white" />}
            {confirmLabel}
          </button>
        </div>
      </form>
    </dialog>
  )
}
