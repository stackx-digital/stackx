import { Link, useForm } from "@inertiajs/react";
import { type FormEventHandler } from "react";

import { AuthShell, Field, inputClass } from "@/Components/auth/AuthShell";
import { Button } from "@/Components/ui/Button";

/** Email + password sign-in (SaaS). */
export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword?: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: "",
        password: "",
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post("/login", { onFinish: () => reset("password") });
    };

    return (
        <AuthShell
            title="Sign in"
            heading="Sign in to your workspace"
            status={status}
            footer={
                <>
                    New to STACKx?{" "}
                    <Link href="/register" className="text-amber hover:underline">
                        Create an account
                    </Link>
                </>
            }
        >
            <form onSubmit={submit}>
                <Field id="email" label="Email" error={errors.email}>
                    <input
                        id="email"
                        type="email"
                        required
                        autoFocus
                        autoComplete="username"
                        value={data.email}
                        onChange={(e) => setData("email", e.target.value)}
                        placeholder="you@company.com"
                        className={inputClass}
                    />
                </Field>

                <Field id="password" label="Password" error={errors.password}>
                    <input
                        id="password"
                        type="password"
                        required
                        autoComplete="current-password"
                        value={data.password}
                        onChange={(e) => setData("password", e.target.value)}
                        placeholder="••••••••"
                        className={inputClass}
                    />
                </Field>

                <div className="mb-4 flex items-center justify-between">
                    <label className="flex items-center gap-2 text-xs text-muted-foreground">
                        <input
                            type="checkbox"
                            checked={data.remember}
                            onChange={(e) =>
                                setData("remember", e.target.checked)
                            }
                            className="rounded border-hairline bg-ink text-amber focus:ring-amber/40"
                        />
                        Remember me
                    </label>
                    {canResetPassword && (
                        <Link
                            href="/forgot-password"
                            className="text-xs text-muted-foreground hover:text-slate-200"
                        >
                            Forgot password?
                        </Link>
                    )}
                </div>

                <Button type="submit" className="w-full" disabled={processing}>
                    {processing ? "Signing in…" : "Sign in"}
                </Button>
            </form>
        </AuthShell>
    );
}
