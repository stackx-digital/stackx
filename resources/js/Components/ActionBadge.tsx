import { cn } from "@/lib/utils";

/** Deterministic action pill (§3): scale / keep / cut. */
export function ActionBadge({
    action,
    className,
}: {
    action: string | null;
    className?: string;
}) {
    const styles: Record<string, string> = {
        scale: "bg-amber/15 text-amber border-amber/30",
        keep: "bg-neutral/15 text-neutral border-neutral/30",
        cut: "bg-cut/15 text-cut border-cut/30",
    };

    const label = action ?? "unscored";
    const style = action ? styles[action] : "bg-hairline/40 text-muted-foreground border-hairline";

    return (
        <span
            className={cn(
                "inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider",
                style,
                className,
            )}
        >
            {label}
        </span>
    );
}
