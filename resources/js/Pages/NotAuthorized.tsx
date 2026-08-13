import { Head, Link } from "@inertiajs/react";

import { buttonVariants } from "@/Components/ui/Button";

/**
 * Landing for an authenticated user whose email is not on the STACKx
 * allowlist. They hold a valid session but have no app access.
 */
export default function NotAuthorized() {
    return (
        <>
            <Head title="Not authorised" />
            <main className="flex min-h-screen items-center justify-center bg-ink px-6">
                <div className="w-full max-w-sm text-center">
                    <h1 className="font-display text-xl font-bold text-slate-100">
                        Not authorised
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Your account isn&apos;t on the STACKx team allowlist. Ask
                        an admin to add your email, then sign in again.
                    </p>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className={`mt-6 w-full ${buttonVariants({ variant: "outline" })}`}
                    >
                        Sign out
                    </Link>
                </div>
            </main>
        </>
    );
}
