import { Head, useForm } from "@inertiajs/react";
import { type FormEventHandler } from "react";

import { Button } from "@/Components/ui/Button";

/**
 * Passwordless login. Submits an email; the server emails a magic link only
 * to allowlisted addresses and always shows the same confirmation.
 */
export default function Login({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({ email: "" });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post("/login");
    };

    return (
        <>
            <Head title="Sign in" />
            <main className="flex min-h-screen items-center justify-center bg-ink px-6">
                <div className="w-full max-w-sm">
                    <div className="mb-8 text-center">
                        <h1 className="font-display text-2xl font-bold tracking-tight text-slate-100">
                            STACK<span className="text-amber">x</span>
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Ad Intelligence — internal access
                        </p>
                    </div>

                    {status && (
                        <div className="mb-4 rounded-md border border-winner/30 bg-winner/10 px-4 py-3 text-sm text-winner">
                            {status}
                        </div>
                    )}

                    <form
                        onSubmit={submit}
                        className="rounded-lg border border-hairline bg-panel p-6"
                    >
                        <label
                            htmlFor="email"
                            className="mb-2 block text-xs font-medium uppercase tracking-wide text-muted-foreground"
                        >
                            Team email
                        </label>
                        <input
                            id="email"
                            type="email"
                            required
                            autoComplete="email"
                            value={data.email}
                            onChange={(e) => setData("email", e.target.value)}
                            placeholder="you@stackx.my"
                            className="mb-1 w-full rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-slate-100 placeholder:text-muted-foreground/60 focus-visible:outline-none"
                        />
                        {errors.email && (
                            <p className="mb-2 text-xs text-cut">
                                {errors.email}
                            </p>
                        )}
                        <Button
                            type="submit"
                            className="mt-3 w-full"
                            disabled={processing}
                        >
                            {processing ? "Sending…" : "Send sign-in link"}
                        </Button>
                    </form>

                    <p className="mt-4 text-center text-xs text-muted-foreground">
                        Access is restricted to the STACKx team allowlist.
                    </p>
                </div>
            </main>
        </>
    );
}
