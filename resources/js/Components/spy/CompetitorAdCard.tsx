import { ExternalLink, CalendarClock } from "lucide-react";

import { cn } from "@/lib/utils";

import { type CompetitorAd } from "./types";

/** A single competitor ad. days_running is emphasized — long-running ads are
 * the proven winners worth studying. */
export function CompetitorAdCard({
    ad,
    isTop,
}: {
    ad: CompetitorAd;
    isTop?: boolean;
}) {
    return (
        <div
            className={cn(
                "flex flex-col rounded-lg border bg-panel p-4",
                isTop ? "border-amber/40" : "border-hairline",
            )}
        >
            <div className="mb-3 flex items-center justify-between gap-2">
                <span
                    className={cn(
                        "inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider",
                        ad.isActive
                            ? "bg-winner/15 text-winner"
                            : "bg-hairline/50 text-muted-foreground",
                    )}
                >
                    {ad.isActive ? "Active" : "Stopped"}
                </span>
                <span
                    className="tabular inline-flex items-center gap-1 text-xs text-slate-200"
                    title="Days running (longest-running = likely winner)"
                >
                    <CalendarClock className="size-3.5 text-amber" />
                    {ad.daysRunning}d
                </span>
            </div>

            <p className="line-clamp-4 flex-1 text-sm text-slate-200">
                {ad.body ?? "— no ad copy —"}
            </p>

            <div className="mt-3 flex items-center justify-between text-[11px] text-muted-foreground">
                <span>
                    {ad.firstSeen ?? "—"} → {ad.lastSeen ?? "—"}
                </span>
                {ad.snapshotUrl && (
                    <a
                        href={ad.snapshotUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-1 text-neutral hover:text-slate-200"
                    >
                        Ad Library
                        <ExternalLink className="size-3" />
                    </a>
                )}
            </div>
        </div>
    );
}
