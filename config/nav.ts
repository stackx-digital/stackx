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
 * render "coming soon" until their milestones. `pillar` is the spec label.
 */
export interface NavItem {
  pillar: string;
  label: string;
  href: string;
  icon: LucideIcon;
  status: "live" | "soon";
}

export const NAV_ITEMS: NavItem[] = [
  {
    pillar: "P1",
    label: "Creative Analytics",
    href: "/analytics",
    icon: BarChart3,
    status: "live",
  },
  {
    pillar: "P2",
    label: "Brand Spy",
    href: "/spy",
    icon: Eye,
    status: "soon",
  },
  {
    pillar: "P3",
    label: "Ad Discovery",
    href: "/discovery",
    icon: Search,
    status: "soon",
  },
  {
    pillar: "P4",
    label: "Ad Creation",
    href: "/create",
    icon: Sparkles,
    status: "soon",
  },
  {
    pillar: "P5",
    label: "Reports",
    href: "/reports",
    icon: FileText,
    status: "soon",
  },
];
