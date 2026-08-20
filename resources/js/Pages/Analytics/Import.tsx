import { Head, Link, useForm } from "@inertiajs/react";
import {
    UploadCloud,
    AlertTriangle,
    CheckCircle2,
    LineChart,
    Settings,
} from "lucide-react";
import { useRef, useState, type DragEvent, type FormEventHandler } from "react";

import { Button } from "@/Components/ui/Button";
import AppLayout from "@/Layouts/AppLayout";

const FIELD_LABELS: Record<string, string> = {
    ad_name: "Ad name",
    meta_ad_id: "Ad ID",
    ad_status: "Status",
    date: "Date",
    spend: "Spend",
    impressions: "Impressions",
    reach: "Reach",
    ctr_all: "CTR (all)",
    ctr_link: "CTR (link)",
    cpc: "CPC",
    cpm: "CPM",
    thruplays: "ThruPlays",
    video_3s: "3s video plays",
    results: "Results",
    cost_per_result: "Cost / result",
    roas: "ROAS",
};

interface Preview {
    fileName: string;
    rowCount: number;
    mapping: Record<string, string>;
    recognized: string[];
    unknownHeaders: string[];
    missingImportant: string[];
    metricFields: string[];
    sampleRows: Array<Record<string, string | number | null>>;
}

export default function Import({
    preview,
    importToken,
    metaConnected,
}: {
    preview?: Preview;
    importToken?: string;
    metaConnected?: boolean;
}) {
    return (
        <AppLayout>
            <Head title="Import Meta CSV" />
            <div className="mx-auto max-w-4xl">
                <div className="mb-6">
                    <span className="text-xs font-medium uppercase tracking-wider text-amber">
                        P1 · Data ingest
                    </span>
                    <h1 className="mt-1 font-display text-2xl font-bold text-slate-100">
                        Get your ads in
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Sync live from Meta, or export from Ads Manager and drop
                        the CSV here. Column names vary by locale/currency — we
                        map them flexibly and show exactly what was recognized.
                    </p>
                </div>

                {!preview && <MetaSyncCard connected={metaConnected ?? false} />}

                {preview ? (
                    <PreviewPanel preview={preview} importToken={importToken!} />
                ) : (
                    <UploadPanel />
                )}
            </div>
        </AppLayout>
    );
}

function MetaSyncCard({ connected }: { connected: boolean }) {
    const { post, processing } = useForm({});

    const sync: FormEventHandler = (e) => {
        e.preventDefault();
        post("/analytics/meta/sync");
    };

    return (
        <div className="mb-5 flex flex-wrap items-center gap-4 rounded-lg border border-hairline bg-panel p-5">
            <LineChart className="size-5 shrink-0 text-amber" />
            <div className="min-w-0 flex-1">
                <h2 className="font-display text-sm font-semibold text-slate-100">
                    Sync live from Meta
                </h2>
                <p className="mt-0.5 text-xs text-muted-foreground">
                    {connected
                        ? "Pull the last 30 days of ad-level performance straight from your Meta ad account."
                        : "Connect a Meta System User token and ad account id in Settings to enable live sync."}
                </p>
            </div>
            {connected ? (
                <form onSubmit={sync}>
                    <Button type="submit" disabled={processing}>
                        <LineChart className="size-4" />
                        {processing ? "Syncing…" : "Sync now"}
                    </Button>
                </form>
            ) : (
                <Link href="/settings">
                    <Button variant="outline">
                        <Settings className="size-4" />
                        Connect Meta
                    </Button>
                </Link>
            )}
        </div>
    );
}

