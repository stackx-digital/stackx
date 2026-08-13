import { redirect } from "next/navigation";

import { Sidebar } from "@/components/shell/sidebar";
import { Topbar } from "@/components/shell/topbar";
import { isEmailAllowed } from "@/lib/auth/allowlist";
import { createClient } from "@/lib/supabase/server";

/**
 * Authenticated app shell. Middleware already gates access; we re-check here
 * (defense in depth) and surface the session email in the topbar.
 */
export default async function AppLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const supabase = createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) redirect("/login");
  if (!isEmailAllowed(user.email)) redirect("/not-authorized");

  return (
    <div className="flex min-h-screen">
      <Sidebar />
      <div className="flex min-w-0 flex-1 flex-col">
        <Topbar email={user.email ?? null} />
        <main className="flex-1 overflow-y-auto p-5 md:p-8">{children}</main>
      </div>
    </div>
  );
}
