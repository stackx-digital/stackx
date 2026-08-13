import { Head } from "@inertiajs/react";
import { FileText } from "lucide-react";

import { ComingSoon } from "@/Components/shell/ComingSoon";
import AppLayout from "@/Layouts/AppLayout";

export default function ReportsIndex() {
    return (
        <AppLayout>
            <Head title="Reports" />
            <ComingSoon
                pillar="P5"
                title="Reports"
                description="Shareable internal report views and a scheduled Slack summary. Built after P1 ships."
                icon={FileText}
            />
        </AppLayout>
    );
}
