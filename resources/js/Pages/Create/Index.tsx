import { Head, useForm, usePage } from "@inertiajs/react";
import { CheckCircle2, Sparkles, Wand2 } from "lucide-react";
import { type FormEventHandler } from "react";

import { VariationCard } from "@/Components/create/VariationCard";
import {
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
}: {
    winners: Winner[];
    competitorAds: CompetitorAdOption[];
    history: HistoryItem[];
}) {
    const { flash } = usePage().props;
    const { data, setData, post, processing } = useForm<{
        product: string;
        source: "" | "ad" | "competitor_ad";
        source_id: string;
        tone: string;
        count: number;
    }>({ product: "", source: "", source_id: "", tone: "", count: 5 });

    const mode: SourceMode = data.source === "" ? "freeform" : data.source;

    const setMode = (m: SourceMode) => {
        setData((d) => ({
            ...d,
            source: m === "freeform" ? "" : m,
            source_id: "",
        }));
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post("/create");
    };

    return (
        <AppLayout>
            <Head title="Ad Creation" />
            <div className="mx-auto max-w-5xl">
                <div className="mb-6">
                    <span className="text-xs font-medium uppercase tracking-wider text-amber">
                        P4 · Ad Creation
                    </span>
                    <h1 className="mt-1 font-display text-2xl font-bold text-slate-100">
                        Generate ad variations
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Turn a winner (yours or a competitor&apos;s) into fresh
                        copy — BM + English, Malaysian style. All output is
                        AI-generated.
                    </p>
                </div>

                {flash?.status && (
                    <div className="mb-6 flex items-start gap-2 rounded-md border border-winner/30 bg-winner/10 px-4 py-3 text-sm text-winner">
                        <CheckCircle2 className="mt-0.5 size-4 shrink-0" />
                        <span>{flash.status}</span>
                    </div>
                )}

                <form
                    onSubmit={submit}
                    className="mb-8 space-y-4 rounded-lg border border-hairline bg-panel p-5"
                >
                    <div>
                        <label className="mb-1.5 block text-[10px] uppercase tracking-wider text-muted-foreground">
                            Base on
                        </label>
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
                    </div>

                    {mode === "ad" && (
                        <SourceSelect
                            value={data.source_id}
                            onChange={(v) => setData("source_id", v)}
                            options={winners.map((w) => ({
                                id: w.id,
                                label: `${w.name}${w.action ? ` · ${w.action}` : ""}`,
                            }))}
                            placeholder="Select a scored ad"
                        />
                    )}
                    {mode === "competitor_ad" && (
                        <SourceSelect
                            value={data.source_id}
                            onChange={(v) => setData("source_id", v)}
                            options={competitorAds}
                            placeholder="Select a competitor ad"
                        />
                    )}

                    <div>
                        <label className="mb-1.5 block text-[10px] uppercase tracking-wider text-muted-foreground">
                            Product / offer
                        </label>
                        <textarea
                            value={data.product}
                            onChange={(e) => setData("product", e.target.value)}
                            required
                            rows={2}
                            placeholder="e.g. Baju Raya premium cotton, RM89, free postage this week"
                            className="w-full rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-slate-100"
                        />
                    </div>

                    <div className="flex flex-wrap gap-4">
                        <div className="flex-1">
                            <label className="mb-1.5 block text-[10px] uppercase tracking-wider text-muted-foreground">
                                Tone (optional)
                            </label>
                            <input
                                value={data.tone}
                                onChange={(e) => setData("tone", e.target.value)}
                                placeholder="urgent, playful, premium…"
                                className="w-full rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-slate-100"
                            />
                        </div>
                        <div className="w-28">
                            <label className="mb-1.5 block text-[10px] uppercase tracking-wider text-muted-foreground">
                                Variations
                            </label>
                            <input
                                type="number"
                                min={1}
                                max={8}
                                value={data.count}
                                onChange={(e) =>
                                    setData("count", Number(e.target.value))
                                }
                                className="w-full rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-slate-100"
                            />
                        </div>
                    </div>

                    <Button type="submit" disabled={processing}>
                        <Wand2 className="size-4" />
                        {processing ? "Generating…" : "Generate variations"}
                    </Button>
                </form>

                {history.length === 0 ? (
                    <div className="rounded-lg border border-dashed border-hairline bg-panel p-10 text-center text-sm text-muted-foreground">
                        No variations yet — fill the brief above and generate.
                    </div>
                ) : (
                    <div className="space-y-6">
                        {history.map((item) => (
                            <HistoryBlock key={item.id} item={item} />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

function SourceSelect({
    value,
    onChange,
    options,
    placeholder,
}: {
    value: string;
    onChange: (v: string) => void;
    options: { id: number; label: string }[];
    placeholder: string;
}) {
    return (
        <select
            value={value}
            onChange={(e) => onChange(e.target.value)}
            className="w-full rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-slate-100"
        >
            <option value="">{placeholder}</option>
            {options.map((o) => (
                <option key={o.id} value={o.id}>
                    {o.label}
                </option>
            ))}
        </select>
    );
}

function HistoryBlock({ item }: { item: HistoryItem }) {
    return (
        <div>
            <div className="mb-3 flex flex-wrap items-center gap-2">
                <h2 className="font-display text-sm font-semibold text-slate-100">
                    {item.product}
                </h2>
                <span className="inline-flex items-center gap-1 rounded-full border border-neutral/30 bg-neutral/10 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wider text-neutral">
                    <Sparkles className="size-2.5" />
                    AI-generated
                </span>
                <span className="text-[11px] text-muted-foreground">
                    {item.source ? `from ${item.source.replace("_", " ")}` : "free-form"}
                    {item.createdAt ? ` · ${item.createdAt}` : ""}
                    {item.generatedBy ? ` · ${item.generatedBy}` : ""}
                </span>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                {item.output.map((v, i) => (
                    <VariationCard key={i} variation={v} />
                ))}
            </div>
        </div>
    );
}
