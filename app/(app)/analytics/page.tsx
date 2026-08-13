import { ScoreMeter } from "@/components/score-meter";

/**
 * P1 Creative Analytics. Data ingest (M2), scoring (M3), and the full report
 * (M4) land here. For M1 this is the shell placeholder — it previews the
 * signature score meter so the cockpit theme is tangible.
 */
export default function AnalyticsPage() {
  return (
    <div className="mx-auto max-w-5xl">
      <div className="mb-6">
        <span className="text-xs font-medium uppercase tracking-wider text-amber">
          P1 · Creative Analytics
        </span>
        <h1 className="mt-1 font-display text-2xl font-bold">
          Account overview
        </h1>
        <p className="mt-1 text-sm text-muted-foreground">
          Import Meta ad data to see scored creatives, winners &amp; losers, and
          scale/cut actions. Data ingest lands in M2.
        </p>
      </div>

      <div className="rounded-lg border border-hairline bg-panel p-6">
        <div className="flex items-center justify-between">
          <h2 className="font-display text-sm font-semibold">
            Creative score meter
          </h2>
          <span className="text-[10px] uppercase tracking-wider text-muted-foreground">
            Preview · sample values
          </span>
        </div>
        <p className="mt-1 text-xs text-muted-foreground">
          The 4-segment signature meter — Hook / Watch / Click / Convert, each a
          percentile within the account. Scoring engine arrives in M3.
        </p>
        <div className="mt-5 max-w-md">
          <ScoreMeter hook={82} watch={64} click={38} convert={null} />
        </div>
      </div>
    </div>
  );
}
