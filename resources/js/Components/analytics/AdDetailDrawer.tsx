import { X } from "lucide-react";
import { useEffect, useState } from "react";

import { ActionBadge } from "@/Components/ActionBadge";
import { ScoreMeter } from "@/Components/ScoreMeter";
import { formatRM } from "@/lib/utils";

import { Sparkline } from "./Sparkline";
import { type AdDetail } from "./types";

/**
 * Slide-over drawer with full metrics, the deterministic score, and the daily
 * trend for one ad (§4). Fetches on open; closes on Esc or backdrop click.
 */
export function AdDetailDrawer({
    adId,
    onClose,
}: {
    adId: number | null;
    onClose: () => void;
}) {
    const [detail, setDetail] = useState<AdDetail | null>(null);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (adId === null) return;
        setDetail(null);
        setLoading(true);
        window.axios
            .get<AdDetail>(`/analytics/ads/${adId}`)
            .then((r) => setDetail(r.data))
            .finally(() => setLoading(false));
    }, [adId]);

    useEffect(() => {
        const onKey = (e: KeyboardEvent) => e.key === "Escape" && onClose();
        window.addEventListener("keydown", onKey);
        return () => window.removeEventListener("keydown", onKey);
    }, [onClose]);

    if (adId === null) return null;

    return (
        <div className="fixed inset-0 z-50">
            <div
                className="absolute inset-0 bg-black/60"
                onClick={onClose}
                aria-hidden
            />
            <aside
                role="dialog"
                aria-modal="true"
                aria-label="Ad detail"
                className="absolute right-0 top-0 flex h-full w-full max-w-md flex-col border-l border-hairline bg-panel shadow-xl"
            >
                <header className="flex items-start justify-between gap-3 border-b border-hairline p-5">
                    <div className="min-w-0">
                        <h2 className="truncate font-display text-base font-semibold text-slate-100">
                            {detail?.ad.name ?? "Loading…"}
                        </h2>
                        <p className="text-xs text-muted-foreground">
                            {detail?.ad.account}
                            {detail?.ad.status ? ` · ${detail.ad.status}` : ""}
                        </p>
                    </div>
                    <button
                        onClick={onClose}
                        aria-label="Close"
                        className="rounded-md p-1 text-muted-foreground hover:bg-ink hover:text-slate-200"
                    >
                        <X className="size-4" />
                    </button>
                </header>

                <div className="flex-1 overflow-y-auto p-5">
                    {loading && (
                        <p className="text-sm text-muted-foreground">Loading…</p>
                    )}

                    {detail && (
                        <div className="space-y-6">
                            {detail.scores && (
                                <div>
                                    <SectionLabel>Creative scores</SectionLabel>
                                    <div className="mt-3">
                                        <ScoreMeter {...detail.scores} />
                                    </div>
                                    {detail.action && (
                                        <div className="mt-3 flex items-start gap-2">
                                            <ActionBadge action={detail.action} />
                                            <span className="text-xs text-muted-foreground">
                                                {detail.actionReason}
                                            </span>
                                        </div>
                                    )}
                                </div>
                            )}

                            <div>
                                <SectionLabel>Metrics</SectionLabel>
                                <dl className="mt-3 grid grid-cols-2 gap-3">
                                    <Metric label="Spend" value={formatRM(detail.aggregate.spend, { maximumFractionDigits: 0 })} />
                                    <Metric label="Impressions" value={detail.aggregate.impressions.toLocaleString()} />
                                    <Metric label="ROAS" value={detail.aggregate.roas?.toFixed(2) ?? "N/A"} />
                                    <Metric label="Cost / result" value={detail.aggregate.cpr ? formatRM(detail.aggregate.cpr) : "N/A"} />
                                    <Metric label="Link CTR" value={detail.aggregate.ctrLink != null ? `${detail.aggregate.ctrLink}%` : "N/A"} />
                                    <Metric label="Results" value={detail.aggregate.results?.toLocaleString() ?? "N/A"} />
                                </dl>
                            </div>

                            <div>
                                <SectionLabel>Daily trend</SectionLabel>
                                <div className="mt-3 flex items-center gap-6">
                                    <TrendCell
                                        label="Spend"
                                        values={detail.daily.map((d) => d.spend)}
                                        color="#FFB020"
                                    />
                                    <TrendCell
                                        label="ROAS"
                                        values={detail.daily.map((d) => d.roas)}
                                        color="#34D399"
                                    />
                                </div>
                                <div className="mt-4 overflow-x-auto">
                                    <table className="w-full text-xs">
                                        <thead>
                                            <tr className="text-left text-[10px] uppercase tracking-wider text-muted-foreground">
                                                <th className="py-1.5 pr-3 font-medium">Date</th>
                                                <th className="py-1.5 pr-3 text-right font-medium">Spend</th>
                                                <th className="py-1.5 text-right font-medium">ROAS</th>
                                            </tr>
                                        </thead>
                                        <tbody className="tabular">
                                            {detail.daily.map((d) => (
                                                <tr key={d.date} className="border-t border-hairline/40">
                                                    <td className="py-1.5 pr-3 text-muted-foreground">{d.date}</td>
                                                    <td className="py-1.5 pr-3 text-right text-slate-200">{formatRM(d.spend, { maximumFractionDigits: 0 })}</td>
                                                    <td className="py-1.5 text-right text-slate-200">{d.roas?.toFixed(2) ?? "—"}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </aside>
        </div>
    );
}

function SectionLabel({ children }: { children: React.ReactNode }) {
    return (
        <h3 className="text-[10px] font-medium uppercase tracking-wider text-muted-foreground">
            {children}
        </h3>
    );
}

function Metric({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-md border border-hairline bg-ink/40 p-3">
            <dt className="text-[10px] uppercase tracking-wider text-muted-foreground">
                {label}
            </dt>
            <dd className="tabular mt-0.5 text-sm font-semibold text-slate-100">
                {value}
            </dd>
        </div>
    );
}

function TrendCell({
    label,
    values,
    color,
}: {
    label: string;
    values: Array<number | null>;
    color: string;
}) {
    return (
        <div>
            <div className="mb-1 text-[10px] uppercase tracking-wider text-muted-foreground">
                {label}
            </div>
            <Sparkline values={values} color={color} />
        </div>
    );
}
