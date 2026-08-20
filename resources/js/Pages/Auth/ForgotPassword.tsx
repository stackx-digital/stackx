import { Link, useForm } from "@inertiajs/react";
import { type FormEventHandler } from "react";

import { AuthShell, Field, inputClass } from "@/Components/auth/AuthShell";
import { Button } from "@/Components/ui/Button";

/** Requests a password-reset link. */
export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({ email: "" });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post("/forgot-password");
    };

    return (
        <AuthShell
            title="Forgot password"
            heading="Reset your password"
            status={status}
            footer={
                <Link href="/login" className="text-amber hover:underline">
                    Back to sign in
                </Link>
            }
        >
            <p className="mb-4 text-sm text-muted-foreground">
                Enter your email and we’ll send you a link to choose a new
                password.
            </p>

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

                <Button type="submit" className="w-full" disabled={processing}>
                    {processing ? "Sending…" : "Email password reset link"}
                </Button>
            </form>
        </AuthShell>
    );
}
