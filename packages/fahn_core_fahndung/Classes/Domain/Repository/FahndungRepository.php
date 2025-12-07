<?php

declare(strict_types=1);

namespace Fahn\CoreFahndung\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Das Repository für Fahndungen.
 *
 * Verantwortlich für den Zugriff auf die Fahndungs-Entitäten in der Datenbank.
 * Implementiert spezifische Such- und Filterlogik für die API.
 */
class FahndungRepository extends Repository
{
    /**
     * Standard-Sortierung für alle Abfragen, sofern nicht überschrieben.
     * Sortiert standardmäßig nach Tatdatum absteigend (neueste zuerst).
     *
     * @var array<string, string>
     */
    protected $defaultOrderings = [
        'dateOfCrime' => QueryInterface::ORDER_DESCENDING,
        'crdate' => QueryInterface::ORDER_DESCENDING,
    ];

    /**
     * Findet alle veröffentlichten Fahndungen mit Paginierung.
     *
     * Diese Methode ist der primäre Einstiegspunkt für die Listenansicht der API.
     * Sie wendet den 'isPublished'-Filter an und limitiert das Ergebnis für die Paginierung.
     *
     * Beachtet automatisch:
     * - hidden = 0
     * - deleted = 0
     * - starttime <= now
     * - endtime >= now OR endtime = 0
     *
     * @param int $limit Die Anzahl der Datensätze pro Seite (SQL LIMIT).
     * @param int $offset Der Versatz (SQL OFFSET).
     * @return QueryResultInterface Das Abfrageergebnis (Lazy Loading Proxy).
     */
    public function findActive(int $limit = 10, int $offset = 0): QueryResultInterface
    {
        $query = $this->createQuery();
        
        // Storage Page wird NICHT eingeschränkt (global suchen)
        $query->getQuerySettings()->setRespectStoragePage(false);
        
        // Explizite Einschränkung auf veröffentlichte Datensätze
        // (Zusätzlich zu den automatischen enableFields wie hidden/deleted)
        $query->matching(
            $query->equals('isPublished', true)
        );
        
        // Sortierung: Neueste zuerst (deterministisch für Paginierung)
        $query->setOrderings([
            'dateOfCrime' => QueryInterface::ORDER_DESCENDING,
            'crdate' => QueryInterface::ORDER_DESCENDING,
        ]);

        // Anwendung der Paginierungsparameter direkt auf dem QueryBuilder
        if ($limit > 0) {
            $query->setLimit($limit);
        }
        if ($offset >= 0) {
            $query->setOffset($offset);
        }
        
        return $query->execute();
    }

    /**
     * Findet ALLE Fahndungen (inkl. hidden/deleted)
     * NUR für Backend-Admin-Module verwenden!
     *
     * @return QueryResultInterface
     */
    public function findForAdminFilters(): QueryResultInterface
    {
        $query = $this->createQuery();
        $querySettings = $query->getQuerySettings();
        
        // KRITISCH: Enable Fields komplett ignorieren
        $querySettings->setIgnoreEnableFields(true);
        
        // Optional: Auch gelöschte Datensätze anzeigen
        $querySettings->setIncludeDeleted(true);
        
        // Global suchen
        $querySettings->setRespectStoragePage(false);
        
        // Sortierung: Nach Erstelldatum
        $query->setOrderings([
            'crdate' => QueryInterface::ORDER_DESCENDING,
        ]);
        
        return $query->execute();
    }

    /**
     * Findet Fahndungen nach Kategorie-UID mit Paginierung.
     *
     * @param int $categoryUid Die UID der Kategorie.
     * @param int $limit Die Anzahl der Datensätze pro Seite (SQL LIMIT).
     * @param int $offset Der Versatz (SQL OFFSET).
     * @return QueryResultInterface
     */
    public function findByCategory(int $categoryUid, int $limit = 10, int $offset = 0): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        
        $query->matching(
            $query->logicalAnd(
                $query->equals('isPublished', true),
                $query->contains('categories', $categoryUid)
            )
        );
        
