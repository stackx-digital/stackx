import { type LucideIcon } from "lucide-react";

/** Reusable empty state for pillars not yet built (P2–P5). */
export function ComingSoon({
  pillar,
  title,
  description,
  icon: Icon,
}: {
  pillar: string;
  title: string;
  description: string;
  icon: LucideIcon;
}) {
  return (
    <div className="flex min-h-[60vh] flex-col items-center justify-center text-center">
      <div className="mb-4 flex size-14 items-center justify-center rounded-xl border border-hairline bg-panel">
        <Icon className="size-6 text-muted-foreground" />
      </div>
      <span className="text-xs font-medium uppercase tracking-wider text-amber">
        {pillar} · Coming soon
      </span>
      <h1 className="mt-2 font-display text-2xl font-bold">{title}</h1>
      <p className="mt-2 max-w-md text-sm text-muted-foreground">
        {description}
      </p>
    </div>
  );
}
