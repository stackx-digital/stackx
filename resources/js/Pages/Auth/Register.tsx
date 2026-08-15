import { Link, useForm } from "@inertiajs/react";
import { type FormEventHandler } from "react";

import { AuthShell, Field, inputClass } from "@/Components/auth/AuthShell";
import { Button } from "@/Components/ui/Button";

/** Public self-serve signup. Creates a fresh organization + first user. */
export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: "",
        company: "",
        email: "",
        password: "",
        password_confirmation: "",
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post("/register", {
            onFinish: () => reset("password", "password_confirmation"),
        });
    };

    return (
        <AuthShell
            title="Create account"
            heading="Create your workspace"
            subheading="Ad Intelligence for your team"
            footer={
                <>
                    Already have an account?{" "}
                    <Link href="/login" className="text-amber hover:underline">
                        Sign in
                    </Link>
                </>
            }
        >
            <form onSubmit={submit}>
                <Field id="name" label="Your name" error={errors.name}>
                    <input
                        id="name"
                        type="text"
                        required
                        autoFocus
                        autoComplete="name"
                        value={data.name}
                        onChange={(e) => setData("name", e.target.value)}
                        placeholder="Aisyah Rahman"
                        className={inputClass}
                    />
                </Field>

                <Field
                    id="company"
                    label="Company (optional)"
                    error={errors.company}
                >
                    <input
                        id="company"
                        type="text"
                        autoComplete="organization"
                        value={data.company}
                        onChange={(e) => setData("company", e.target.value)}
                        placeholder="Your agency or brand"
                        className={inputClass}
                    />
                </Field>

                <Field id="email" label="Email" error={errors.email}>
                    <input
                        id="email"
                        type="email"
                        required
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
                        placeholder="••••••••"
                        className={inputClass}
                    />
                </Field>

                <Button
                    type="submit"
                    className="mt-2 w-full"
                    disabled={processing}
                >
                    {processing ? "Creating…" : "Create account"}
                </Button>
            </form>
        </AuthShell>
    );
}
