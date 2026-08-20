import { Sparkles } from "lucide-react";

import { type BriefItem } from "./types";

/** Renders one AI-generated creative brief. */
export function BriefCard({ item }: { item: BriefItem }) {
    const b = item.output;

    return (
        <div className="rounded-lg border border-hairline bg-panel p-5">
            <div className="mb-4 flex flex-wrap items-center gap-2">
                <h2 className="font-display text-sm font-semibold text-slate-100">
                    {item.product}
                </h2>
                <span className="inline-flex items-center gap-1 rounded-full border border-neutral/30 bg-neutral/10 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wider text-neutral">
                    <Sparkles className="size-2.5" />
                    AI-generated
                </span>
                <span className="text-[11px] text-muted-foreground">
                    {item.createdAt}
                    {item.generatedBy ? ` · ${item.generatedBy}` : ""}
                </span>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <Field label="Objective" value={b.objective} />
                <Field label="Target audience" value={b.target_audience} />
                <Field label="Big idea" value={b.big_idea} />
                <Field label="Angle" value={b.angle} />
                <Field label="Visual direction" value={b.visual_direction} />
                <Field label="CTA" value={b.cta} />
            </div>

            {b.hooks.length > 0 && (
                <List label="Hook ideas" items={b.hooks} />
            )}
            {b.copy_points.length > 0 && (
                <List label="Copy points" items={b.copy_points} />
            )}
        </div>
    );
}

function Field({ label, value }: { label: string; value: string | null }) {
    if (!value) return null;
    return (
        <div>
            <div className="text-[10px] uppercase tracking-wider text-muted-foreground">
                {label}
            </div>
            <p className="mt-0.5 text-sm text-slate-200">{value}</p>
        </div>
    );
}

function List({ label, items }: { label: string; items: string[] }) {
    return (
        <div className="mt-4">
            <div className="text-[10px] uppercase tracking-wider text-muted-foreground">
                {label}
            </div>
            <ul className="mt-1 list-inside list-disc space-y-1 text-sm text-slate-200">
                {items.map((it, i) => (
                    <li key={i}>{it}</li>
                ))}
            </ul>
        </div>
    );
}
