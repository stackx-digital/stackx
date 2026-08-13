import { Link, usePage } from "@inertiajs/react";

import { NAV_ITEMS } from "@/config/nav";
import { cn } from "@/lib/utils";

/** Cockpit sidebar — the 5-pillar nav with active state and "soon" markers. */
export function Sidebar() {
    const { url } = usePage();

    return (
        <aside className="hidden w-60 shrink-0 flex-col border-r border-hairline bg-panel md:flex">
            <div className="flex h-14 items-center gap-2 border-b border-hairline px-5">
                <span className="font-display text-lg font-bold tracking-tight text-slate-100">
                    STACK<span className="text-amber">x</span>
                </span>
                <span className="rounded bg-ink px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wider text-muted-foreground">
                    Intel
                </span>
            </div>

            <nav className="flex-1 space-y-1 p-3">
                {NAV_ITEMS.map((item) => {
                    const active =
                        url === item.href || url.startsWith(`${item.href}/`);
                    const Icon = item.icon;
                    return (
                        <Link
                            key={item.href}
                            href={item.href}
                            aria-current={active ? "page" : undefined}
                            className={cn(
                                "group flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors",
                                active
                                    ? "bg-ink text-slate-100"
                                    : "text-muted-foreground hover:bg-ink/60 hover:text-slate-200",
                            )}
                        >
                            <Icon className="size-4 shrink-0" />
                            <span className="flex-1">{item.label}</span>
                            {item.status === "soon" ? (
                                <span className="text-[9px] font-medium uppercase tracking-wider text-muted-foreground/70">
                                    soon
                                </span>
                            ) : (
                                active && (
                                    <span
                                        className="size-1.5 rounded-full bg-amber"
                                        aria-hidden
                                    />
                                )
                            )}
                        </Link>
                    );
                })}
            </nav>

            <div className="border-t border-hairline p-3 text-[10px] text-muted-foreground">
                Internal tool · RM (MYR)
            </div>
        </aside>
    );
}
