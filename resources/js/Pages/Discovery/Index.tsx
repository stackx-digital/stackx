import { Head } from "@inertiajs/react";
import { Search } from "lucide-react";

import { ComingSoon } from "@/Components/shell/ComingSoon";
import AppLayout from "@/Layouts/AppLayout";

export default function DiscoveryIndex() {
    return (
        <AppLayout>
            <Head title="Ad Discovery" />
            <ComingSoon
                pillar="P3"
                title="Ad Discovery"
                description="Semantic search across saved and scraped ads using pgvector embeddings. Built after P1 ships."
                icon={Search}
            />
        </AppLayout>
    );
}