        // Sortierung: Neueste zuerst
        $query->setOrderings([
            'dateOfCrime' => QueryInterface::ORDER_DESCENDING,
            'crdate' => QueryInterface::ORDER_DESCENDING,
        ]);

        // Paginierung anwenden
        if ($limit > 0) {
            $query->setLimit($limit);
        }
        if ($offset >= 0) {
            $query->setOffset($offset);
        }
        
        return $query->execute();
    }

    /**
     * Findet Fahndungen innerhalb eines Datumsbereichs
     *
     * @param \DateTime|null $from
     * @param \DateTime|null $to
     * @return QueryResultInterface
     */
    public function findByDateRange(?\DateTime $from = null, ?\DateTime $to = null): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        
        $constraints = [
            $query->equals('isPublished', true),
        ];
        
        if ($from !== null) {
            $constraints[] = $query->greaterThanOrEqual('dateOfCrime', $from);
        }
        
        if ($to !== null) {
            $constraints[] = $query->lessThanOrEqual('dateOfCrime', $to);
        }
        
        $query->matching(
            $query->logicalAnd(...$constraints)
        );
        
        $query->setOrderings([
            'dateOfCrime' => QueryInterface::ORDER_DESCENDING,
        ]);
        
        return $query->execute();
    }

    /**
     * Sucht nach Fahndungen anhand eines Suchbegriffs in Titel, Beschreibung und Fall-ID.
     * Berücksichtigt dabei nur veröffentlichte Datensätze.
     *
     * Die Suchlogik kombiniert eine ODER-Verknüpfung der Suchfelder mit einer
     * UND-Verknüpfung des Veröffentlichungsstatus:
     * (title LIKE %term% OR description LIKE %term% OR caseId LIKE %term%) AND isPublished = true
     *
     * @param string $searchTerm Der Suchbegriff.
     * @param int $limit Paginierungslimit.
     * @param int $offset Paginierungsoffset.
     * @return QueryResultInterface
     */
    public function findBySearchTerm(string $searchTerm, int $limit = 10, int $offset = 0): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        // 1. Aufbau der Such-Constraints (ODER-Gruppe)
        // Nutzung von 'like' für Teilstringsuche.
        // HINWEIS: Wildcards (%) müssen manuell hinzugefügt werden.
        $searchConstraints = [
            $query->like('title', '%' . $searchTerm . '%'),
            $query->like('description', '%' . $searchTerm . '%'),
            $query->like('caseId', '%' . $searchTerm . '%'),
        ];

        // 2. Aufbau des Status-Constraints (UND-Gruppe)
        // Hier kombinieren wir die Suchbedingungen mit dem Veröffentlichungsstatus.
        // WICHTIG: Nutzung des Spread-Operators (...$searchConstraints) für die 
        // Kompatibilität mit der variadischen Signatur von logicalOr() in TYPO3 v13.
        $finalConstraint = $query->logicalAnd(
            $query->equals('isPublished', true),
            $query->logicalOr(...$searchConstraints)
        );

        $query->matching($finalConstraint);

        // 3. Paginierung anwenden
        if ($limit > 0) {
            $query->setLimit($limit);
        }
        if ($offset >= 0) {
            $query->setOffset($offset);
        }
        
        // Standard-Sortierung explizit setzen (Sicherheitshalber, falls $defaultOrderings überschrieben würde)
        $query->setOrderings([
            'dateOfCrime' => QueryInterface::ORDER_DESCENDING,
            'crdate' => QueryInterface::ORDER_DESCENDING,
        ]);

        return $query->execute();
    }

    /**
     * Zählt alle veröffentlichten Fahndungen.
     *
     * Wird benötigt, um im Frontend die Gesamtanzahl der Seiten zu berechnen.
     * Führt eine optimierte COUNT(*)-Abfrage aus, ohne Objekte zu hydrieren.
     *
     * @return int Die Anzahl der Datensätze.
     */
    public function countAll(): int
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        
        // Gleicher Filter wie bei findActive, aber ohne Limit/Offset
        $query->matching($query->equals('isPublished', true));
        
        // execute()->count() triggert die interne Zähl-Optimierung von Extbase
        return $query->execute()->count();
    }
}









