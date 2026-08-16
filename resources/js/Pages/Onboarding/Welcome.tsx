import { Head, Link, router, usePage } from "@inertiajs/react";
import { Check, KeyRound, Sparkles, Upload, UploadCloud } from "lucide-react";
import { useState } from "react";

import { Button } from "@/Components/ui/Button";
import AppLayout from "@/Layouts/AppLayout";
import { cn } from "@/lib/utils";

interface Props {
    steps: {
        aiConfigured: boolean;
        hasData: boolean;
    };
    completed: boolean;
}

export default function Welcome({ steps }: Props) {
    const { flash } = usePage().props;
    const [loadingDemo, setLoadingDemo] = useState(false);

    const loadDemo = () => {
        setLoadingDemo(true);
        router.post(
            "/analytics/demo",
            {},
            { preserveScroll: true, onFinish: () => setLoadingDemo(false) },
        );
    };

    return (
        <AppLayout>
            <Head title="Welcome" />
            <div className="mx-auto max-w-2xl">
                <header className="mb-8 text-center">
                    <div className="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-amber/15">
                        <Sparkles className="size-6 text-amber" />
                    </div>
                    <h1 className="font-display text-2xl font-bold text-slate-100">
                        Welcome to STACK<span className="text-amber">x</span>
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Two quick steps and your workspace is ready to analyse
                        creative performance.
                    </p>
                </header>

                {flash?.status && (
                    <div className="mb-5 rounded-md border border-winner/30 bg-winner/10 px-4 py-3 text-sm text-winner">
                        {flash.status}
                    </div>
                )}

                <div className="space-y-4">
                    <StepCard
                        index={1}
                        done
                        icon={Check}
                        title="Account verified"
                        blurb="Your email is confirmed and your workspace is live."
                    />

                    <StepCard
                        index={2}
                        done={steps.aiConfigured}
                        icon={KeyRound}
                        title="Add an AI API key"
                        blurb="Bring your own Anthropic or OpenAI key to unlock creative tagging, recommendations, copy generation and vision — all optional, added anytime."
                    >
                        {!steps.aiConfigured && (
                            <Link href="/settings">
                                <Button variant="outline" size="sm">
                                    Open settings
                                </Button>
                            </Link>
                        )}
                    </StepCard>

                    <StepCard
                        index={3}
                        done={steps.hasData}
                        icon={UploadCloud}
                        title="Add your first ads"
                        blurb="Load a demo Raya campaign to explore instantly, or import your own Meta Ads CSV export."
                    >
                        {!steps.hasData && (
                            <div className="flex flex-wrap gap-2">
                                <Button
                                    size="sm"
                                    onClick={loadDemo}
                                    disabled={loadingDemo}
                                >
                                    {loadingDemo
                                        ? "Loading…"
                                        : "Load demo data"}
                                </Button>
                                <Link href="/analytics/import">
                                    <Button variant="outline" size="sm">
                                        <Upload className="size-3.5" />
                                        Import CSV
                                    </Button>
                                </Link>
                            </div>
                        )}
                    </StepCard>
                </div>

                <div className="mt-8 flex items-center justify-between">
                    <p className="text-xs text-muted-foreground">
                        You can always finish setup later from the dashboard.
                    </p>
                    <Link href="/welcome/complete" method="post" as="button">
                        <Button>Go to dashboard</Button>
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}

function StepCard({
    index,
    done,
    icon: Icon,
    title,
    blurb,
    children,
}: {
    index: number;
    done: boolean;
    icon: typeof Check;
    title: string;
    blurb: string;
    children?: React.ReactNode;
}) {
    return (
        <div
            className={cn(
                "flex gap-4 rounded-lg border p-5",
                done
                    ? "border-winner/30 bg-winner/5"
                    : "border-hairline bg-panel",
            )}
        >
            <div
                className={cn(
                    "flex size-9 shrink-0 items-center justify-center rounded-full",
                    done ? "bg-winner/20 text-winner" : "bg-ink text-amber",
                )}
            >
                {done ? (
                    <Check className="size-5" />
                ) : (
                    <Icon className="size-5" />
                )}
            </div>
            <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2">
                    <span className="text-[10px] font-medium uppercase tracking-wider text-muted-foreground">
                        Step {index}
                    </span>
                    {done && (
                        <span className="text-[10px] font-medium uppercase tracking-wider text-winner">
                            Done
                        </span>
                    )}
                </div>
                <h2 className="mt-0.5 font-display text-sm font-semibold text-slate-100">
                    {title}
                </h2>
                <p className="mt-1 text-xs text-muted-foreground">{blurb}</p>
                {children && <div className="mt-3">{children}</div>}
            </div>
        </div>
    );
}
