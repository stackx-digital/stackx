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

/**
 * Full-bleed creative cover for a grid card — fills the card's top edge at a
 * fixed aspect ratio, same graceful fallback as CreativeThumb.
 */
export function CreativeCover({ src, alt }: { src: string | null; alt: string }) {
    const [failed, setFailed] = useState(false);

    if (!src || failed) {
        return (
            <div
                className="flex aspect-square w-full items-center justify-center bg-ink/60 text-muted-foreground/40"
                aria-hidden
            >
                <ImageOff className="size-8" />
            </div>
        );
    }

    return (
        <img
            src={src}
            alt={alt}
            onError={() => setFailed(true)}
            className="aspect-square w-full object-cover"
        />
    );
}
