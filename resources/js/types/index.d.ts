export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    organization_id?: number;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    onboarding: {
        completed: boolean;
    };
    flash: {
        status?: string | null;
        apiToken?: string | null;
    };
};
