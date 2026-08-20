import { Head } from "@inertiajs/react";

import { ActionBadge } from "@/Components/ActionBadge";
import { ScoreMeter } from "@/Components/ScoreMeter";
import { formatRM } from "@/lib/utils";

interface Scores {
    hook: number | null;
    watch: number | null;
    click: number | null;
    convert: number | null;
}
interface AdRow {
    name: string;
    account: string | null;
    spend: number | null;
    roas: number | null;
    scores: Scores | null;
    action: string | null;
}
interface Payload {
    generatedAt: string;
    summary: {
        adCount: number;
        totalSpend: number;
        blendedRoas: number | null;
        blendedCpa: number | null;
        totalResults: number | null;
    };
    winners: AdRow[];
    losers: AdRow[];
    topCompetitorAds: Array<{
        competitor: string;
        body: string | null;
        daysRunning: number;
    }>;
}

/** Public, read-only shared report — no sidebar/topbar, no auth. */
export default function PublicReport({
    title,
    payload,
}: {
    title: string;
    payload: Payload;
}) {
    const { summary } = payload;

    return (
        <>
            <Head title={title} />
            <main className="min-h-screen bg-ink px-6 py-10">
                <div className="mx-auto max-w-4xl">
                    <header className="mb-8 flex items-center justify-between">
                        <div>
                            <span className="font-display text-lg font-bold tracking-tight text-slate-100">
                                STACK<span className="text-amber">x</span>
                            </span>
                            <p className="text-xs text-muted-foreground">
                                {title} · generated {payload.generatedAt}
                            </p>
                        </div>
                        <span className="rounded-full border border-hairline px-2 py-0.5 text-[10px] uppercase tracking-wider text-muted-foreground">
                            Read-only
                        </span>
                    </header>

                    <div className="mb-8 grid grid-cols-2 gap-3 md:grid-cols-4">
                        <Stat label="Total spend" value={formatRM(summary.totalSpend, { maximumFractionDigits: 0 })} />
                        <Stat label="Blended ROAS" value={summary.blendedRoas?.toFixed(2) ?? "N/A"} />
                        <Stat label="Blended CPA" value={summary.blendedCpa ? formatRM(summary.blendedCpa) : "N/A"} />
                        <Stat label="Sales / results" value={summary.totalResults?.toLocaleString() ?? "N/A"} />
                    </div>

                    <div className="grid gap-6 md:grid-cols-2">
                        <AdList title="Winners — scale" accent="text-winner" ads={payload.winners} empty="No clear winners." />
                        <AdList title="Losers — cut" accent="text-cut" ads={payload.losers} empty="No clear losers." />
                    </div>

                    {payload.topCompetitorAds?.length > 0 && (
                        <div className="mt-8">
                            <h2 className="mb-3 font-display text-xs font-semibold uppercase tracking-wider text-neutral">
                                Competitors — longest-running ads
                            </h2>
                            <ul className="space-y-2">
                                {payload.topCompetitorAds.map((c, i) => (
                                    <li
                                        key={i}
                                        className="rounded-lg border border-hairline bg-panel p-3"
                                    >
                                        <div className="flex items-center justify-between text-xs">
                                            <span className="font-medium text-slate-200">
                                                {c.competitor}
                                            </span>
                                            <span className="tabular text-muted-foreground">
                                                {c.daysRunning}d
                                            </span>
                                        </div>
                                        {c.body && (
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {c.body}
                                            </p>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    <footer className="mt-10 border-t border-hairline pt-4 text-center text-[11px] text-muted-foreground">
                        Shared report · STACKx Ad Intelligence · scores computed,
                        RM (MYR)
                    </footer>
                </div>
            </main>
        </>
    );
}

function Stat({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-lg border border-hairline bg-panel p-4">
            <div className="text-[10px] uppercase tracking-wider text-muted-foreground">
                {label}
            </div>
            <div className="tabular mt-1 text-lg font-semibold text-slate-100">
                {value}
            </div>
        </div>
    );
}

function AdList({
    title,
    accent,
    ads,
    empty,
}: {
    title: string;
    accent: string;
    ads: AdRow[];
    empty: string;
}) {
    return (
        <div className="rounded-lg border border-hairline bg-panel p-4">
            <h2 className={`mb-3 font-display text-xs font-semibold uppercase tracking-wider ${accent}`}>
                {title}
            </h2>
            {ads.length === 0 ? (
                <p className="text-xs text-muted-foreground">{empty}</p>
            ) : (
                <ul className="space-y-3">
                    {ads.map((ad, i) => (
                        <li key={i}>
                            <div className="flex items-center justify-between gap-2">
                                <span className="truncate text-sm text-slate-200">
                                    {ad.name}
                                </span>
                                <span className="tabular shrink-0 text-xs text-muted-foreground">
                                    {formatRM(ad.spend, { maximumFractionDigits: 0 })} · {ad.roas?.toFixed(1) ?? "—"}×
                                </span>
                            </div>
                            {ad.scores && (
                                <div className="mt-1.5">
                                    <ScoreMeter size="compact" {...ad.scores} />
                                </div>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
