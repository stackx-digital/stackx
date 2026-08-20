import { Head, Link, useForm, usePage } from "@inertiajs/react";
import { CheckCircle2, Library, Plus, Trash2 } from "lucide-react";
import { useState, type FormEventHandler } from "react";

import { Button } from "@/Components/ui/Button";
import AppLayout from "@/Layouts/AppLayout";

interface BoardRow {
    id: number;
    name: string;
    description: string | null;
    itemCount: number;
}

export default function LibraryIndex({ boards }: { boards: BoardRow[] }) {
    const { flash } = usePage().props;
    const [showAdd, setShowAdd] = useState(false);
    const { data, setData, post, processing, reset } = useForm({
        name: "",
        description: "",
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post("/library", { onSuccess: () => reset() });
    };

    return (
        <AppLayout>
            <Head title="Creative Library" />
            <div className="mx-auto max-w-5xl">
                <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <span className="text-xs font-medium uppercase tracking-wider text-amber">
                            Library · Swipe files
                        </span>
                        <h1 className="mt-1 font-display text-2xl font-bold text-slate-100">
                            Creative Library
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Save winners and inspiring competitor ads into boards.
                        </p>
                    </div>
                    <Button onClick={() => setShowAdd((v) => !v)}>
                        <Plus className="size-4" />
                        New board
                    </Button>
                </div>

                {flash?.status && (
                    <div className="mb-6 flex items-start gap-2 rounded-md border border-winner/30 bg-winner/10 px-4 py-3 text-sm text-winner">
                        <CheckCircle2 className="mt-0.5 size-4 shrink-0" />
                        <span>{flash.status}</span>
                    </div>
                )}

                {showAdd && (
                    <form
                        onSubmit={submit}
                        className="mb-6 flex flex-wrap items-end gap-3 rounded-lg border border-hairline bg-panel p-4"
                    >
                        <div className="flex-1">
                            <label className="mb-1 block text-[10px] uppercase tracking-wider text-muted-foreground">
                                Board name
                            </label>
                            <input
                                value={data.name}
                                onChange={(e) => setData("name", e.target.value)}
                                required
                                placeholder="e.g. Raya winners"
                                className="w-full rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-slate-100"
                            />
                        </div>
                        <div className="flex-1">
                            <label className="mb-1 block text-[10px] uppercase tracking-wider text-muted-foreground">
                                Description (optional)
                            </label>
                            <input
                                value={data.description}
                                onChange={(e) => setData("description", e.target.value)}
                                className="w-full rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-slate-100"
                            />
                        </div>
                        <Button type="submit" disabled={processing}>
                            Create
                        </Button>
                    </form>
                )}

                {boards.length === 0 ? (
                    <div className="rounded-lg border border-dashed border-hairline bg-panel p-10 text-center">
                        <Library className="mx-auto mb-3 size-6 text-muted-foreground" />
                        <p className="text-sm text-muted-foreground">
                            No boards yet — create one to start saving ads.
                        </p>
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 md:grid-cols-3">
                        {boards.map((b) => (
                            <div
                                key={b.id}
                                className="group flex flex-col rounded-lg border border-hairline bg-panel p-4"
                            >
                                <Link
                                    href={`/library/${b.id}`}
                                    className="flex-1"
                                >
                                    <h2 className="font-display text-sm font-semibold text-slate-100">
                                        {b.name}
                                    </h2>
                                    {b.description && (
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {b.description}
                                        </p>
                                    )}
                                    <p className="tabular mt-3 text-xs text-muted-foreground">
                                        {b.itemCount} item{b.itemCount === 1 ? "" : "s"}
                                    </p>
                                </Link>
                                <Link
                                    href={`/library/${b.id}`}
                                    method="delete"
                                    as="button"
                                    className="mt-3 inline-flex items-center gap-1 self-start text-[11px] text-muted-foreground opacity-0 transition-opacity hover:text-cut group-hover:opacity-100"
                                >
                                    <Trash2 className="size-3" />
                                    Delete
                                </Link>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
