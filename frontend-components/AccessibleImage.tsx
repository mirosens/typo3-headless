/**
 * A.7.4.1: AccessibleImage-Komponente für Next.js
 * 
 * Single-Entry-Point für alle Bilder mit automatischer
 * WCAG 2.2-konformer Behandlung von dekorativen Bildern.
 * 
 * Verwendung:
 * ```tsx
 * <AccessibleImage data={imageDto} className="my-image" priority />
 * ```
 */

import Image from 'next/image';
import React from 'react';

interface ImageAccessibility {
  alt: string;
  title: string;
  isDecorative: boolean;
  ariaLabel?: string;
  ariaDescription?: string;
}

interface ImageMeta {
  width: number;
  height: number;
  mimeType?: string;
}

interface ImageDto {
  src: string;
  meta: ImageMeta;
  accessibility: ImageAccessibility;
  sources?: Record<string, unknown>;
}

interface AccessibleImageProps {
  data: ImageDto;
  className?: string;
  priority?: boolean;
  sizes?: string;
  fill?: boolean;
  objectFit?: 'contain' | 'cover' | 'fill' | 'none' | 'scale-down';
}

export const AccessibleImage: React.FC<AccessibleImageProps> = ({
  data,
  className,
  priority = false,
  sizes,
  fill = false,
  objectFit = 'cover',
}) => {
  const { src, meta, accessibility } = data;

  // A.7.1.3: Dekorative Bilder erhalten aria-hidden und role="presentation"
  if (accessibility.isDecorative) {
    return (
      <div className={className} aria-hidden="true">
        <Image
          src={src}
          width={fill ? undefined : meta.width}
          height={fill ? undefined : meta.height}
          alt=""
          role="presentation"
          priority={priority}
          sizes={sizes}
          fill={fill}
          style={fill ? { objectFit } : undefined}
        />
      </div>
    );
  }

  // A.7.1.1: Informative Bilder erhalten Alt-Text (Backend-validiert)
  return (
    <div className={className}>
      <Image
        src={src}
        width={fill ? undefined : meta.width}
        height={fill ? undefined : meta.height}
        alt={accessibility.alt || ''}
        title={accessibility.title || undefined}
        aria-label={accessibility.ariaLabel || accessibility.alt || undefined}
        aria-describedby={accessibility.ariaDescription ? 'image-description' : undefined}
        priority={priority}
        sizes={sizes}
        fill={fill}
        style={fill ? { objectFit } : undefined}
      />
      {accessibility.ariaDescription && (
        <span id="image-description" className="sr-only">
          {accessibility.ariaDescription}
        </span>
      )}
    </div>
  );
};






