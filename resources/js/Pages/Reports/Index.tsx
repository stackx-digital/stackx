import { Head, Link, router, usePage } from "@inertiajs/react";
import {
    CheckCircle2,
    Copy,
    ExternalLink,
    FileText,
    Send,
    Trash2,
} from "lucide-react";
import { useState } from "react";

import { Button } from "@/Components/ui/Button";
import AppLayout from "@/Layouts/AppLayout";

interface ReportRow {
    id: number;
    title: string;
    url: string;
    createdBy: string | null;
    createdAt: string | null;
}

export default function ReportsIndex({
    reports,
    slackConfigured,
}: {
    reports: ReportRow[];
    slackConfigured: boolean;
}) {
    const { flash } = usePage().props;
    const [copiedId, setCopiedId] = useState<number | null>(null);

    const copy = async (row: ReportRow) => {
        try {
            await navigator.clipboard.writeText(row.url);
            setCopiedId(row.id);
            setTimeout(() => setCopiedId(null), 1500);
        } catch {
            /* no-op */
        }
    };

    return (
        <AppLayout>
            <Head title="Reports" />
            <div className="mx-auto max-w-4xl">
                <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <span className="text-xs font-medium uppercase tracking-wider text-amber">
                            P5 · Reports
                        </span>
                        <h1 className="mt-1 font-display text-2xl font-bold text-slate-100">
                            Shareable reports
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Snapshot the current analytics into a read-only link
                            anyone can open — no login needed. Revoke by deleting.
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            variant="outline"
                            onClick={() => router.post("/reports/slack")}
                            disabled={!slackConfigured}
                            title={
                                slackConfigured
                                    ? undefined
                                    : "Set SLACK_WEBHOOK_URL to enable"
                            }
                        >
                            <Send className="size-4" />
                            Send to Slack
                        </Button>
                        <Button onClick={() => router.post("/reports")}>
                            <FileText className="size-4" />
                            Create report
                        </Button>
                    </div>
                </div>

                {flash?.status && (
                    <div className="mb-6 flex items-start gap-2 rounded-md border border-winner/30 bg-winner/10 px-4 py-3 text-sm text-winner">
                        <CheckCircle2 className="mt-0.5 size-4 shrink-0" />
                        <span className="break-all">{flash.status}</span>
                    </div>
                )}

                {!slackConfigured && (
                    <p className="mb-4 text-xs text-muted-foreground">
                        Weekly Slack summary is scheduled (Mondays 9am). Set{" "}
                        <code>SLACK_WEBHOOK_URL</code> to activate it.
                    </p>
                )}

                {reports.length === 0 ? (
                    <div className="rounded-lg border border-dashed border-hairline bg-panel p-10 text-center text-sm text-muted-foreground">
                        No reports yet — click <strong>Create report</strong> to
                        snapshot your current analytics.
                    </div>
                ) : (
                    <div className="divide-y divide-hairline overflow-hidden rounded-lg border border-hairline bg-panel">
                        {reports.map((r) => (
                            <div
                                key={r.id}
                                className="flex flex-wrap items-center justify-between gap-3 p-4"
                            >
                                <div>
                                    <div className="text-sm font-medium text-slate-100">
                                        {r.title}
                                    </div>
                                    <div className="text-[11px] text-muted-foreground">
                                        {r.createdBy ? `${r.createdBy} · ` : ""}
                                        {r.createdAt}
                                    </div>
                                </div>
                                <div className="flex items-center gap-1">
                                    <button
                                        onClick={() => copy(r)}
                                        className="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs text-muted-foreground hover:bg-ink hover:text-slate-200"
                                    >
                                        {copiedId === r.id ? (
                                            <CheckCircle2 className="size-3.5 text-winner" />
                                        ) : (
                                            <Copy className="size-3.5" />
                                        )}
                                        {copiedId === r.id ? "Copied" : "Copy link"}
                                    </button>
                                    <a
                                        href={r.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs text-neutral hover:bg-ink"
                                    >
                                        Open <ExternalLink className="size-3" />
                                    </a>
                                    <Link
                                        href={`/reports/${r.id}`}
                                        method="delete"
                                        as="button"
                                        className="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs text-cut hover:bg-cut/10"
                                    >
                                        <Trash2 className="size-3.5" />
                                        Revoke
                                    </Link>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
