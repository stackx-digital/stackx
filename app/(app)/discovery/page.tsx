import { Search } from "lucide-react";

import { ComingSoon } from "@/components/shell/coming-soon";

export default function DiscoveryPage() {
  return (
    <ComingSoon
      pillar="P3"
      title="Ad Discovery"
      description="Semantic search across saved and scraped ads using pgvector embeddings. Built after P1 ships."
      icon={Search}
    />
  );
}
