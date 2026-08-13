import { Link } from "@inertiajs/react";

import { buttonVariants } from "@/Components/ui/Button";

/** Top bar — session email + sign out. */
export function Topbar({ email }: { email: string | null }) {
    return (
        <header className="flex h-14 items-center justify-between border-b border-hairline bg-panel/60 px-5 backdrop-blur">
            <div className="text-xs uppercase tracking-wider text-muted-foreground">
                Performance Cockpit
            </div>
            <div className="flex items-center gap-3">
                {email && (
                    <span className="hidden text-xs text-muted-foreground sm:inline">
                        {email}
                    </span>
                )}
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    className={buttonVariants({ variant: "ghost", size: "sm" })}
                >
                    Sign out
                </Link>
            </div>
        </header>
    );
}
