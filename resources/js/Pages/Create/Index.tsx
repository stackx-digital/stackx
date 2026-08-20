import { Head, useForm, usePage } from "@inertiajs/react";
import { CheckCircle2, FileText, Wand2 } from "lucide-react";
import { useState, type FormEventHandler } from "react";

import { BriefCard } from "@/Components/create/BriefCard";
import { VariationCard } from "@/Components/create/VariationCard";
import {
    type BriefItem,
    type CompetitorAdOption,
    type HistoryItem,
    type Winner,
} from "@/Components/create/types";
import { Button } from "@/Components/ui/Button";
import AppLayout from "@/Layouts/AppLayout";
import { cn } from "@/lib/utils";

type SourceMode = "freeform" | "ad" | "competitor_ad";

export default function CreateIndex({
    winners,
    competitorAds,
    history,
    briefs,
}: {
    winners: Winner[];
    competitorAds: CompetitorAdOption[];
    history: HistoryItem[];
    briefs: BriefItem[];
}) {
    const { flash } = usePage().props;
    const [tab, setTab] = useState<"copy" | "brief">("copy");

    return (
        <AppLayout>
            <Head title="Ad Creation" />
            <div className="mx-auto max-w-5xl">
                <div className="mb-6">
                    <span className="text-xs font-medium uppercase tracking-wider text-amber">
                        P4 · Ad Creation
                    </span>
                    <h1 className="mt-1 font-display text-2xl font-bold text-slate-100">
                        Create from winners
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Generate ad copy or a full creative brief from a winner
                        (yours or a competitor&apos;s). All output is AI-generated.
                    </p>
                </div>

                {flash?.status && (
                    <div className="mb-6 flex items-start gap-2 rounded-md border border-winner/30 bg-winner/10 px-4 py-3 text-sm text-winner">
                        <CheckCircle2 className="mt-0.5 size-4 shrink-0" />
                        <span>{flash.status}</span>
                    </div>
                )}

                <div className="mb-6 flex gap-1">
                    {(
                        [
                            ["copy", "Ad copy", Wand2],
                            ["brief", "Creative brief", FileText],
                        ] as const
                    ).map(([t, label, Icon]) => (
                        <button
                            key={t}
                            onClick={() => setTab(t)}
                            className={cn(
                                "inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm transition-colors",
                                tab === t
                                    ? "border-amber/40 bg-amber/10 text-amber"
                                    : "border-hairline text-muted-foreground hover:text-slate-200",
                            )}
                        >
                            <Icon className="size-4" />
                            {label}
                        </button>
                    ))}
                </div>

                {tab === "copy" ? (
                    <CopyPanel
                        winners={winners}
                        competitorAds={competitorAds}
                        history={history}
                    />
                ) : (
                    <BriefPanel
                        winners={winners}
                        competitorAds={competitorAds}
                        briefs={briefs}
                    />
                )}
            </div>
        </AppLayout>
    );
}

function SourcePicker({
    mode,
    setMode,
    sourceId,
    setSourceId,
    winners,
    competitorAds,
}: {
    mode: SourceMode;
    setMode: (m: SourceMode) => void;
    sourceId: string;
    setSourceId: (v: string) => void;
    winners: Winner[];
    competitorAds: CompetitorAdOption[];
}) {
    const options =
        mode === "ad"
            ? winners.map((w) => ({
                  id: w.id,
                  label: `${w.name}${w.action ? ` · ${w.action}` : ""}`,
              }))
            : mode === "competitor_ad"
              ? competitorAds
              : [];

    return (
        <div className="space-y-3">
            <div className="flex flex-wrap gap-1">
                {(
                    [
                        ["freeform", "Free-form brief"],
                        ["ad", "Our winner"],
                        ["competitor_ad", "Competitor ad"],
                    ] as [SourceMode, string][]
                ).map(([m, label]) => (
                    <button
                        key={m}
                        type="button"
                        onClick={() => setMode(m)}
                        className={cn(
                            "rounded-md border px-3 py-1.5 text-xs transition-colors",
                            mode === m
                                ? "border-amber/40 bg-amber/10 text-amber"
                                : "border-hairline text-muted-foreground hover:text-slate-200",
                        )}
                    >
                        {label}
                    </button>
                ))}
            </div>
            {mode !== "freeform" && (
                <select
                    value={sourceId}
                    onChange={(e) => setSourceId(e.target.value)}
                    className="w-full rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-slate-100"
                >
                    <option value="">Select…</option>
                    {options.map((o) => (
                        <option key={o.id} value={o.id}>
                            {o.label}
                        </option>
                    ))}
                </select>
            )}
        </div>
    );
}

