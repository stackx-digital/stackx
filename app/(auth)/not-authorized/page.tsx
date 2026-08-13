import { signOut } from "@/app/actions/auth";
import { Button } from "@/components/ui/button";

/**
 * Landing for an authenticated user whose email is not on the STACKx
 * allowlist. They have a valid Supabase session but no app access.
 */
export default function NotAuthorizedPage() {
  return (
    <main className="flex min-h-screen items-center justify-center bg-ink px-6">
      <div className="w-full max-w-sm text-center">
        <h1 className="font-display text-xl font-bold">Not authorised</h1>
        <p className="mt-2 text-sm text-muted-foreground">
          Your account isn&apos;t on the STACKx team allowlist. Ask an admin to
          add your email, then sign in again.
        </p>
        <form action={signOut} className="mt-6">
          <Button type="submit" variant="outline" className="w-full">
            Sign out
          </Button>
        </form>
      </div>
    </main>
  );
}
