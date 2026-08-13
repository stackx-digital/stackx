import { Sparkles } from "lucide-react";

import { ComingSoon } from "@/components/shell/coming-soon";

export default function CreatePage() {
  return (
    <ComingSoon
      pillar="P4"
      title="Ad Creation"
      description="Take a winning ad — ours or a competitor's — and let Claude generate copy, angle and hook variations. Built after P1 ships."
      icon={Sparkles}
    />
  );
}
