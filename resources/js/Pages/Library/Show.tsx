import { Head, Link, useForm, usePage } from "@inertiajs/react";
import { ArrowLeft, CheckCircle2, Plus, Trash2 } from "lucide-react";
import { type FormEventHandler } from "react";

import { Button } from "@/Components/ui/Button";
import AppLayout from "@/Layouts/AppLayout";
import { cn } from "@/lib/utils";

interface Item {
    id: number;
    source: string;
    title: string;
    body: string | null;
    mediaUrl: string | null;
    note: string | null;
}
interface Option {
    id: number;
    label: string;
}

export default function BoardShow({
    board,
    items,
    sources,
}: {
    board: { id: number; name: string; description: string | null };
    items: Item[];
    sources: { ads: Option[]; competitorAds: Option[] };
}) {
    const { flash } = usePage().props;
    const { data, setData, post, processing, reset } = useForm<{
        source: "ad" | "competitor_ad" | "external";
        source_id: string;
        title: string;
        body: string;
        media_url: string;
        note: string;
    }>({
        source: "ad",
        source_id: "",
        title: "",
        body: "",
        media_url: "",
        note: "",
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(`/library/${board.id}/items`, { onSuccess: () => reset() });
    };

    const options =
        data.source === "ad"
            ? sources.ads
            : data.source === "competitor_ad"
              ? sources.competitorAds
              : [];

    return (
        <AppLayout>
            <Head title={board.name} />
            <div className="mx-auto max-w-5xl">
                <Link
                    href="/library"
                    className="mb-4 inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-slate-200"
                >
                    <ArrowLeft className="size-3" />
                    All boards
                </Link>

                <div className="mb-6">
                    <h1 className="font-display text-2xl font-bold text-slate-100">
                        {board.name}
                    </h1>
                    {board.description && (
                        <p className="mt-1 text-sm text-muted-foreground">
                            {board.description}
                        </p>
                    )}
                </div>

                {flash?.status && (
                    <div className="mb-6 flex items-start gap-2 rounded-md border border-winner/30 bg-winner/10 px-4 py-3 text-sm text-winner">
                        <CheckCircle2 className="mt-0.5 size-4 shrink-0" />
                        <span>{flash.status}</span>
                    </div>
                )}

                <form
                    onSubmit={submit}
                    className="mb-8 space-y-3 rounded-lg border border-hairline bg-panel p-4"
                >
                    <div className="flex flex-wrap gap-1">
                        {(
                            [
                                ["ad", "Our ad"],
                                ["competitor_ad", "Competitor ad"],
                                ["external", "Paste external"],
                            ] as const
                        ).map(([s, label]) => (
                            <button
                                key={s}
                                type="button"
                                onClick={() => setData("source", s)}
                                className={cn(
                                    "rounded-md border px-3 py-1.5 text-xs transition-colors",
                                    data.source === s
                                        ? "border-amber/40 bg-amber/10 text-amber"
                                        : "border-hairline text-muted-foreground hover:text-slate-200",
                                )}
                            >
                                {label}
                            </button>
                        ))}
                    </div>

                    {data.source === "external" ? (
                        <div className="space-y-2">
                            <input
                                value={data.title}
                                onChange={(e) => setData("title", e.target.value)}
                                placeholder="Title"
                                className="w-full rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-slate-100"
                            />
                            <textarea
                                value={data.body}
                                onChange={(e) => setData("body", e.target.value)}
                                rows={2}
                                placeholder="Ad copy / notes"
                                className="w-full rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-slate-100"
                            />
                            <input
                                value={data.media_url}
                                onChange={(e) => setData("media_url", e.target.value)}
                                placeholder="Media URL (optional)"
                                className="w-full rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-slate-100"
                            />
                        </div>
                    ) : (
                        <select
                            value={data.source_id}
                            onChange={(e) => setData("source_id", e.target.value)}
                            className="w-full rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-slate-100"
                        >
                            <option value="">Select…</option>
                            {options.map((o) => (
                                <option key={o.id} value={o.id}>
                                    {o.label}
                                </option>
                            ))}
                        </select>
                    )}

                    <input
                        value={data.note}
                        onChange={(e) => setData("note", e.target.value)}
                        placeholder="Why keep this? (optional note)"
                        className="w-full rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-slate-100"
                    />
                    <Button type="submit" disabled={processing}>
                        <Plus className="size-4" />
                        Add to board
                    </Button>
                </form>

                {items.length === 0 ? (
                    <p className="rounded-lg border border-dashed border-hairline bg-panel p-8 text-center text-sm text-muted-foreground">
                        Nothing saved yet.
                    </p>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2">
                        {items.map((item) => (
                            <div
                                key={item.id}
                                className="flex flex-col rounded-lg border border-hairline bg-panel p-4"
                            >
                                <div className="mb-2 flex items-center justify-between gap-2">
                                    <span className="rounded-full border border-hairline px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wider text-muted-foreground">
                                        {item.source.replace("_", " ")}
                                    </span>
                                    <Link
                                        href={`/library/items/${item.id}`}
                                        method="delete"
                                        as="button"
                                        className="text-muted-foreground hover:text-cut"
                                    >
                                        <Trash2 className="size-3.5" />
                                    </Link>
                                </div>
                                <h3 className="text-sm font-medium text-slate-100">
                                    {item.title}
                                </h3>
                                {item.body && (
                                    <p className="mt-1 line-clamp-3 text-sm text-muted-foreground">
                                        {item.body}
                                    </p>
                                )}
                                {item.note && (
                                    <p className="mt-2 border-l-2 border-amber/40 pl-2 text-xs italic text-slate-300">
                                        {item.note}
                                    </p>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
