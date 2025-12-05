<?php

declare(strict_types=1);

namespace Fahn\CoreFahndung\Domain\Model;

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Fahndung Domain Model
 */
class Fahndung extends AbstractEntity
{
    /**
     * Titel der Fahndung
     *
     * @var string
     * @Extbase\Validate("NotEmpty")
     */
    protected string $title = '';

    /**
     * Beschreibung (längerer Text)
     *
     * @var string
     * @Extbase\Validate("NotEmpty")
     */
    protected string $description = '';

    /**
     * Eindeutige Fallnummer
     *
     * @var string
     * @Extbase\Validate("NotEmpty")
     */
    protected string $caseId = '';

    /**
     * Kategorien (sys_category Relation)
     *
     * @var ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category>
     */
    protected ObjectStorage $categories;

    /**
     * Bilder (FAL FileReferences)
     *
     * @var ObjectStorage<FileReference>
     */
    protected ObjectStorage $images;

    /**
     * Tatdatum/-zeitpunkt
     *
     * @var ?\DateTime
     */
    protected ?\DateTime $dateOfCrime = null;

    /**
     * Tatort
     *
     * @var string
     */
    protected string $location = '';

    /**
     * Veröffentlicht (steuert API-Sichtbarkeit)
     *
     * @var bool
     */
    protected bool $isPublished = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->categories = new ObjectStorage();
        $this->images = new ObjectStorage();
    }

    // --- Getter & Setter ---

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getCaseId(): string
    {
        return $this->caseId;
    }

    public function setCaseId(string $caseId): void
    {
        $this->caseId = $caseId;
    }

    /**
     * @return ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category>
     */
    public function getCategories(): ObjectStorage
    {
        return $this->categories;
    }

    /**
     * @param ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category> $categories
     */
    public function setCategories(ObjectStorage $categories): void
    {
        $this->categories = $categories;
    }

    public function addCategory(\TYPO3\CMS\Extbase\Domain\Model\Category $category): void
    {
        $this->categories->attach($category);
    }

    public function removeCategory(\TYPO3\CMS\Extbase\Domain\Model\Category $category): void
    {
        $this->categories->detach($category);
    }

    /**
     * @return ObjectStorage<FileReference>
     */
    public function getImages(): ObjectStorage
    {
        return $this->images;
    }

    /**
     * @param ObjectStorage<FileReference> $images
     */
    public function setImages(ObjectStorage $images): void
    {
        $this->images = $images;
    }

    public function addImage(FileReference $image): void
    {
        $this->images->attach($image);
    }

    public function removeImage(FileReference $image): void
    {
        $this->images->detach($image);
    }

    public function getDateOfCrime(): ?\DateTime
    {
        return $this->dateOfCrime;
    }

    public function setDateOfCrime(?\DateTime $dateOfCrime): void
    {
        $this->dateOfCrime = $dateOfCrime;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): void
    {
        $this->isPublished = $isPublished;
    }
}








