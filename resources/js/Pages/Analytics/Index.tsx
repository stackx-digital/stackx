import { Head, Link, router, usePage } from "@inertiajs/react";
import { CheckCircle2, Upload, Sparkles } from "lucide-react";

import { ScoreMeter } from "@/Components/ScoreMeter";
import { Button, buttonVariants } from "@/Components/ui/Button";
import AppLayout from "@/Layouts/AppLayout";

/**
 * P1 Creative Analytics. M2 adds the two ingest paths (CSV import + demo data);
 * scoring (M3) and the full report (M4) land here next. The score meter preview
 * keeps the cockpit theme tangible until then.
 */
export default function AnalyticsIndex() {
    const { flash } = usePage().props;

    return (
        <AppLayout>
            <Head title="Creative Analytics" />
            <div className="mx-auto max-w-5xl">
                <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <span className="text-xs font-medium uppercase tracking-wider text-amber">
                            P1 · Creative Analytics
                        </span>
                        <h1 className="mt-1 font-display text-2xl font-bold text-slate-100">
                            Account overview
                        </h1>
                        <p className="mt-1 max-w-xl text-sm text-muted-foreground">
                            Import Meta ad data to see scored creatives, winners
                            &amp; losers, and scale/cut actions. Scoring &amp;
                            report land in M3–M4.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
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

                {flash?.status && (
                    <div className="mb-6 flex items-start gap-2 rounded-md border border-winner/30 bg-winner/10 px-4 py-3 text-sm text-winner">
                        <CheckCircle2 className="mt-0.5 size-4 shrink-0" />
                        <span>{flash.status}</span>
                    </div>
                )}

                <div className="rounded-lg border border-hairline bg-panel p-6">
                    <div className="flex items-center justify-between">
                        <h2 className="font-display text-sm font-semibold text-slate-100">
                            Creative score meter
                        </h2>
                        <span className="text-[10px] uppercase tracking-wider text-muted-foreground">
                            Preview · sample values
                        </span>
                    </div>
                    <p className="mt-1 text-xs text-muted-foreground">
                        The 4-segment signature meter — Hook / Watch / Click /
                        Convert, each a percentile within the account. Scoring
                        engine arrives in M3.
                    </p>
                    <div className="mt-5 max-w-md">
                        <ScoreMeter
                            hook={82}
                            watch={64}
                            click={38}
                            convert={null}
                        />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
