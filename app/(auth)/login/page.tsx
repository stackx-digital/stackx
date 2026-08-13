"use client";

import { useState } from "react";

import { Button } from "@/components/ui/button";
import { createClient } from "@/lib/supabase/client";

/**
 * Passwordless login (§5). Magic-link / OTP only — no public signup UI. The
 * allowlist gate lives in middleware; this page just initiates the email.
 */
export default function LoginPage() {
  const [email, setEmail] = useState("");
  const [status, setStatus] = useState<"idle" | "sending" | "sent" | "error">(
    "idle",
  );
  const [message, setMessage] = useState("");

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setStatus("sending");
    const supabase = createClient();
    const { error } = await supabase.auth.signInWithOtp({
      email,
      options: {
        emailRedirectTo: `${window.location.origin}/auth/callback`,
      },
    });
    if (error) {
      setStatus("error");
      setMessage(error.message);
    } else {
      setStatus("sent");
      setMessage(`Check ${email} for a sign-in link.`);
    }
  }

  return (
    <main className="flex min-h-screen items-center justify-center bg-ink px-6">
      <div className="w-full max-w-sm">
        <div className="mb-8 text-center">
          <h1 className="font-display text-2xl font-bold tracking-tight">
            STACK<span className="text-amber">x</span>
          </h1>
          <p className="mt-1 text-sm text-muted-foreground">
            Ad Intelligence — internal access
          </p>
        </div>

        <form
          onSubmit={handleSubmit}
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
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            placeholder="you@stackx.my"
            className="mb-4 w-full rounded-md border border-hairline bg-ink px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          />
          <Button
            type="submit"
            className="w-full"
            disabled={status === "sending" || status === "sent"}
          >
            {status === "sending"
              ? "Sending…"
              : status === "sent"
                ? "Link sent"
                : "Send sign-in link"}
          </Button>

          {message && (
            <p
              className={`mt-4 text-sm ${
                status === "error" ? "text-cut" : "text-winner"
              }`}
            >
              {message}
            </p>
          )}
        </form>

        <p className="mt-4 text-center text-xs text-muted-foreground">
          Access is restricted to the STACKx team allowlist.
        </p>
      </div>
    </main>
  );
}
