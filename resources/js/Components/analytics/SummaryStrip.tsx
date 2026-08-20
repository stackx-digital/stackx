import { formatRM } from "@/lib/utils";

import { type Summary } from "./types";

/** Account headline metrics (§4): spend, blended ROAS/CPA, sales. */
export function SummaryStrip({ summary }: { summary: Summary }) {
    const items = [
        {
            label: "Total spend",
            value: formatRM(summary.totalSpend, { maximumFractionDigits: 0 }),
        },
        { label: "Blended ROAS", value: summary.blendedRoas?.toFixed(2) ?? "N/A" },
        {
            label: "Blended CPA",
            value: summary.blendedCpa
                ? formatRM(summary.blendedCpa)
                : "N/A",
        },
        {
            label: "Sales / results",
            value: summary.totalResults?.toLocaleString() ?? "N/A",
        },
        { label: "Ads scored", value: `${summary.scored}/${summary.adCount}` },
    ];

    return (
        <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-5">
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
