import { Link, useForm } from "@inertiajs/react";
import { type FormEventHandler } from "react";

import { AuthShell } from "@/Components/auth/AuthShell";
import { Button } from "@/Components/ui/Button";

/** Shown after signup until the email is verified. */
export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post("/email/verification-notification");
    };

    return (
        <AuthShell title="Verify email" heading="Verify your email">
            <p className="mb-4 text-sm text-muted-foreground">
                Thanks for signing up. We’ve sent a verification link to your
                email — click it to activate your workspace. Didn’t get it? Send
                a new one below.
            </p>

            {status === "verification-link-sent" && (
                <div className="mb-4 rounded-md border border-winner/30 bg-winner/10 px-3 py-2 text-xs text-winner">
                    A fresh verification link has been sent to your email.
                </div>
            )}

            <form onSubmit={submit} className="flex items-center justify-between">
                <Button type="submit" disabled={processing}>
                    {processing ? "Sending…" : "Resend verification email"}
                </Button>
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    className="text-xs text-muted-foreground hover:text-slate-200"
                >
                    Sign out
                </Link>
            </form>
        </AuthShell>
    );
}
