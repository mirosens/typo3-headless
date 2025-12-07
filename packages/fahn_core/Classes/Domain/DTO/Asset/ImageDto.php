<?php

declare(strict_types=1);

namespace Vendor\FahnCore\Domain\DTO\Asset;

use JsonSerializable;
use TYPO3\CMS\Core\Resource\FileReference;

/**
 * A.7.3.2: ImageDto für API-Responses mit Accessibility-Informationen
 * 
 * Serialisiert FileReference-Objekte in ein strukturiertes JSON-Format
 * mit explizitem accessibility-Objekt für WCAG 2.2-Konformität.
 */
class ImageDto implements JsonSerializable
{
    protected string $src = '';
    protected int $width = 0;
    protected int $height = 0;
    protected string $mimeType = '';

    protected string $alt = '';
    protected string $title = '';
    protected bool $isDecorative = false;

    /** @var array<string, array<string, mixed>> */
    protected array $sources = [];

    public function __construct(FileReference $fileReference)
    {
        $this->src = (string)($fileReference->getPublicUrl() ?? '');
        $this->width = (int)$fileReference->getProperty('width');
        $this->height = (int)$fileReference->getProperty('height');
        $this->mimeType = (string)$fileReference->getMimeType();

        // A.7.1.3: Dekorative Bilder erkennen
        $this->isDecorative = (bool)($fileReference->getProperty('tx_is_decorative') ?? false);

        if ($this->isDecorative) {
            // WCAG 2.2: Dekorative Bilder erhalten leeren Alt-Text
            $this->alt = '';
            $this->title = '';
        } else {
            // A.7.1.1: Alt-Text ist Pflichtfeld (Backend-validiert)
            $this->alt = (string)($fileReference->getProperty('alternative') ?? '');
            $this->title = (string)($fileReference->getProperty('title') ?? '');
        }

        $this->processCropVariants($fileReference);
    }

    protected function processCropVariants(FileReference $fileReference): void
    {
        // TODO A.7.3: CropVariant-Handling für responsive Images ergänzen
        // Beispiel-Struktur für zukünftige Implementierung:
        // $cropVariants = $fileReference->getProperty('crop');
        // if ($cropVariants) {
        //     foreach ($cropVariants as $variant => $config) {
        //         $this->sources[$variant] = [
        //             'src' => $this->src,
        //             'width' => $config['width'] ?? $this->width,
        //             'height' => $config['height'] ?? $this->height,
        //         ];
        //     }
        // }
    }

    public function jsonSerialize(): array
    {
        return [
            'src' => $this->src,
            'meta' => [
                'width' => $this->width,
                'height' => $this->height,
                'mimeType' => $this->mimeType,
            ],
            'accessibility' => [
                'alt' => $this->alt,
                'title' => $this->title,
                'isDecorative' => $this->isDecorative,
                'ariaLabel' => $this->isDecorative ? '' : $this->alt,
                'ariaDescription' => $this->isDecorative ? '' : $this->title,
            ],
            'sources' => $this->sources,
        ];
    }

    public function getSrc(): string
    {
        return $this->src;
    }

    public function getAlt(): string
    {
        return $this->alt;
    }

    public function isDecorative(): bool
    {
        return $this->isDecorative;
    }
}









