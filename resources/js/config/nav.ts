import {
    BarChart3,
    Eye,
    Search,
    Sparkles,
    FileText,
    type LucideIcon,
} from "lucide-react";

/**
 * Single source of truth for the 5 pillars (§2). P1 is live from M4; P2–P5
 * render "coming soon" until their milestones. `routeName` maps to a named
 * Laravel route.
 */
export interface NavItem {
    pillar: string;
    label: string;
    routeName: string;
    href: string;
    icon: LucideIcon;
    status: "live" | "soon";
}

export const NAV_ITEMS: NavItem[] = [
    {
        pillar: "P1",
        label: "Creative Analytics",
        routeName: "analytics",
        href: "/analytics",
        icon: BarChart3,
        status: "live",
    },
    {
        pillar: "P2",
        label: "Brand Spy",
        routeName: "spy",
        href: "/spy",
        icon: Eye,
        status: "soon",
    },
    {
        pillar: "P3",
        label: "Ad Discovery",
        routeName: "discovery",
        href: "/discovery",
        icon: Search,
        status: "soon",
    },
    {
        pillar: "P4",
        label: "Ad Creation",
        routeName: "create",
        href: "/create",
        icon: Sparkles,
        status: "soon",
    },
    {
        pillar: "P5",
        label: "Reports",
        routeName: "reports",
        href: "/reports",
        icon: FileText,
        status: "soon",
    },
];
