import { Head, Link, router, usePage } from "@inertiajs/react";
import { CheckCircle2, Upload, Sparkles, Gauge } from "lucide-react";

import { ActionBadge } from "@/Components/ActionBadge";
import { ScoreMeter } from "@/Components/ScoreMeter";
import { Button, buttonVariants } from "@/Components/ui/Button";
import AppLayout from "@/Layouts/AppLayout";
import { formatRM } from "@/lib/utils";

interface AdRow {
    id: number;
    name: string;
    account: string | null;
    spend: number | null;
    roas: number | null;
    scores: {
        hook: number | null;
        watch: number | null;
        click: number | null;
        convert: number | null;
    } | null;
    action: string | null;
    actionReason: string | null;
}

interface Summary {
    adCount: number;
    totalSpend: number;
    blendedRoas: number | null;
    totalResults: number | null;
    scored: number;
}

export default function AnalyticsIndex({
    ads,
    summary,
}: {
    ads: AdRow[];
    summary: Summary;
}) {
    const { flash } = usePage().props;
    const hasData = summary.adCount > 0;

    return (
        <AppLayout>
            <Head title="Creative Analytics" />
            <div className="mx-auto max-w-6xl">
                <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <span className="text-xs font-medium uppercase tracking-wider text-amber">
                            P1 · Creative Analytics
                        </span>
                        <h1 className="mt-1 font-display text-2xl font-bold text-slate-100">
                            Account overview
                        </h1>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Link
                            href="/analytics/import"
                            className={buttonVariants({ variant: "outline" })}
                        >
                            <Upload className="size-4" />
                            Import CSV
                        </Link>
                        <Button
                            variant="outline"
                            onClick={() => router.post("/analytics/demo")}
                        >
                            <Sparkles className="size-4" />
                            Load demo data
                        </Button>
                        {hasData && (
                            <Button
                                onClick={() => router.post("/analytics/score")}
                            >
                                <Gauge className="size-4" />
                                Recompute scores
                            </Button>
                        )}
                    </div>
                </div>

                {flash?.status && (
                    <div className="mb-6 flex items-start gap-2 rounded-md border border-winner/30 bg-winner/10 px-4 py-3 text-sm text-winner">
                        <CheckCircle2 className="mt-0.5 size-4 shrink-0" />
                        <span>{flash.status}</span>
                    </div>
                )}

                {!hasData ? (
                    <EmptyState />
                ) : (
                    <>
                        <SummaryStrip summary={summary} />
                        <ScoredTable ads={ads} />
                    </>
                )}
            </div>
        </AppLayout>
    );
}

function SummaryStrip({ summary }: { summary: Summary }) {
    const items = [
        { label: "Total spend", value: formatRM(summary.totalSpend, { maximumFractionDigits: 0 }) },
        { label: "Blended ROAS", value: summary.blendedRoas?.toFixed(2) ?? "N/A" },
        { label: "Sales / results", value: summary.totalResults?.toLocaleString() ?? "N/A" },
        { label: "Ads scored", value: `${summary.scored}/${summary.adCount}` },
    ];

    return (
        <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
            {items.map((it) => (
                <div
                    key={it.label}
                    className="rounded-lg border border-hairline bg-panel p-4"
                >
                    <div className="text-[10px] uppercase tracking-wider text-muted-foreground">
                        {it.label}
                    </div>
                    <div className="tabular mt-1 text-lg font-semibold text-slate-100">
                        {it.value}
                    </div>
                </div>
            ))}
        </div>
    );
}

function ScoredTable({ ads }: { ads: AdRow[] }) {
    return (
        <div className="overflow-hidden rounded-lg border border-hairline bg-panel">
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-hairline text-left text-[10px] uppercase tracking-wider text-muted-foreground">
                            <th className="px-4 py-3 font-medium">Ad</th>
                            <th className="px-4 py-3 text-right font-medium">Spend</th>
                            <th className="px-4 py-3 text-right font-medium">ROAS</th>
                            <th className="w-56 px-4 py-3 font-medium">
                                Hook · Watch · Click · Convert
                            </th>
                            <th className="px-4 py-3 font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {ads.map((ad) => (
                            <tr
                                key={ad.id}
                                className="border-b border-hairline/50 last:border-0 hover:bg-ink/40"
                            >
                                <td className="px-4 py-3">
                                    <div className="font-medium text-slate-100">
                                        {ad.name}
                                    </div>
                                    <div className="text-[11px] text-muted-foreground">
                                        {ad.account}
                                    </div>
                                </td>
                                <td className="tabular px-4 py-3 text-right text-slate-200">
                                    {formatRM(ad.spend, { maximumFractionDigits: 0 })}
                                </td>
                                <td className="tabular px-4 py-3 text-right text-slate-200">
                                    {ad.roas?.toFixed(2) ?? "—"}
                                </td>
                                <td className="px-4 py-3">
                                    {ad.scores ? (
                                        <ScoreMeter
                                            size="compact"
                                            hook={ad.scores.hook}
                                            watch={ad.scores.watch}
                                            click={ad.scores.click}
                                            convert={ad.scores.convert}
                                        />
                                    ) : (
                                        <span className="text-xs text-muted-foreground">
                                            Not scored yet
                                        </span>
                                    )}
                                </td>
                                <td className="px-4 py-3">
                                    <span title={ad.actionReason ?? undefined}>
                                        <ActionBadge action={ad.action} />
                                    </span>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function EmptyState() {
    return (
        <div className="rounded-lg border border-dashed border-hairline bg-panel p-10 text-center">
            <h2 className="font-display text-lg font-semibold text-slate-100">
                No ad data yet
            </h2>
            <p className="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                Import a Meta Ads Manager CSV, or load demo data to see scored
                creatives with the 4-segment Hook / Watch / Click / Convert meter
                and scale/cut actions.
            </p>
            <div className="mt-5 flex items-center justify-center gap-2">
                <Link
                    href="/analytics/import"
                    className={buttonVariants({ variant: "default" })}
                >
                    <Upload className="size-4" />
                    Import CSV
                </Link>
                <Button
                    variant="outline"
                    onClick={() => router.post("/analytics/demo")}
                >
                    <Sparkles className="size-4" />
                    Load demo data
                </Button>
            </div>
        </div>
    );
}
