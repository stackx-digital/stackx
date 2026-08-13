import { Sparkles } from "lucide-react";

import { type Tags } from "./types";

/** "inferred" provenance badge — AI output is always labelled, never passed
 * off as measured data (§0). */
export function InferredBadge({ by }: { by?: string | null }) {
    return (
        <span
            title={by ? `Inferred by ${by}` : "AI-inferred"}
            className="inline-flex items-center gap-1 rounded-full border border-neutral/30 bg-neutral/10 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wider text-neutral"
        >
            <Sparkles className="size-2.5" />
            inferred
        </span>
    );
}

/** Renders the four creative tags as chips with an inferred badge. */
export function InferredTags({ tags }: { tags: Tags }) {
    const chips = [
        { label: "Format", value: tags.format },
        { label: "Hook", value: tags.hookType },
        { label: "Angle", value: tags.angle },
        { label: "Audience", value: tags.audience },
    ].filter((c) => c.value);

    if (chips.length === 0) return null;

    return (
        <div className="flex flex-wrap items-center gap-1.5">
            {chips.map((c) => (
                <span
                    key={c.label}
                    className="inline-flex items-center gap-1 rounded-md border border-hairline bg-ink/40 px-2 py-0.5 text-[11px] text-slate-200"
                >
                    <span className="text-[9px] uppercase tracking-wider text-muted-foreground">
                        {c.label}
                    </span>
                    {c.value}
                </span>
            ))}
            <InferredBadge by={tags.inferredBy} />
        </div>
    );
}
