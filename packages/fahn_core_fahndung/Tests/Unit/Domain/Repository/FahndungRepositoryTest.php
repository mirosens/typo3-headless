<?php

declare(strict_types=1);

namespace Fahn\CoreFahndung\Tests\Unit\Domain\Repository;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Fahn\CoreFahndung\Domain\Repository\FahndungRepository;

/**
 * Unit Test für FahndungRepository
 * 
 * Hinweis: Repository-Tests sind in TYPO3 oft schwierig zu isolieren,
 * da sie stark mit Extbase Persistence gekoppelt sind. Für echte
 * Validierung sollten Functional Tests verwendet werden.
 */
final class FahndungRepositoryTest extends TestCase
{
    #[Test]
    public function repositoryClassExists(): void
    {
        self::assertTrue(
            class_exists(FahndungRepository::class),
            'FahndungRepository class should exist'
        );
    }

    #[Test]
    public function repositoryExtendsExtbaseRepository(): void
    {
        $reflection = new \ReflectionClass(FahndungRepository::class);
        self::assertTrue(
            $reflection->isSubclassOf(\TYPO3\CMS\Extbase\Persistence\Repository::class),
            'FahndungRepository should extend Extbase Repository'
        );
    }
}










