import {
    BarChart3,
    Eye,
    Search,
    Sparkles,
    FileText,
    Library,
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
        status: "live",
    },
    {
        pillar: "P3",
        label: "Ad Discovery",
        routeName: "discovery",
        href: "/discovery",
        icon: Search,
        status: "live",
    },
    {
        pillar: "P4",
        label: "Ad Creation",
        routeName: "create",
        href: "/create",
        icon: Sparkles,
        status: "live",
    },
    {
        pillar: "P5",
        label: "Reports",
        routeName: "reports",
        href: "/reports",
        icon: FileText,
        status: "live",
    },
    {
        pillar: "LIB",
        label: "Creative Library",
        routeName: "library",
        href: "/library",
        icon: Library,
        status: "live",
    },
];
