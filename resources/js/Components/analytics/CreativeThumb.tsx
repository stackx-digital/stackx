import { ImageOff } from "lucide-react";
import { useState } from "react";

/**
 * Small creative thumbnail with a graceful fallback — the ad's own image when
 * we have one (Meta creative pull or vision-tag upload), otherwise a neutral
 * placeholder. Never blocks the row on a broken/expired Meta CDN link.
 */
export function CreativeThumb({
    src,
    alt,
    size = "sm",
}: {
    src: string | null;
    alt: string;
    size?: "sm" | "lg";
}) {
    const [failed, setFailed] = useState(false);
    const dims = size === "lg" ? "size-16" : "size-9";

    if (!src || failed) {
        return (
            <div
                className={`flex ${dims} shrink-0 items-center justify-center rounded-md border border-hairline bg-ink/60 text-muted-foreground/50`}
                aria-hidden
            >
                <ImageOff className={size === "lg" ? "size-6" : "size-4"} />
            </div>
        );
    }

    return (
        <img
            src={src}
            alt={alt}
            onError={() => setFailed(true)}
            className={`${dims} shrink-0 rounded-md border border-hairline object-cover`}
        />
    );
}
