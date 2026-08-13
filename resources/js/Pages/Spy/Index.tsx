import { Head } from "@inertiajs/react";
import { Eye } from "lucide-react";

import { ComingSoon } from "@/Components/shell/ComingSoon";
import AppLayout from "@/Layouts/AppLayout";

export default function SpyIndex() {
    return (
        <AppLayout>
            <Head title="Brand Spy" />
            <ComingSoon
                pillar="P2"
                title="Brand Spy"
                description="Track competitors via the Meta Ad Library — active ads, longest-running creatives, angles and hooks. Built after P1 ships."
                icon={Eye}
            />
        </AppLayout>
    );
}
