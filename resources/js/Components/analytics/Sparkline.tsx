/** Minimal inline-SVG sparkline for a daily trend. Nulls are skipped. */
export function Sparkline({
    values,
    color = "#FFB020",
    height = 32,
    width = 120,
}: {
    values: Array<number | null>;
    color?: string;
    height?: number;
    width?: number;
}) {
    const points = values
        .map((v, i) => ({ v, i }))
        .filter((p): p is { v: number; i: number } => p.v !== null);

    if (points.length < 2) {
        return (
            <span className="text-[11px] text-muted-foreground">
                Not enough data
            </span>
        );
    }

    const vals = points.map((p) => p.v);
    const min = Math.min(...vals);
    const max = Math.max(...vals);
    const span = max - min || 1;
    const stepX = width / (values.length - 1);

    const d = points
        .map((p, idx) => {
            const x = p.i * stepX;
            const y = height - ((p.v - min) / span) * height;
            return `${idx === 0 ? "M" : "L"}${x.toFixed(1)},${y.toFixed(1)}`;
        })
        .join(" ");

    return (
        <svg
            width={width}
            height={height}
            viewBox={`0 0 ${width} ${height}`}
            role="img"
            aria-label="Daily trend"
            className="overflow-visible"
        >
            <path
                d={d}
                fill="none"
                stroke={color}
                strokeWidth="1.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}
