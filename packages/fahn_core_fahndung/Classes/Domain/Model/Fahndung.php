<?php

declare(strict_types=1);

namespace Fahn\CoreFahndung\Domain\Model;

use TYPO3\CMS\Extbase\Attribute\Validate;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Fahndung Domain Model
 *
 * Repräsentiert einen Fahndungsfall im System.
 * Erzwingt strikte Validierungsregeln mittels nativer PHP 8 Attribute
 * zur Sicherstellung der Datenintegrität vor der Persistierung.
 */
class Fahndung extends AbstractEntity
{
    /**
     * Der Titel des Fahndungsfalls.
     * Muss zwingend vorhanden sein.
     */
    #[Validate(['validator' => 'NotEmpty'])]
    protected string $title = '';

    /**
     * Ausführliche Beschreibung des Falls.
     * Darf nicht leer sein, da essenziell für die Detailansicht.
     */
    #[Validate(['validator' => 'NotEmpty'])]
    protected string $description = '';

    /**
     * Aktenzeichen oder interne Fallnummer.
     * Optional, aber typisiert als String.
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
     * Datum und Uhrzeit der Tat.
     * Validiert auf korrekten DateTime-Typ.
     */
    #[Validate(['validator' => 'DateTime'])]
    protected ?\DateTime $dateOfCrime = null;

    /**
     * Ort des Geschehens.
     */
    protected string $location = '';

    /**
     * Status der Veröffentlichung.
     * True = Öffentlich sichtbar (Active), False = Entwurf/Archiviert.
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

    public function getIsPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): void
    {
        $this->isPublished = $isPublished;
    }
}









