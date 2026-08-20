import { ChevronDown, LayoutGrid } from "lucide-react";
import { useMemo, useRef, useState } from "react";

import { cn, formatRM } from "@/lib/utils";

import { CreativeCover } from "./CreativeThumb";
import { type AdRow } from "./types";

type MetricKey =
    | "roas"
    | "spend"
    | "cpr"
    | "results"
    | "impressions"
    | "ctrLink";

const METRIC_LABELS: Record<MetricKey, string> = {
    roas: "ROAS",
    spend: "Spend",
    cpr: "CPA",
    results: "Purchases",
    impressions: "Impressions",
    ctrLink: "Link CTR",
};

const DEFAULT_METRICS: MetricKey[] = ["roas", "spend", "cpr", "results"];

type GroupKey = "none" | "format" | "hookType" | "angle" | "audience";

const GROUP_LABELS: Record<GroupKey, string> = {
    none: "No grouping",
    format: "Format",
    hookType: "Hook type",
    angle: "Angle",
    audience: "Audience",
};

function metricValue(ad: AdRow, key: MetricKey): string {
    switch (key) {
        case "roas":
            return ad.roas?.toFixed(2) ?? "—";
        case "spend":
            return ad.spend != null
                ? formatRM(ad.spend, { maximumFractionDigits: 0 })
                : "—";
        case "cpr":
            return ad.cpr != null ? formatRM(ad.cpr) : "—";
        case "results":
            return ad.results?.toLocaleString() ?? "—";
        case "impressions":
            return ad.impressions?.toLocaleString() ?? "—";
        case "ctrLink":
            return ad.ctrLink != null ? `${ad.ctrLink.toFixed(2)}%` : "—";
    }
}

/**
 * Creative-first grid — a swipeable wall of ad images with their headline
 * metrics, grouped by creative attribute (format/hook/angle/audience) when
 * useful. Complements the sortable ScoredTable for a more visual scan.
 */
export function CreativeGrid({
    ads,
    onSelect,
}: {
    ads: AdRow[];
    onSelect: (id: number) => void;
}) {
    const [group, setGroup] = useState<GroupKey>("none");
    const [metrics, setMetrics] = useState<MetricKey[]>(DEFAULT_METRICS);
    const [metricsOpen, setMetricsOpen] = useState(false);
    const metricsRef = useRef<HTMLDivElement>(null);

    const toggleMetric = (key: MetricKey) =>
        setMetrics((prev) =>
            prev.includes(key)
                ? prev.filter((m) => m !== key)
                : [...prev, key],
        );

    const groups = useMemo(() => {
        if (group === "none") {
            return [{ label: null, ads }];
        }

        const field =
            group === "format"
                ? "format"
                : group === "hookType"
                  ? "hookType"
                  : group === "angle"
                    ? "angle"
                    : "audience";

        const map = new Map<string, AdRow[]>();
        for (const ad of ads) {
            const key = ad.tags?.[field] ?? "Untagged";
            const list = map.get(key) ?? [];
            list.push(ad);
            map.set(key, list);
        }

        return [...map.entries()]
            .sort(([a], [b]) =>
                a === "Untagged" ? 1 : b === "Untagged" ? -1 : a.localeCompare(b),
            )
            .map(([label, groupAds]) => ({ label, ads: groupAds }));
    }, [ads, group]);

    return (
        <div>
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-2">
                    <LayoutGrid className="size-4 text-muted-foreground" />
                    <select
                        value={group}
                        onChange={(e) => setGroup(e.target.value as GroupKey)}
                        className="rounded-md border border-hairline bg-ink px-3 py-1.5 text-sm text-slate-100 focus-visible:outline-none"
                    >
                        {Object.entries(GROUP_LABELS).map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="relative" ref={metricsRef}>
                    <button
                        onClick={() => setMetricsOpen((o) => !o)}
                        className="flex items-center gap-1.5 rounded-md border border-hairline bg-ink px-3 py-1.5 text-sm text-slate-200 hover:bg-panel"
                    >
                        Metrics ({metrics.length})
                        <ChevronDown className="size-3.5" />
                    </button>
                    {metricsOpen && (
                        <>
                            <div
                                className="fixed inset-0 z-10"
                                onClick={() => setMetricsOpen(false)}
                            />
                            <div className="absolute right-0 z-20 mt-1.5 w-44 rounded-md border border-hairline bg-panel p-2 shadow-xl">
                                {(Object.keys(METRIC_LABELS) as MetricKey[]).map(
                                    (key) => (
                                        <label
                                            key={key}
                                            className="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-xs text-slate-200 hover:bg-ink/60"
                                        >
                                            <input
                                                type="checkbox"
                                                checked={metrics.includes(key)}
                                                onChange={() => toggleMetric(key)}
                                                className="rounded border-hairline bg-ink text-amber focus:ring-amber/40"
                                            />
                                            {METRIC_LABELS[key]}
                                        </label>
                                    ),
                                )}
                            </div>
                        </>
                    )}
                </div>
            </div>

            <div className="space-y-8">
                {groups.map(({ label, ads: groupAds }) => (
                    <div key={label ?? "all"}>
                        {label && (
                            <h3 className="mb-3 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                {label}
                                <span className="ml-1.5 tabular text-muted-foreground/60">
                                    ({groupAds.length})
                                </span>
                            </h3>
                        )}
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                            {groupAds.map((ad) => (
                                <CreativeCard
                                    key={ad.id}
                                    ad={ad}
                                    metrics={metrics}
                                    onSelect={onSelect}
                                />
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

function CreativeCard({
    ad,
    metrics,
    onSelect,
}: {
    ad: AdRow;
    metrics: MetricKey[];
    onSelect: (id: number) => void;
}) {
    // Deterministic highlight — reuses the same spend-weighted classification
    // as the Action column (§3), not a fresh guess at "good."
    const winning = ad.action === "scale";

    return (
        <button
            onClick={() => onSelect(ad.id)}
            className="group overflow-hidden rounded-lg border border-hairline bg-panel text-left transition-colors hover:border-amber/40"
        >
            <CreativeCover src={ad.thumbnailUrl} alt={ad.name} />
            <div className="p-3">
                <p className="truncate font-mono text-xs text-slate-200">
                    {ad.name}
                </p>
                <dl className="mt-2 space-y-1">
                    {metrics.map((key) => {
                        const highlighted =
                            winning && (key === "roas" || key === "cpr");
                        return (
                            <div
                                key={key}
                                className="flex items-center justify-between text-xs"
                            >
                                <dt className="text-muted-foreground">
                                    {METRIC_LABELS[key]}
                                </dt>
                                <dd
                                    className={cn(
                                        "tabular font-medium",
                                        highlighted
                                            ? "rounded bg-winner/15 px-1.5 py-0.5 text-winner"
                                            : "text-slate-200",
                                    )}
                                >
                                    {metricValue(ad, key)}
                                </dd>
                            </div>
                        );
                    })}
                </dl>
            </div>
        </button>
    );
}
