export interface CompetitorAd {
    id: number;
    body: string | null;
    snapshotUrl: string | null;
    mediaUrl: string | null;
    platforms: string[] | null;
    daysRunning: number;
    isActive: boolean;
    firstSeen: string | null;
    lastSeen: string | null;
}

export interface Competitor {
    id: number;
    name: string;
    metaPageId: string | null;
    lastSyncedAt: string | null;
    adCount: number;
    activeCount: number;
    ads: CompetitorAd[];
}
