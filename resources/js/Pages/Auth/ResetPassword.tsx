import { useForm } from "@inertiajs/react";
import { type FormEventHandler } from "react";

import { AuthShell, Field, inputClass } from "@/Components/auth/AuthShell";
import { Button } from "@/Components/ui/Button";

/** Consumes a reset token and sets a new password. */
export default function ResetPassword({
    token,
    email,
}: {
    token: string;
    email: string;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token,
        email,
        password: "",
        password_confirmation: "",
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post("/reset-password", {
            onFinish: () => reset("password", "password_confirmation"),
        });
    };

    return (
        <AuthShell title="Reset password" heading="Choose a new password">
            <form onSubmit={submit}>
                <Field id="email" label="Email" error={errors.email}>
                    <input
                        id="email"
                        type="email"
                        required
                        autoComplete="username"
                        value={data.email}
                        onChange={(e) => setData("email", e.target.value)}
                        className={inputClass}
                    />
                </Field>

                <Field id="password" label="New password" error={errors.password}>
                    <input
                        id="password"
                        type="password"
                        required
                        autoFocus
                        autoComplete="new-password"
                        value={data.password}
                        onChange={(e) => setData("password", e.target.value)}
                        placeholder="At least 8 characters"
                        className={inputClass}
                    />
                </Field>

                <Field
                    id="password_confirmation"
                    label="Confirm password"
                    error={errors.password_confirmation}
                >
                    <input
                        id="password_confirmation"
                        type="password"
                        required
                        autoComplete="new-password"
                        value={data.password_confirmation}
                        onChange={(e) =>
                            setData("password_confirmation", e.target.value)
                        }
                        className={inputClass}
                    />
                </Field>

                <Button type="submit" className="w-full" disabled={processing}>
                    {processing ? "Saving…" : "Reset password"}
                </Button>
            </form>
        </AuthShell>
    );
}
