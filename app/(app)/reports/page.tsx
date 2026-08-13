import { FileText } from "lucide-react";

import { ComingSoon } from "@/components/shell/coming-soon";

export default function ReportsPage() {
  return (
    <ComingSoon
      pillar="P5"
      title="Reports"
      description="Shareable internal report views and a scheduled Slack summary. Built after P1 ships."
      icon={FileText}
    />
  );
}