function UploadPanel() {
    const inputRef = useRef<HTMLInputElement>(null);
    const [dragging, setDragging] = useState(false);
    const { setData, post, processing, errors } = useForm<{ file: File | null }>(
        { file: null },
    );

    const submit = (file: File | null) => {
        if (!file) return;
        setData("file", file);
        post("/analytics/import/preview", { forceFormData: true });
    };

    const onDrop = (e: DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        setDragging(false);
        submit(e.dataTransfer.files?.[0] ?? null);
    };

    const onSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        submit(inputRef.current?.files?.[0] ?? null);
    };

    return (
        <form onSubmit={onSubmit}>
            <div
                role="button"
                tabIndex={0}
                onClick={() => inputRef.current?.click()}
                onKeyDown={(e) =>
                    (e.key === "Enter" || e.key === " ") &&
                    inputRef.current?.click()
                }
                onDragOver={(e) => {
                    e.preventDefault();
                    setDragging(true);
                }}
                onDragLeave={() => setDragging(false)}
                onDrop={onDrop}
                className={`flex min-h-[240px] cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed p-8 text-center transition-colors ${
                    dragging
                        ? "border-amber bg-amber/5"
                        : "border-hairline bg-panel hover:border-amber/50"
                }`}
            >
                <UploadCloud className="mb-3 size-8 text-muted-foreground" />
                <p className="text-sm text-slate-200">
                    Drag &amp; drop your Meta CSV, or{" "}
                    <span className="text-amber">browse</span>
                </p>
                <p className="mt-1 text-xs text-muted-foreground">
                    Ads Manager export · up to 10 MB
                </p>
                <input
                    ref={inputRef}
                    type="file"
                    accept=".csv,text/csv,text/plain"
                    className="hidden"
                    onChange={(e) => submit(e.target.files?.[0] ?? null)}
                />
            </div>
            {errors.file && (
                <p className="mt-2 text-sm text-cut">{errors.file}</p>
            )}
            {processing && (
                <p className="mt-3 text-sm text-muted-foreground">Parsing…</p>
            )}
        </form>
    );
}

function PreviewPanel({
    preview,
    importToken,
}: {
    preview: Preview;
    importToken: string;
}) {
    const { post, processing } = useForm({ import_token: importToken });

    const confirm: FormEventHandler = (e) => {
        e.preventDefault();
        post("/analytics/import");
    };

    return (
        <div className="space-y-5">
            <div className="rounded-lg border border-hairline bg-panel p-5">
                <div className="flex items-center justify-between">
                    <h2 className="font-display text-sm font-semibold text-slate-100">
                        {preview.fileName}
                    </h2>
                    <span className="tabular text-xs text-muted-foreground">
                        {preview.rowCount} rows
                    </span>
                </div>

                {preview.missingImportant.length > 0 && (
                    <div className="mt-4 flex items-start gap-2 rounded-md border border-cut/30 bg-cut/10 px-3 py-2 text-sm text-cut">
                        <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                        <span>
                            Missing important column(s):{" "}
                            {preview.missingImportant
                                .map((f) => FIELD_LABELS[f] ?? f)
                                .join(", ")}
                            . Those metrics will be blank (never guessed).
                        </span>
                    </div>
                )}

                <h3 className="mt-5 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                    Recognized columns
                </h3>
                <div className="mt-2 overflow-x-auto">
                    <table className="w-full text-sm">
                        <tbody>
                            {Object.entries(preview.mapping).map(
                                ([field, header]) => (
                                    <tr
                                        key={field}
                                        className="border-b border-hairline/50"
                                    >
                                        <td className="py-1.5 pr-4 text-slate-200">
                                            {FIELD_LABELS[field] ?? field}
                                        </td>
                                        <td className="py-1.5 font-mono text-xs text-muted-foreground">
                                            {header}
                                        </td>
                                    </tr>
                                ),
                            )}
                        </tbody>
                    </table>
                </div>

                {preview.unknownHeaders.length > 0 && (
                    <p className="mt-4 text-xs text-muted-foreground">
                        Ignored columns: {preview.unknownHeaders.join(", ")}
                    </p>
                )}
            </div>

            <div className="flex items-center gap-3">
                <form onSubmit={confirm}>
                    <Button type="submit" disabled={processing}>
                        <CheckCircle2 className="size-4" />
                        {processing ? "Importing…" : "Confirm import"}
                    </Button>
                </form>
                <Link
                    href="/analytics/import"
                    className="text-sm text-muted-foreground hover:text-slate-200"
                >
                    Choose a different file
                </Link>
            </div>
        </div>
    );
}
