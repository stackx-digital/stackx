export interface Scores {
    hook: number | null;
    watch: number | null;
    click: number | null;
    convert: number | null;
}

export interface AdRow {
    id: number;
    name: string;
    account: string | null;
    status: string | null;
    spend: number | null;
    impressions: number | null;
    roas: number | null;
    cpr: number | null;
    ctrLink: number | null;
    results: number | null;
    scores: Scores | null;
    action: string | null;
    actionReason: string | null;
}

export interface Summary {
    adCount: number;
    scored: number;
    totalSpend: number;
    blendedRoas: number | null;
    blendedCpa: number | null;
    totalResults: number | null;
}

export interface AdDetail {
    ad: { id: number; name: string; account: string | null; status: string | null };
    aggregate: {
        spend: number;
        impressions: number;
        roas: number | null;
        cpr: number | null;
        ctrLink: number | null;
        results: number | null;
        revenue: number | null;
    };
    scores: Scores | null;
    action: string | null;
    actionReason: string | null;
    daily: Array<{
        date: string;
        spend: number | null;
        impressions: number | null;
        ctr_link: number | null;
        roas: number | null;
        results: number | null;
    }>;
}
