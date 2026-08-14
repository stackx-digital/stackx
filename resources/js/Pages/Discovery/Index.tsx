import { Head, router, useForm, usePage } from "@inertiajs/react";
import { Search, Sparkles, CheckCircle2, Info, AlertTriangle } from "lucide-react";
import { type FormEventHandler } from "react";

import { Button } from "@/Components/ui/Button";
import AppLayout from "@/Layouts/AppLayout";
import { cn } from "@/lib/utils";

interface Result {
    source: "ad" | "competitor_ad";
    sourceId: number;
    title: string;
    snippet: string | null;
    similarity: number;
    competitor: string | null;
}

export default function DiscoveryIndex({
    query,
    results,
    embeddedCount,
    embeddingModel,
    searchError,
}: {
    query: string;
    results: Result[];
    embeddedCount: number;
    embeddingModel: string;
    searchError: string | null;
}) {
    const { flash } = usePage().props;
    const { data, setData, get, processing } = useForm({ q: query });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        get("/discovery", { preserveState: true });
    };

    return (
        <AppLayout>
            <Head title="Ad Discovery" />
            <div className="mx-auto max-w-4xl">
                <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <span className="text-xs font-medium uppercase tracking-wider text-amber">
                            P3 · Ad Discovery
                        </span>
                        <h1 className="mt-1 font-display text-2xl font-bold text-slate-100">
                            Semantic search
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Search meaning, not keywords — across your ads and
                            competitor ads.
                        </p>
                    </div>
                    <Button onClick={() => router.post("/discovery/embed")}>
                        <Sparkles className="size-4" />
                        Generate embeddings
                    </Button>
                </div>

                {flash?.status && (
                    <div className="mb-4 flex items-start gap-2 rounded-md border border-winner/30 bg-winner/10 px-4 py-3 text-sm text-winner">
                        <CheckCircle2 className="mt-0.5 size-4 shrink-0" />
                        <span>{flash.status}</span>
                    </div>
                )}

                {embeddedCount === 0 && (
                    <div className="mb-4 flex items-start gap-2 rounded-md border border-neutral/25 bg-neutral/10 px-4 py-3 text-sm text-neutral">
                        <Info className="mt-0.5 size-4 shrink-0" />
                        <span>
                            Nothing indexed yet. Click{" "}
                            <strong>Generate embeddings</strong> (needs an
                            embedding key) to make ads searchable.
                        </span>
                    </div>
                )}

                <form onSubmit={submit} className="mb-6">
                    <div className="flex items-center gap-2 rounded-lg border border-hairline bg-panel px-3">
                        <Search className="size-4 text-muted-foreground" />
                        <input
                            value={data.q}
                            onChange={(e) => setData("q", e.target.value)}
                            placeholder="e.g. Raya discount urgency, free shipping…"
                            className="flex-1 bg-transparent py-3 text-sm text-slate-100 placeholder:text-muted-foreground/60 focus-visible:outline-none"
                        />
                        <Button type="submit" size="sm" disabled={processing}>
                            Search
                        </Button>
                    </div>
                </form>

                {searchError && (
                    <div className="mb-4 flex items-start gap-2 rounded-md border border-cut/30 bg-cut/10 px-4 py-3 text-sm text-cut">
                        <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                        <span>{searchError}</span>
                    </div>
                )}

                {query && !searchError && (
                    <p className="mb-3 text-xs text-muted-foreground">
                        {results.length} result(s) for “{query}”
                    </p>
                )}

                <div className="space-y-3">
                    {results.map((r) => (
                        <ResultRow key={`${r.source}-${r.sourceId}`} result={r} />
                    ))}
                </div>

                {embeddedCount > 0 && (
                    <p className="mt-8 text-center text-[11px] text-muted-foreground">
                        {embeddedCount} items indexed · similarity computed via{" "}
                        <span className="font-mono">{embeddingModel}</span>
                    </p>
                )}
            </div>
        </AppLayout>
    );
}

function ResultRow({ result }: { result: Result }) {
    const pct = Math.round(result.similarity * 100);
    const isCompetitor = result.source === "competitor_ad";

    return (
        <div className="rounded-lg border border-hairline bg-panel p-4">
            <div className="flex items-center justify-between gap-3">
                <div className="flex items-center gap-2">
                    <span
                        className={cn(
                            "rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider",
                            isCompetitor
                                ? "border-cut/30 bg-cut/10 text-cut"
                                : "border-winner/30 bg-winner/10 text-winner",
                        )}
                    >
                        {isCompetitor ? "Competitor" : "Our ad"}
                    </span>
                    <span className="text-sm font-medium text-slate-100">
                        {result.title}
                    </span>
                </div>
                <span
                    className="tabular text-xs text-muted-foreground"
                    title="Cosine similarity"
                >
                    {pct}%
                </span>
            </div>
            {result.snippet && (
                <p className="mt-2 line-clamp-2 text-sm text-muted-foreground">
                    {result.snippet}
                </p>
            )}
            <div className="mt-2 h-1 overflow-hidden rounded-full bg-ink">
                <div
                    className="h-full rounded-full bg-amber"
                    style={{ width: `${pct}%` }}
                />
            </div>
        </div>
    );
}
