import { ArrowDown, ArrowUp } from "lucide-react";
import { useMemo, useState } from "react";

import { ActionBadge } from "@/Components/ActionBadge";
import { ScoreMeter } from "@/Components/ScoreMeter";
import { cn, formatRM } from "@/lib/utils";

import { CreativeThumb } from "./CreativeThumb";
import { type AdRow } from "./types";

type SortKey =
    | "name"
    | "spend"
    | "impressions"
    | "roas"
    | "cpr"
    | "ctrLink"
    | "hook"
    | "watch"
    | "click"
    | "convert";

const SCORE_KEYS: SortKey[] = ["hook", "watch", "click", "convert"];

function valueFor(ad: AdRow, key: SortKey): number | string | null {
    if (SCORE_KEYS.includes(key)) return ad.scores?.[key as keyof typeof ad.scores] ?? null;
    return ad[key as keyof AdRow] as number | string | null;
}

/** Scored ad table, sortable by any metric or score (§4). */
export function ScoredTable({
    ads,
    onSelect,
}: {
    ads: AdRow[];
    onSelect: (id: number) => void;
}) {
    const [sort, setSort] = useState<{ key: SortKey; dir: "asc" | "desc" }>({
        key: "spend",
        dir: "desc",
    });

    const sorted = useMemo(() => {
        const rows = [...ads];
        rows.sort((a, b) => {
            const av = valueFor(a, sort.key);
            const bv = valueFor(b, sort.key);
            // Nulls always sort last, regardless of direction.
            if (av === null && bv === null) return 0;
            if (av === null) return 1;
            if (bv === null) return -1;
            const cmp =
                typeof av === "string"
                    ? String(av).localeCompare(String(bv))
                    : (av as number) - (bv as number);
            return sort.dir === "asc" ? cmp : -cmp;
        });
        return rows;
    }, [ads, sort]);

    const toggle = (key: SortKey) =>
        setSort((s) =>
            s.key === key
                ? { key, dir: s.dir === "asc" ? "desc" : "asc" }
                : { key, dir: key === "name" ? "asc" : "desc" },
        );

    return (
        <div className="overflow-hidden rounded-lg border border-hairline bg-panel">
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-hairline text-left text-[10px] uppercase tracking-wider text-muted-foreground">
                            <Th label="Ad" k="name" sort={sort} onSort={toggle} />
                            <Th label="Spend" k="spend" sort={sort} onSort={toggle} align="right" />
                            <Th label="Impr." k="impressions" sort={sort} onSort={toggle} align="right" />
                            <Th label="ROAS" k="roas" sort={sort} onSort={toggle} align="right" />
                            <Th label="CPR" k="cpr" sort={sort} onSort={toggle} align="right" />
                            <Th label="CTR" k="ctrLink" sort={sort} onSort={toggle} align="right" />
                            <th className="px-4 py-3 font-medium">
                                <div className="flex gap-2">
                                    {SCORE_KEYS.map((k) => (
                                        <button
                                            key={k}
                                            onClick={() => toggle(k)}
                                            className={cn(
                                                "uppercase tracking-wider hover:text-slate-200",
                                                sort.key === k && "text-amber",
                                            )}
                                        >
                                            {k[0]}
                                        </button>
                                    ))}
                                </div>
                            </th>
                            <th className="px-4 py-3 font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {sorted.map((ad) => (
                            <tr
                                key={ad.id}
                                onClick={() => onSelect(ad.id)}
                                className="cursor-pointer border-b border-hairline/50 last:border-0 hover:bg-ink/40"
                            >
                                <td className="px-4 py-3">
                                    <div className="flex items-center gap-3">
                                        <CreativeThumb
                                            src={ad.thumbnailUrl}
                                            alt={ad.name}
                                        />
                                        <div className="min-w-0">
                                            <div className="truncate font-medium text-slate-100">
                                                {ad.name}
                                            </div>
                                            <div className="flex items-center gap-1.5 text-[11px] text-muted-foreground">
                                                <span>{ad.account}</span>
                                                {ad.tags?.format && (
                                                    <span className="rounded bg-ink/60 px-1 text-[10px] text-neutral">
                                                        {ad.tags.format}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td className="tabular px-4 py-3 text-right text-slate-200">
                                    {formatRM(ad.spend, { maximumFractionDigits: 0 })}
                                </td>
                                <td className="tabular px-4 py-3 text-right text-muted-foreground">
                                    {ad.impressions?.toLocaleString() ?? "—"}
                                </td>
                                <td className="tabular px-4 py-3 text-right text-slate-200">
                                    {ad.roas?.toFixed(2) ?? "—"}
                                </td>
                                <td className="tabular px-4 py-3 text-right text-muted-foreground">
                                    {ad.cpr ? formatRM(ad.cpr) : "—"}
                                </td>
                                <td className="tabular px-4 py-3 text-right text-muted-foreground">
                                    {ad.ctrLink != null ? `${ad.ctrLink.toFixed(2)}%` : "—"}
                                </td>
                                <td className="w-48 px-4 py-3">
                                    {ad.scores ? (
                                        <ScoreMeter size="compact" {...ad.scores} />
                                    ) : (
                                        <span className="text-xs text-muted-foreground">
                                            —
                                        </span>
                                    )}
                                </td>
                                <td className="px-4 py-3">
                                    <span title={ad.actionReason ?? undefined}>
                                        <ActionBadge action={ad.action} />
                                    </span>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function Th({
    label,
    k,
    sort,
    onSort,
    align = "left",
}: {
    label: string;
    k: SortKey;
    sort: { key: SortKey; dir: "asc" | "desc" };
    onSort: (k: SortKey) => void;
    align?: "left" | "right";
}) {
    const active = sort.key === k;
    return (
        <th className={cn("px-4 py-3 font-medium", align === "right" && "text-right")}>
            <button
                onClick={() => onSort(k)}
                className={cn(
                    "inline-flex items-center gap-1 uppercase tracking-wider hover:text-slate-200",
                    align === "right" && "flex-row-reverse",
                    active && "text-amber",
                )}
            >
                {label}
                {active &&
                    (sort.dir === "asc" ? (
                        <ArrowUp className="size-3" />
                    ) : (
                        <ArrowDown className="size-3" />
                    ))}
            </button>
        </th>
    );
}
