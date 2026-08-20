import { Head, Link, router, usePage } from "@inertiajs/react";
import {
    CheckCircle2,
    Upload,
    Sparkles,
    Gauge,
    Wand2,
    LayoutGrid,
    Table2,
} from "lucide-react";
import { useState } from "react";

import { AdDetailDrawer } from "@/Components/analytics/AdDetailDrawer";
import { CreativeGrid } from "@/Components/analytics/CreativeGrid";
import { ScoredTable } from "@/Components/analytics/ScoredTable";
import { SummaryStrip } from "@/Components/analytics/SummaryStrip";
import { type AdRow, type Summary } from "@/Components/analytics/types";
import { WinnersLosers } from "@/Components/analytics/WinnersLosers";
import { Button, buttonVariants } from "@/Components/ui/Button";
import AppLayout from "@/Layouts/AppLayout";
import { cn } from "@/lib/utils";

/**
 * P1 Creative Analytics report (M4): account summary, winners/losers, a
 * sortable scored table, and a per-ad detail drawer. Scores are deterministic
 * (M3); AI tags/recommendations layer on in M5.
 */
export default function AnalyticsIndex({
    ads,
    summary,
}: {
    ads: AdRow[];
    summary: Summary;
}) {
    const { flash } = usePage().props;
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const [view, setView] = useState<"grid" | "table">("grid");
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
                            <>
                                <Button
                                    variant="outline"
                                    onClick={() => router.post("/analytics/score")}
                                >
                                    <Gauge className="size-4" />
                                    Recompute scores
                                </Button>
                                <Button onClick={() => router.post("/analytics/ai")}>
                                    <Wand2 className="size-4" />
                                    Generate AI insights
                                </Button>
                            </>
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
                        <WinnersLosers ads={ads} onSelect={setSelectedId} />

                        <div className="mb-3 flex justify-end">
                            <div className="inline-flex rounded-md border border-hairline bg-panel p-0.5">
                                <ViewButton
                                    icon={LayoutGrid}
                                    active={view === "grid"}
                                    onClick={() => setView("grid")}
                                    label="Grid"
                                />
                                <ViewButton
                                    icon={Table2}
                                    active={view === "table"}
                                    onClick={() => setView("table")}
                                    label="Table"
                                />
                            </div>
                        </div>

                        {view === "grid" ? (
                            <CreativeGrid ads={ads} onSelect={setSelectedId} />
                        ) : (
                            <ScoredTable ads={ads} onSelect={setSelectedId} />
                        )}
                    </>
                )}
            </div>

            <AdDetailDrawer
                adId={selectedId}
                onClose={() => setSelectedId(null)}
            />
        </AppLayout>
    );
}

function ViewButton({
    icon: Icon,
    active,
    onClick,
    label,
}: {
    icon: typeof LayoutGrid;
    active: boolean;
    onClick: () => void;
    label: string;
}) {
    return (
        <button
            onClick={onClick}
            aria-label={label}
            aria-pressed={active}
            className={cn(
                "flex items-center gap-1.5 rounded px-2.5 py-1.5 text-xs font-medium transition-colors",
                active
                    ? "bg-ink text-slate-100"
                    : "text-muted-foreground hover:text-slate-200",
            )}
        >
            <Icon className="size-3.5" />
            {label}
        </button>
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
