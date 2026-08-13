import { usePage } from "@inertiajs/react";
import { type PropsWithChildren } from "react";

import { Sidebar } from "@/Components/shell/Sidebar";
import { Topbar } from "@/Components/shell/Topbar";

/**
 * Authenticated cockpit shell. Access is gated server-side (auth +
 * allowlisted middleware); this just lays out the sidebar, topbar, and page.
 */
export default function AppLayout({ children }: PropsWithChildren) {
    const { auth } = usePage().props;

    return (
        <div className="flex min-h-screen bg-ink">
            <Sidebar />
            <div className="flex min-w-0 flex-1 flex-col">
                <Topbar email={auth.user?.email ?? null} />
                <main className="flex-1 overflow-y-auto p-5 md:p-8">
                    {children}
                </main>
            </div>
        </div>
    );
}
