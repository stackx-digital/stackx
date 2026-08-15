import { Head, Link, router, usePage } from "@inertiajs/react";
import {
    AlertTriangle,
    Bell,
    CheckCircle2,
    RadarIcon,
    TrendingUp,
} from "lucide-react";

import { Button } from "@/Components/ui/Button";
import AppLayout from "@/Layouts/AppLayout";
import { cn } from "@/lib/utils";

interface AlertRow {
    id: number;
    type: "fatigue" | "scale" | "cut" | string;
    severity: "low" | "medium" | "high" | string;
    title: string;
    detail: string | null;
    ad: string | null;
    createdAt: string | null;
}

export default function AlertsIndex({ alerts }: { alerts: AlertRow[] }) {
    const { flash } = usePage().props;

    return (
        <AppLayout>
            <Head title="Alerts" />
            <div className="mx-auto max-w-4xl">
                <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <span className="text-xs font-medium uppercase tracking-wider text-amber">
                            Alerts · Fatigue & scaling
                        </span>
                        <h1 className="mt-1 font-display text-2xl font-bold text-slate-100">
                            Performance alerts
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Deterministic signals from daily metrics — no AI.
                            Runs daily; detect on demand too.
                        </p>
                    </div>
                    <Button onClick={() => router.post("/alerts/detect")}>
                        <RadarIcon className="size-4" />
                        Detect now
                    </Button>
                </div>

                {flash?.status && (
                    <div className="mb-6 flex items-start gap-2 rounded-md border border-winner/30 bg-winner/10 px-4 py-3 text-sm text-winner">
                        <CheckCircle2 className="mt-0.5 size-4 shrink-0" />
                        <span>{flash.status}</span>
                    </div>
                )}

                {alerts.length === 0 ? (
                    <div className="rounded-lg border border-dashed border-hairline bg-panel p-10 text-center">
                        <Bell className="mx-auto mb-3 size-6 text-muted-foreground" />
                        <p className="text-sm text-muted-foreground">
                            No active alerts. Click <strong>Detect now</strong>{" "}
                            after importing a few days of metrics.
                        </p>
                    </div>
                ) : (
                    <div className="space-y-3">
                        {alerts.map((a) => (
                            <AlertRowCard key={a.id} alert={a} />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

function AlertRowCard({ alert }: { alert: AlertRow }) {
    const isFatigue = alert.type === "fatigue";
    const Icon = isFatigue ? AlertTriangle : TrendingUp;
    const accent = isFatigue ? "text-cut" : "text-winner";

    return (
        <div className="flex items-start justify-between gap-4 rounded-lg border border-hairline bg-panel p-4">
            <div className="flex items-start gap-3">
                <Icon className={cn("mt-0.5 size-5 shrink-0", accent)} />
                <div>
                    <div className="flex items-center gap-2">
                        <span className="text-sm font-semibold text-slate-100">
                            {alert.title}
                        </span>
                        <span
                            className={cn(
                                "rounded-full border px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wider",
                                alert.severity === "high"
                                    ? "border-cut/30 bg-cut/10 text-cut"
                                    : "border-hairline text-muted-foreground",
                            )}
                        >
                            {alert.severity}
                        </span>
                    </div>
                    {alert.ad && (
                        <div className="text-xs text-muted-foreground">
                            {alert.ad}
                        </div>
                    )}
                    <p className="mt-1 text-sm text-slate-300">{alert.detail}</p>
                </div>
            </div>
            <Link
                href={`/alerts/${alert.id}/resolve`}
                method="post"
                as="button"
                className="shrink-0 rounded-md px-2 py-1 text-xs text-muted-foreground hover:bg-ink hover:text-slate-200"
            >
                Dismiss
            </Link>
        </div>
    );
}
