import { TrendingUp, TrendingDown } from "lucide-react";

import { ScoreMeter } from "@/Components/ScoreMeter";
import { formatRM } from "@/lib/utils";

import { type AdRow } from "./types";

/** Winners (scale) & losers (cut) sections (§4), spend-ordered. */
export function WinnersLosers({
    ads,
    onSelect,
}: {
    ads: AdRow[];
    onSelect: (id: number) => void;
}) {
    const winners = ads
        .filter((a) => a.action === "scale")
        .sort((a, b) => (b.roas ?? 0) - (a.roas ?? 0))
        .slice(0, 4);
    const losers = ads
        .filter((a) => a.action === "cut")
        .sort((a, b) => (a.roas ?? 0) - (b.roas ?? 0))
        .slice(0, 4);

    if (winners.length === 0 && losers.length === 0) return null;

    return (
        <div className="mb-6 grid gap-4 md:grid-cols-2">
            <Section
                title="Winners — scale"
                icon={<TrendingUp className="size-4 text-winner" />}
                accent="text-winner"
                ads={winners}
                empty="No clear winners yet."
                onSelect={onSelect}
            />
            <Section
                title="Losers — cut"
                icon={<TrendingDown className="size-4 text-cut" />}
                accent="text-cut"
                ads={losers}
                empty="No clear losers yet."
                onSelect={onSelect}
            />
        </div>
    );
}

function Section({
    title,
    icon,
    accent,
    ads,
    empty,
    onSelect,
}: {
    title: string;
    icon: React.ReactNode;
    accent: string;
    ads: AdRow[];
    empty: string;
    onSelect: (id: number) => void;
}) {
    return (
        <div className="rounded-lg border border-hairline bg-panel p-4">
            <div className="mb-3 flex items-center gap-2">
                {icon}
                <h2
                    className={`font-display text-xs font-semibold uppercase tracking-wider ${accent}`}
                >
                    {title}
                </h2>
            </div>
            {ads.length === 0 ? (
                <p className="text-xs text-muted-foreground">{empty}</p>
            ) : (
                <ul className="space-y-3">
                    {ads.map((ad) => (
                        <li key={ad.id}>
                            <button
                                onClick={() => onSelect(ad.id)}
                                className="w-full rounded-md px-1 py-1 text-left hover:bg-ink/40"
                            >
                                <div className="flex items-center justify-between gap-3">
                                    <span className="truncate text-sm text-slate-200">
                                        {ad.name}
                                    </span>
                                    <span className="tabular shrink-0 text-xs text-muted-foreground">
                                        {formatRM(ad.spend, {
                                            maximumFractionDigits: 0,
                                        })}{" "}
                                        · {ad.roas?.toFixed(1) ?? "—"}×
                                    </span>
                                </div>
                                {ad.scores && (
                                    <div className="mt-1.5">
                                        <ScoreMeter
                                            size="compact"
                                            {...ad.scores}
                                        />
                                    </div>
                                )}
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