function CopyPanel({
    winners,
    competitorAds,
    history,
}: {
    winners: Winner[];
    competitorAds: CompetitorAdOption[];
    history: HistoryItem[];
}) {
    const { data, setData, post, processing } = useForm<{
        product: string;
        source: "" | "ad" | "competitor_ad";
        source_id: string;
        tone: string;
        count: number;
    }>({ product: "", source: "", source_id: "", tone: "", count: 5 });

    const mode: SourceMode = data.source === "" ? "freeform" : data.source;
    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post("/create");
    };

    return (
        <>
            <form
                onSubmit={submit}
                className="mb-8 space-y-4 rounded-lg border border-hairline bg-panel p-5"
            >
                <SourcePicker
                    mode={mode}
                    setMode={(m) =>
                        setData((d) => ({
                            ...d,
                            source: m === "freeform" ? "" : m,
                            source_id: "",
                        }))
                    }
                    sourceId={data.source_id}
                    setSourceId={(v) => setData("source_id", v)}
                    winners={winners}
                    competitorAds={competitorAds}
                />
                <textarea
                    value={data.product}
                    onChange={(e) => setData("product", e.target.value)}
                    required
                    rows={2}
                    placeholder="Product / offer — e.g. Baju Raya premium cotton, RM89, free postage"
                    className="w-full rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-slate-100"
                />
                <div className="flex flex-wrap gap-4">
                    <div className="flex-1">
                        <input
                            value={data.tone}
                            onChange={(e) => setData("tone", e.target.value)}
                            placeholder="Tone (optional)"
                            className="w-full rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-slate-100"
                        />
                    </div>
                    <input
                        type="number"
                        min={1}
                        max={8}
                        value={data.count}
                        onChange={(e) => setData("count", Number(e.target.value))}
                        className="w-24 rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-slate-100"
                    />
                    <Button type="submit" disabled={processing}>
                        <Wand2 className="size-4" />
                        {processing ? "Generating…" : "Generate copy"}
                    </Button>
                </div>
            </form>

            {history.length === 0 ? (
                <Empty text="No copy generated yet." />
            ) : (
                <div className="space-y-6">
                    {history.map((item) => (
                        <div key={item.id}>
                            <div className="mb-3 flex flex-wrap items-center gap-2">
                                <h2 className="font-display text-sm font-semibold text-slate-100">
                                    {item.product}
                                </h2>
                                <span className="text-[11px] text-muted-foreground">
                                    {item.createdAt}
                                    {item.generatedBy ? ` · ${item.generatedBy}` : ""}
                                </span>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                {item.output.map((v, i) => (
                                    <VariationCard key={i} variation={v} />
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </>
    );
}

function BriefPanel({
    winners,
    competitorAds,
    briefs,
}: {
    winners: Winner[];
    competitorAds: CompetitorAdOption[];
    briefs: BriefItem[];
}) {
    const { data, setData, post, processing } = useForm<{
        product: string;
        source: "" | "ad" | "competitor_ad";
        source_id: string;
    }>({ product: "", source: "", source_id: "" });

    const mode: SourceMode = data.source === "" ? "freeform" : data.source;
    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post("/create/brief");
    };

    return (
        <>
            <form
                onSubmit={submit}
                className="mb-8 space-y-4 rounded-lg border border-hairline bg-panel p-5"
            >
                <SourcePicker
                    mode={mode}
                    setMode={(m) =>
                        setData((d) => ({
                            ...d,
                            source: m === "freeform" ? "" : m,
                            source_id: "",
                        }))
                    }
                    sourceId={data.source_id}
                    setSourceId={(v) => setData("source_id", v)}
                    winners={winners}
                    competitorAds={competitorAds}
                />
                <textarea
                    value={data.product}
                    onChange={(e) => setData("product", e.target.value)}
                    required
                    rows={2}
                    placeholder="Product / offer for the brief"
                    className="w-full rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-slate-100"
                />
                <Button type="submit" disabled={processing}>
                    <FileText className="size-4" />
                    {processing ? "Generating…" : "Generate brief"}
                </Button>
            </form>

            {briefs.length === 0 ? (
                <Empty text="No briefs generated yet." />
            ) : (
                <div className="space-y-6">
                    {briefs.map((item) => (
                        <BriefCard key={item.id} item={item} />
                    ))}
                </div>
            )}
        </>
    );
}

function Empty({ text }: { text: string }) {
    return (
        <div className="rounded-lg border border-dashed border-hairline bg-panel p-10 text-center text-sm text-muted-foreground">
            {text}
        </div>
    );
}
