import { type ClassValue, clsx } from 'clsx'
import { twMerge } from 'tailwind-merge'

// cn() merges Tailwind classes safely — clsx handles conditionals and arrays,
// twMerge resolves conflicts (e.g. "p-2 p-4" → "p-4"). Used by all shadcn-vue components.
export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}
