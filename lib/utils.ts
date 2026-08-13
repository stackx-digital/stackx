import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

/**
 * Format a value as Malaysian Ringgit (RM). Currency across the app is MYR
 * (§6). Numbers render in mono/tabular at the component level.
 */
export function formatRM(
  value: number | null | undefined,
  opts: { maximumFractionDigits?: number } = {},
): string {
  if (value === null || value === undefined || Number.isNaN(value)) return "—";
  return new Intl.NumberFormat("en-MY", {
    style: "currency",
    currency: "MYR",
    currencyDisplay: "narrowSymbol",
    maximumFractionDigits: opts.maximumFractionDigits ?? 2,
  }).format(value);
}

/** Compact number formatting for impressions/reach etc. */
export function formatCompact(value: number | null | undefined): string {
  if (value === null || value === undefined || Number.isNaN(value)) return "—";
  return new Intl.NumberFormat("en-MY", {
    notation: "compact",
    maximumFractionDigits: 1,
  }).format(value);
}
