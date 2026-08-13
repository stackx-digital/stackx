import { Head } from "@inertiajs/react";
import { Sparkles } from "lucide-react";

import { ComingSoon } from "@/Components/shell/ComingSoon";
import AppLayout from "@/Layouts/AppLayout";

export default function CreateIndex() {
    return (
        <AppLayout>
            <Head title="Ad Creation" />
            <ComingSoon
                pillar="P4"
                title="Ad Creation"
                description="Take a winning ad — ours or a competitor's — and let the AI layer generate copy, angle and hook variations. Built after P1 ships."
                icon={Sparkles}
            />
        </AppLayout>
    );
}
