import { Link, usePage } from "@inertiajs/react";
import { Rocket, X } from "lucide-react";
import { type PropsWithChildren } from "react";

import { Sidebar } from "@/Components/shell/Sidebar";
import { Topbar } from "@/Components/shell/Topbar";

/**
 * Authenticated cockpit shell. Access is gated server-side (auth + verified
 * middleware, scoped to the user's org); this just lays out the sidebar,
 * topbar, and page. Shows a "finish setup" banner until onboarding is done.
 */
export default function AppLayout({ children }: PropsWithChildren) {
    const { props, url } = usePage();
    const { auth, onboarding } = props;

    const showSetup = !onboarding?.completed && !url.startsWith("/welcome");

    return (
        <div className="flex min-h-screen bg-ink">
            <Sidebar />
            <div className="flex min-w-0 flex-1 flex-col">
                <Topbar email={auth.user?.email ?? null} />
                {showSetup && <SetupBanner />}
                <main className="flex-1 overflow-y-auto p-5 md:p-8">
                    {children}
                </main>
            </div>
        </div>
    );
}

function SetupBanner() {
    return (
        <div className="flex items-center gap-3 border-b border-amber/20 bg-amber/10 px-5 py-2.5 text-sm">
            <Rocket className="size-4 shrink-0 text-amber" />
            <span className="flex-1 text-slate-200">
                Finish setting up your workspace — add an API key and load some
                ads.
            </span>
            <Link
                href="/welcome"
                className="rounded-md bg-amber px-3 py-1 text-xs font-semibold text-ink hover:bg-amber/90"
            >
                Continue setup
            </Link>
            <Link
                href="/welcome/complete"
                method="post"
                as="button"
                aria-label="Dismiss setup"
                className="rounded p-1 text-muted-foreground hover:text-slate-200"
            >
                <X className="size-4" />
            </Link>
        </div>
    );
}
