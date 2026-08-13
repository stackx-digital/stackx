/**
 * STACKx team email allowlist (§7 M1, §5). Internal tool — no public signup.
 * Layer 1 of two: app-layer gate enforced here + in middleware. Layer 2 (RLS
 * on org_members) is added with migrations in M2.
 *
 * Source: STACKX_ALLOWED_EMAILS — comma-separated. Entries may be:
 *   - a full email  (alice@stackx.my)
 *   - a domain glob (@stackx.my) to allow anyone at that domain
 * Matching is case-insensitive and whitespace-tolerant.
 */

function parseAllowlist(): string[] {
  return (process.env.STACKX_ALLOWED_EMAILS ?? "")
    .split(",")
    .map((e) => e.trim().toLowerCase())
    .filter(Boolean);
}

export function isEmailAllowed(email: string | null | undefined): boolean {
  if (!email) return false;
  const normalized = email.trim().toLowerCase();
  const domain = normalized.slice(normalized.indexOf("@"));
  const allowlist = parseAllowlist();

  // Fail closed: an empty/misconfigured allowlist admits no one.
  if (allowlist.length === 0) return false;

  return allowlist.some((entry) =>
    entry.startsWith("@") ? entry === domain : entry === normalized,
  );
}
