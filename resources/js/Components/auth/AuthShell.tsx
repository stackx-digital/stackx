import { Head } from "@inertiajs/react";
import { type PropsWithChildren, type ReactNode } from "react";

/**
 * Centered card used by every unauthenticated screen (login, register, verify,
 * password reset). Keeps the cockpit brand + panel styling in one place.
 */
export function AuthShell({
    title,
    heading,
    subheading,
    status,
    children,
    footer,
}: PropsWithChildren<{
    title: string;
    heading?: string;
    subheading?: string;
    status?: string;
    footer?: ReactNode;
}>) {
    return (
        <>
            <Head title={title} />
            <main className="flex min-h-screen items-center justify-center bg-ink px-6 py-12">
                <div className="w-full max-w-sm">
                    <div className="mb-8 text-center">
                        <h1 className="font-display text-2xl font-bold tracking-tight text-slate-100">
                            STACK<span className="text-amber">x</span>
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {subheading ?? "Ad Intelligence"}
                        </p>
                    </div>

                    {status && (
                        <div className="mb-4 rounded-md border border-winner/30 bg-winner/10 px-4 py-3 text-sm text-winner">
                            {status}
                        </div>
                    )}

                    <div className="rounded-lg border border-hairline bg-panel p-6">
                        {heading && (
                            <h2 className="mb-4 font-display text-base font-semibold text-slate-100">
                                {heading}
                            </h2>
                        )}
                        {children}
                    </div>

                    {footer && (
                        <div className="mt-4 text-center text-xs text-muted-foreground">
                            {footer}
                        </div>
                    )}
                </div>
            </main>
        </>
    );
}

/** Shared field styling for the auth forms. */
export function Field({
    id,
    label,
    error,
    children,
}: PropsWithChildren<{ id: string; label: string; error?: string }>) {
    return (
        <div className="mb-3">
            <label
                htmlFor={id}
                className="mb-1.5 block text-xs font-medium uppercase tracking-wide text-muted-foreground"
            >
                {label}
            </label>
            {children}
            {error && <p className="mt-1 text-xs text-cut">{error}</p>}
        </div>
    );
}

export const inputClass =
    "w-full rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-slate-100 placeholder:text-muted-foreground/60 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-amber/40";
