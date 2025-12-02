<?php

declare(strict_types=1);

namespace Vendor\FahnCore\Domain\DTO;

use JsonSerializable;
use Vendor\FahnCore\Domain\DTO\Asset\ImageDto;

/**
 * A.7.3: CaseDto für Fahndungsdaten mit Accessibility-Informationen
 * 
 * Beispiel-DTO für strukturierte API-Ausgabe mit integrierten
 * Accessibility-Metadaten für alle enthaltenen Bilder.
 */
class CaseDto implements JsonSerializable
{
    protected string $title = '';
    protected string $caseId = '';
    protected string $description = '';
    protected ?\DateTimeInterface $dateOfCrime = null;
    protected string $location = '';
    
    /** @var ImageDto[] */
    protected array $images = [];

    public function __construct(
        string $title,
        string $caseId,
        string $description,
        ?\DateTimeInterface $dateOfCrime = null,
        string $location = ''
    ) {
        $this->title = $title;
        $this->caseId = $caseId;
        $this->description = $description;
        $this->dateOfCrime = $dateOfCrime;
        $this->location = $location;
    }

    /**
     * @param ImageDto[] $images
     */
    public function setImages(array $images): void
    {
        $this->images = $images;
    }

    public function jsonSerialize(): array
    {
        return [
            'title' => $this->title,
            'caseId' => $this->caseId,
            'description' => $this->description,
            'dateOfCrime' => $this->dateOfCrime?->format('c'),
            'location' => $this->location,
            'images' => array_map(
                fn(ImageDto $image) => $image->jsonSerialize(),
                $this->images
            ),
            'accessibility' => [
                'hasImages' => count($this->images) > 0,
                'imagesWithAltText' => count(array_filter(
                    $this->images,
                    fn(ImageDto $img) => !$img->isDecorative() && $img->getAlt() !== ''
                )),
            ],
        ];
    }
}






