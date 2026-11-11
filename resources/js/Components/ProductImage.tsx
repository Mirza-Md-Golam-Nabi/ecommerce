interface ProductImageProps {
    src: string | null;
    alt: string;
    placeholderIcon?: string | null;
    className?: string;
}

/**
 * Product/category image with a soft themed placeholder (category emoji) when no photo is uploaded.
 */
export default function ProductImage({ src, alt, placeholderIcon, className = '' }: ProductImageProps) {
    if (src) {
        return <img src={src} alt={alt} loading="lazy" className={`size-full object-contain ${className}`} />;
    }

    return (
        <div
            role="img"
            aria-label={alt}
            className={`grid size-full place-items-center rounded-xl bg-gradient-to-br from-primary/5 via-accent/10 to-primary/10 ${className}`}
        >
            <span className="text-5xl drop-shadow-sm sm:text-6xl">{placeholderIcon ?? '🛒'}</span>
        </div>
    );
}
