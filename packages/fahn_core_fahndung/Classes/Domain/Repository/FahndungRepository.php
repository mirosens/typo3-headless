<?php

declare(strict_types=1);

namespace Fahn\CoreFahndung\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Fahndung Repository
 */
class FahndungRepository extends Repository
{
    /**
     * Findet nur veröffentlichte, aktive Fahndungen
     * (für öffentliches Frontend / API)
     *
     * Beachtet automatisch:
     * - hidden = 0
     * - deleted = 0
     * - starttime <= now
     * - endtime >= now OR endtime = 0
     */
    public function findActive(): QueryResultInterface
    {
        $query = $this->createQuery();
        
        // Storage Page wird NICHT eingeschränkt (global suchen)
        $query->getQuerySettings()->setRespectStoragePage(false);
        
        // Nur veröffentlichte Datensätze
        $query->matching(
            $query->equals('isPublished', true)
        );
        
        // Sortierung: Neueste zuerst
        $query->setOrderings([
            'crdate' => QueryInterface::ORDER_DESCENDING,
        ]);
        
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
     * Findet Fahndungen nach Kategorie-UID
     *
     * @param int $categoryUid
     * @return QueryResultInterface
     */
    public function findByCategory(int $categoryUid): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        
        $query->matching(
            $query->logicalAnd(
                $query->equals('isPublished', true),
                $query->contains('categories', $categoryUid)
            )
        );
        
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
}






