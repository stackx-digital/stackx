import { Head, router, useForm, usePage } from "@inertiajs/react";
import {
    CheckCircle2,
    Eye,
    Info,
    Plus,
    RefreshCw,
    Sparkles,
} from "lucide-react";
import { useState, type FormEventHandler } from "react";

import { CompetitorAdCard } from "@/Components/spy/CompetitorAdCard";
import { type Competitor } from "@/Components/spy/types";
import { Button, buttonVariants } from "@/Components/ui/Button";
import AppLayout from "@/Layouts/AppLayout";
import { cn } from "@/lib/utils";

export default function SpyIndex({
    competitors,
    adLibraryEnabled,
}: {
    competitors: Competitor[];
    adLibraryEnabled: boolean;
}) {
    const { flash } = usePage().props;
    const [selectedId, setSelectedId] = useState<number | null>(
        competitors[0]?.id ?? null,
    );
    const [showAdd, setShowAdd] = useState(false);

    const selected =
        competitors.find((c) => c.id === selectedId) ?? competitors[0] ?? null;

    return (
        <AppLayout>
            <Head title="Brand Spy" />
            <div className="mx-auto max-w-6xl">
                <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <span className="text-xs font-medium uppercase tracking-wider text-amber">
                            P2 · Brand Spy
                        </span>
                        <h1 className="mt-1 font-display text-2xl font-bold text-slate-100">
                            Competitor ads
                        </h1>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            variant="outline"
                            onClick={() => setShowAdd((v) => !v)}
                        >
                            <Plus className="size-4" />
                            Add competitor
                        </Button>
                        <Button onClick={() => router.post("/spy/demo")}>
                            <Sparkles className="size-4" />
                            Load demo competitors
                        </Button>
                    </div>
                </div>

                {!adLibraryEnabled && (
                    <div className="mb-4 flex items-start gap-2 rounded-md border border-neutral/25 bg-neutral/10 px-4 py-3 text-sm text-neutral">
                        <Info className="mt-0.5 size-4 shrink-0" />
                        <span>
                            Live Ad Library sync is off — showing saved/demo
                            data. Set <code>FEATURE_META_AD_LIBRARY=true</code>{" "}
                            and a token to sync. Note: Ad Library coverage in some
                            regions is limited to political ads.
                        </span>
                    </div>
                )}

                {flash?.status && (
                    <div className="mb-6 flex items-start gap-2 rounded-md border border-winner/30 bg-winner/10 px-4 py-3 text-sm text-winner">
                        <CheckCircle2 className="mt-0.5 size-4 shrink-0" />
                        <span>{flash.status}</span>
                    </div>
                )}

                {showAdd && <AddCompetitorForm onDone={() => setShowAdd(false)} />}

                {competitors.length === 0 ? (
                    <EmptyState />
                ) : (
                    <div className="grid gap-6 md:grid-cols-[240px_1fr]">
                        <CompetitorList
                            competitors={competitors}
                            selectedId={selected?.id ?? null}
                            onSelect={setSelectedId}
                        />
                        {selected && (
                            <CompetitorPanel
                                competitor={selected}
                                canSync={adLibraryEnabled}
                            />
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

function CompetitorList({
    competitors,
    selectedId,
    onSelect,
}: {
    competitors: Competitor[];
    selectedId: number | null;
    onSelect: (id: number) => void;
}) {
    return (
        <aside className="space-y-1">
            {competitors.map((c) => (
                <button
                    key={c.id}
                    onClick={() => onSelect(c.id)}
                    className={cn(
                        "w-full rounded-md border px-3 py-2 text-left transition-colors",
                        c.id === selectedId
                            ? "border-hairline bg-panel"
                            : "border-transparent hover:bg-panel/60",
                    )}
                >
                    <div className="text-sm font-medium text-slate-100">
                        {c.name}
                    </div>
                    <div className="tabular text-[11px] text-muted-foreground">
                        {c.activeCount} active · {c.adCount} total
                    </div>
                </button>
            ))}
        </aside>
    );
}

function CompetitorPanel({
    competitor,
    canSync,
}: {
    competitor: Competitor;
    canSync: boolean;
}) {
    // Ads arrive ordered by days_running desc from the server.
    const ads = competitor.ads;

    return (
        <section>
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 className="font-display text-lg font-semibold text-slate-100">
                        {competitor.name}
                    </h2>
                    <p className="text-xs text-muted-foreground">
                        {competitor.metaPageId
                            ? `Page ${competitor.metaPageId}`
                            : "No page ID"}
                        {competitor.lastSyncedAt
                            ? ` · synced ${competitor.lastSyncedAt}`
                            : " · never synced"}
                    </p>
                </div>
                <Button
                    variant="outline"
                    disabled={!canSync}
                    title={
                        canSync
                            ? undefined
                            : "Enable FEATURE_META_AD_LIBRARY to sync"
                    }
                    onClick={() =>
                        router.post(`/spy/competitors/${competitor.id}/sync`)
                    }
                >
                    <RefreshCw className="size-4" />
                    Sync
                </Button>
            </div>

            {ads.length === 0 ? (
                <p className="rounded-lg border border-dashed border-hairline bg-panel p-6 text-center text-sm text-muted-foreground">
                    No ads saved for this competitor yet.
                </p>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2">
                    {ads.map((ad, i) => (
                        <CompetitorAdCard key={ad.id} ad={ad} isTop={i === 0} />
                    ))}
                </div>
            )}
        </section>
    );
}

function AddCompetitorForm({ onDone }: { onDone: () => void }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: "",
        meta_page_id: "",
        search_terms: "",
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post("/spy/competitors", {
            onSuccess: () => {
                reset();
                onDone();
            },
        });
    };

    return (
        <form
            onSubmit={submit}
            className="mb-6 grid gap-3 rounded-lg border border-hairline bg-panel p-4 md:grid-cols-3"
        >
            <div>
                <label className="mb-1 block text-[10px] uppercase tracking-wider text-muted-foreground">
                    Name
                </label>
                <input
                    value={data.name}
                    onChange={(e) => setData("name", e.target.value)}
                    required
                    placeholder="Competitor brand"
                    className="w-full rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-slate-100"
                />
                {errors.name && (
                    <p className="mt-1 text-xs text-cut">{errors.name}</p>
                )}
            </div>
            <div>
                <label className="mb-1 block text-[10px] uppercase tracking-wider text-muted-foreground">
                    Meta Page ID (optional)
                </label>
                <input
                    value={data.meta_page_id}
                    onChange={(e) => setData("meta_page_id", e.target.value)}
                    placeholder="1234567890"
                    className="w-full rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-slate-100"
                />
            </div>
            <div className="flex items-end gap-2">
                <input
                    value={data.search_terms}
                    onChange={(e) => setData("search_terms", e.target.value)}
                    placeholder="or search terms"
                    className="w-full rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-slate-100"
                />
                <Button type="submit" disabled={processing}>
                    Add
                </Button>
            </div>
        </form>
    );
}

function EmptyState() {
    return (
        <div className="rounded-lg border border-dashed border-hairline bg-panel p-10 text-center">
            <div className="mx-auto mb-4 flex size-12 items-center justify-center rounded-xl border border-hairline bg-ink">
                <Eye className="size-5 text-muted-foreground" />
            </div>
            <h2 className="font-display text-lg font-semibold text-slate-100">
                No competitors tracked
            </h2>
            <p className="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                Add a competitor by name (and Meta Page ID), or load demo
                competitors to explore their longest-running ads.
            </p>
            <div className="mt-5">
                <button
                    onClick={() => router.post("/spy/demo")}
                    className={buttonVariants({ variant: "default" })}
                >
                    <Sparkles className="size-4" />
                    Load demo competitors
                </button>
            </div>
        </div>
    );
}
