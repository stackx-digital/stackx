import { Head, useForm, usePage } from "@inertiajs/react";
import {
    BrainCircuit,
    Check,
    Eye,
    KeyRound,
    Search,
    Send,
    type LucideIcon,
} from "lucide-react";

import { Button } from "@/Components/ui/Button";
import AppLayout from "@/Layouts/AppLayout";
import { cn } from "@/lib/utils";

type SecretKey =
    | "anthropic_api_key"
    | "openai_api_key"
    | "voyage_api_key"
    | "meta_ad_library_token"
    | "slack_webhook_url";

interface Props {
    settings: {
        aiProvider: string | null;
        anthropicModel: string | null;
        openaiModel: string | null;
        embeddingProvider: string | null;
        configured: Record<SecretKey, boolean>;
    };
    capabilities: {
        ai: boolean;
        embedding: boolean;
        adLibrary: boolean;
        slack: boolean;
    };
    defaults: {
        anthropicModel: string;
        openaiModel: string;
        envAiKey: boolean;
    };
}

const inputClass =
    "w-full rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-slate-100 placeholder:text-muted-foreground/60 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-amber/40";

export default function SettingsIndex({
    settings,
    capabilities,
    defaults,
}: Props) {
    const { flash } = usePage().props;

    const { data, setData, put, processing, errors, recentlySuccessful } =
        useForm({
            ai_provider: settings.aiProvider ?? "",
            anthropic_model: settings.anthropicModel ?? "",
            openai_model: settings.openaiModel ?? "",
            embedding_provider: settings.embeddingProvider ?? "",
            anthropic_api_key: "",
            openai_api_key: "",
            voyage_api_key: "",
            meta_ad_library_token: "",
            slack_webhook_url: "",
            remove: [] as SecretKey[],
        });

    const toggleRemove = (key: SecretKey, on: boolean) =>
        setData(
            "remove",
            on ? [...data.remove, key] : data.remove.filter((k) => k !== key),
        );

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put("/settings", { preserveScroll: true });
    };

    return (
        <AppLayout>
            <Head title="Settings" />
            <div className="mx-auto max-w-3xl">
                <header className="mb-6">
                    <span className="text-xs font-medium uppercase tracking-wider text-amber">
                        Workspace settings
                    </span>
                    <h1 className="mt-1 font-display text-2xl font-bold text-slate-100">
                        API keys &amp; providers
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Bring your own credentials. Keys are encrypted at rest
                        and never shown again after saving — only your workspace
                        can use them.
                    </p>
                </header>

                {(flash?.status || recentlySuccessful) && (
                    <div className="mb-5 rounded-md border border-winner/30 bg-winner/10 px-4 py-3 text-sm text-winner">
                        {flash?.status ?? "Settings saved."}
                    </div>
                )}

                <div className="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <Capability label="AI" on={capabilities.ai} icon={BrainCircuit} />
                    <Capability
                        label="Embeddings"
                        on={capabilities.embedding}
                        icon={Search}
                    />
                    <Capability
                        label="Ad Library"
                        on={capabilities.adLibrary}
                        icon={Eye}
                    />
                    <Capability label="Slack" on={capabilities.slack} icon={Send} />
                </div>

                <form onSubmit={submit}>
                    <Section
                        icon={BrainCircuit}
                        title="AI text layer"
                        blurb="Powers creative tagging, recommendations, copy variations, briefs, and vision tagging."
                    >
                        <Row label="Default provider">
                            <select
                                value={data.ai_provider}
                                onChange={(e) =>
                                    setData("ai_provider", e.target.value)
                                }
                                className={inputClass}
                            >
                                <option value="">Use deployment default</option>
                                <option value="anthropic">Anthropic (Claude)</option>
                                <option value="openai">OpenAI (GPT)</option>
                            </select>
                        </Row>

                        <SecretRow
                            label="Anthropic API key"
                            name="anthropic_api_key"
                            data={data}
                            setData={setData}
                            configured={settings.configured.anthropic_api_key}
                            error={errors.anthropic_api_key}
                            toggleRemove={toggleRemove}
                            placeholder="sk-ant-…"
                        />
                        <Row label="Anthropic model">
                            <input
                                value={data.anthropic_model}
                                onChange={(e) =>
                                    setData("anthropic_model", e.target.value)
                                }
                                placeholder={defaults.anthropicModel}
                                className={inputClass}
                            />
                        </Row>

                        <SecretRow
                            label="OpenAI API key"
                            name="openai_api_key"
                            data={data}
                            setData={setData}
                            configured={settings.configured.openai_api_key}
                            error={errors.openai_api_key}
                            toggleRemove={toggleRemove}
                            placeholder="sk-…"
                        />
                        <Row label="OpenAI model">
                            <input
                                value={data.openai_model}
                                onChange={(e) =>
                                    setData("openai_model", e.target.value)
                                }
                                placeholder={defaults.openaiModel}
                                className={inputClass}
                            />
                        </Row>
                    </Section>

                    <Section
                        icon={Search}
                        title="Embeddings"
                        blurb="Powers semantic Ad Discovery (pgvector). OpenAI reuses your OpenAI key above."
                    >
                        <Row label="Provider">
                            <select
                                value={data.embedding_provider}
                                onChange={(e) =>
                                    setData("embedding_provider", e.target.value)
                                }
                                className={inputClass}
                            >
                                <option value="">Use deployment default</option>
                                <option value="openai">OpenAI</option>
                                <option value="voyage">Voyage</option>
                            </select>
                        </Row>
                        <SecretRow
                            label="Voyage API key"
                            name="voyage_api_key"
                            data={data}
                            setData={setData}
                            configured={settings.configured.voyage_api_key}
                            error={errors.voyage_api_key}
                            toggleRemove={toggleRemove}
                            placeholder="pa-…"
                        />
                    </Section>

                    <Section
                        icon={Eye}
                        title="Brand Spy — Meta Ad Library"
                        blurb="A token turns on live competitor sync. Without it, Spy runs on saved/demo data."
                    >
                        <SecretRow
                            label="Meta Ad Library token"
                            name="meta_ad_library_token"
                            data={data}
                            setData={setData}
                            configured={settings.configured.meta_ad_library_token}
                            error={errors.meta_ad_library_token}
                            toggleRemove={toggleRemove}
                            placeholder="EAA…"
                        />
                    </Section>

                    <Section
                        icon={Send}
                        title="Reports — Slack"
                        blurb="Incoming webhook URL for weekly report summaries."
                    >
                        <SecretRow
                            label="Slack webhook URL"
                            name="slack_webhook_url"
                            data={data}
                            setData={setData}
                            configured={settings.configured.slack_webhook_url}
                            error={errors.slack_webhook_url}
                            toggleRemove={toggleRemove}
                            placeholder="https://hooks.slack.com/services/…"
                        />
                    </Section>

                    <div className="flex items-center justify-end gap-3 pt-2">
                        <Button type="submit" disabled={processing}>
                            {processing ? "Saving…" : "Save settings"}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

function Capability({
    label,
    on,
    icon: Icon,
}: {
    label: string;
    on: boolean;
    icon: LucideIcon;
}) {
    return (
        <div className="flex items-center gap-2 rounded-md border border-hairline bg-panel px-3 py-2">
            <Icon
                className={cn(
                    "size-4 shrink-0",
                    on ? "text-winner" : "text-muted-foreground",
                )}
            />
            <div className="min-w-0">
                <div className="truncate text-xs text-slate-200">{label}</div>
                <div
                    className={cn(
                        "text-[10px] uppercase tracking-wider",
                        on ? "text-winner" : "text-muted-foreground",
                    )}
                >
                    {on ? "Ready" : "Not set"}
                </div>
            </div>
        </div>
    );
}

function Section({
    icon: Icon,
    title,
    blurb,
    children,
}: {
    icon: LucideIcon;
    title: string;
    blurb: string;
    children: React.ReactNode;
}) {
    return (
        <section className="mb-5 rounded-lg border border-hairline bg-panel p-5">
            <div className="mb-4 flex items-start gap-3">
                <Icon className="mt-0.5 size-4 shrink-0 text-amber" />
                <div>
                    <h2 className="font-display text-sm font-semibold text-slate-100">
                        {title}
                    </h2>
                    <p className="mt-0.5 text-xs text-muted-foreground">
                        {blurb}
                    </p>
                </div>
            </div>
            <div className="space-y-4">{children}</div>
        </section>
    );
}

function Row({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <div className="grid gap-1.5 sm:grid-cols-[180px_1fr] sm:items-center sm:gap-4">
            <label className="text-xs font-medium text-muted-foreground">
                {label}
            </label>
            <div>{children}</div>
        </div>
    );
}

function SecretRow({
    label,
    name,
    data,
    setData,
    configured,
    error,
    toggleRemove,
    placeholder,
}: {
    label: string;
    name: SecretKey;
    data: Record<string, unknown>;
    setData: (key: SecretKey, value: string) => void;
    configured: boolean;
    error?: string;
    toggleRemove: (key: SecretKey, on: boolean) => void;
    placeholder: string;
}) {
    return (
        <Row label={label}>
            <div className="flex items-center gap-2">
                <div className="relative flex-1">
                    <KeyRound className="pointer-events-none absolute left-2.5 top-1/2 size-3.5 -translate-y-1/2 text-muted-foreground/60" />
                    <input
                        type="password"
                        autoComplete="off"
                        value={data[name] as string}
                        onChange={(e) => setData(name, e.target.value)}
                        placeholder={
                            configured ? "•••••••• (saved)" : placeholder
                        }
                        className={cn(inputClass, "pl-8")}
                    />
                </div>
                {configured && (
                    <span
                        className="inline-flex items-center gap-1 rounded-md border border-winner/30 bg-winner/10 px-2 py-1 text-[10px] font-medium uppercase tracking-wider text-winner"
                        title="A key is saved for this field"
                    >
                        <Check className="size-3" /> Set
                    </span>
                )}
            </div>
            {configured && (
                <label className="mt-1.5 flex items-center gap-2 text-[11px] text-muted-foreground">
                    <input
                        type="checkbox"
                        onChange={(e) => toggleRemove(name, e.target.checked)}
                        className="rounded border-hairline bg-ink text-cut focus:ring-cut/40"
                    />
                    Remove saved key
                </label>
            )}
            {error && <p className="mt-1 text-xs text-cut">{error}</p>}
        </Row>
    );
}
