import { cn } from "@/lib/utils";

/**
 * The signature 4-segment creative score meter (§6). Hook / Watch / Click /
 * Convert, each a percentile 0–100 (or null for N/A when a metric is missing
 * account-wide — §3). Presentational only: the deterministic scoring engine
 * (M3) computes the numbers; this component never invents them.
 */

export type ScoreStage = "hook" | "watch" | "click" | "convert";

export interface ScoreMeterProps {
    hook: number | null;
    watch: number | null;
    click: number | null;
    convert: number | null;
    /** compact = table-row density; default = detail view. */
    size?: "compact" | "default";
    className?: string;
}

const STAGES: { key: ScoreStage; label: string; short: string }[] = [
    { key: "hook", label: "Hook", short: "H" },
    { key: "watch", label: "Watch", short: "W" },
    { key: "click", label: "Click", short: "C" },
    { key: "convert", label: "Convert", short: "V" },
];

/** Score band → color. Relative percentiles read as strong/mid/weak. */
function bandColor(score: number | null): string {
    if (score === null) return "bg-hairline";
    if (score >= 67) return "bg-winner";
    if (score >= 34) return "bg-amber";
    return "bg-cut";
}

export function ScoreMeter({
    hook,
    watch,
    click,
    convert,
    size = "default",
    className,
}: ScoreMeterProps) {
    const values: Record<ScoreStage, number | null> = {
        hook,
        watch,
        click,
        convert,
    };
    const trackHeight = size === "compact" ? "h-1.5" : "h-2.5";

    return (
        <div
            className={cn("flex w-full gap-1", className)}
            role="img"
            aria-label={STAGES.map(
                (s) =>
                    `${s.label} ${values[s.key] === null ? "N/A" : values[s.key]}`,
            ).join(", ")}
        >
            {STAGES.map((stage) => {
                const v = values[stage.key];
                return (
                    <div key={stage.key} className="flex-1">
                        <div
                            className={cn(
                                "relative w-full overflow-hidden rounded-full bg-black/40",
                                trackHeight,
                            )}
                        >
                            <div
                                className={cn(
                                    "absolute inset-y-0 left-0 rounded-full transition-[width]",
                                    bandColor(v),
                                )}
                                style={{
                                    width: v === null ? "100%" : `${v}%`,
                                    opacity: v === null ? 0.2 : 1,
                                }}
                            />
                        </div>
                        {size === "default" && (
                            <div className="mt-1 flex items-center justify-between text-[10px] text-muted-foreground">
                                <span className="uppercase tracking-wide">
                                    {stage.short}
                                </span>
                                <span className="tabular">
                                    {v === null ? "N/A" : v}
                                </span>
                            </div>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
