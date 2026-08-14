export interface Winner {
    id: number;
    name: string;
    action: string | null;
}

export interface CompetitorAdOption {
    id: number;
    label: string;
}

export interface Variation {
    hook: string | null;
    primary_text: string | null;
    headline: string | null;
    angle: string | null;
    cta: string | null;
}

export interface HistoryItem {
    id: number;
    product: string;
    source: string | null;
    output: Variation[];
    generatedBy: string | null;
    createdBy: string | null;
    createdAt: string | null;
}
