import { Check, Copy } from "lucide-react";
import { useState } from "react";

import { type Variation } from "./types";

/** One AI-generated copy variation with a copy-to-clipboard action. */
export function VariationCard({ variation }: { variation: Variation }) {
    const [copied, setCopied] = useState(false);

    const assembled = [
        variation.hook,
        variation.primary_text,
        variation.headline ? `Headline: ${variation.headline}` : null,
        variation.cta ? `CTA: ${variation.cta}` : null,
    ]
        .filter(Boolean)
        .join("\n\n");

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(assembled);
            setCopied(true);
            setTimeout(() => setCopied(false), 1500);
        } catch {
            /* clipboard unavailable — no-op */
        }
    };

    return (
        <div className="flex flex-col rounded-lg border border-hairline bg-panel p-4">
            <div className="mb-2 flex items-start justify-between gap-2">
                {variation.angle && (
                    <span className="rounded-full border border-neutral/30 bg-neutral/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-neutral">
                        {variation.angle}
                    </span>
                )}
                <button
                    onClick={copy}
                    className="ml-auto inline-flex items-center gap-1 text-[11px] text-muted-foreground hover:text-slate-200"
                >
                    {copied ? (
                        <Check className="size-3 text-winner" />
                    ) : (
                        <Copy className="size-3" />
                    )}
                    {copied ? "Copied" : "Copy"}
                </button>
            </div>

            {variation.hook && (
                <p className="text-sm font-semibold text-slate-100">
                    {variation.hook}
                </p>
            )}
            {variation.primary_text && (
                <p className="mt-2 whitespace-pre-line text-sm text-slate-300">
                    {variation.primary_text}
                </p>
            )}
            <div className="mt-3 flex flex-wrap items-center gap-2 text-[11px] text-muted-foreground">
                {variation.headline && (
                    <span className="rounded bg-ink px-1.5 py-0.5">
                        {variation.headline}
                    </span>
                )}
                {variation.cta && (
                    <span className="rounded bg-amber/15 px-1.5 py-0.5 font-medium text-amber">
                        {variation.cta}
                    </span>
                )}
            </div>
        </div>
    );
}
