import { Eye } from "lucide-react";

import { ComingSoon } from "@/components/shell/coming-soon";

export default function SpyPage() {
  return (
    <ComingSoon
      pillar="P2"
      title="Brand Spy"
      description="Track competitors via the Meta Ad Library — active ads, longest-running creatives, angles and hooks. Built after P1 ships."
      icon={Eye}
    />
  );
}
