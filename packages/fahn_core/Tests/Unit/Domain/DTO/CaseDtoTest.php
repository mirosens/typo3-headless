<?php

declare(strict_types=1);

namespace Vendor\FahnCore\Tests\Unit\Domain\DTO;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Vendor\FahnCore\Domain\DTO\Asset\ImageDto;
use Vendor\FahnCore\Domain\DTO\CaseDto;

/**
 * Unit Test für CaseDto
 */
final class CaseDtoTest extends TestCase
{
    #[Test]
    public function jsonSerializeReturnsCorrectStructure(): void
    {
        // Verwende UTC, um Zeitzonen-Probleme zu vermeiden
        $dateOfCrime = new \DateTimeImmutable('2024-01-15 10:30:00', new \DateTimeZone('UTC'));
        $dto = new CaseDto(
            'Test Case',
            'CASE-123',
            'Test description',
            $dateOfCrime,
            'Test Location'
        );
        
        $image1 = $this->createMock(ImageDto::class);
        $image1->method('jsonSerialize')->willReturn([
            'src' => 'https://example.com/image1.jpg',
            'meta' => ['width' => 800, 'height' => 600, 'mimeType' => 'image/jpeg'],
            'accessibility' => [
                'alt' => 'Test image',
                'title' => '',
                'isDecorative' => false,
            ],
            'sources' => [],
        ]);
        $image1->method('isDecorative')->willReturn(false);
        $image1->method('getAlt')->willReturn('Test image');
        
        $dto->setImages([$image1]);
        
        $result = $dto->jsonSerialize();
        
        self::assertIsArray($result);
        self::assertSame('Test Case', $result['title']);
        self::assertSame('CASE-123', $result['caseId']);
        self::assertSame('Test description', $result['description']);
        // Prüfe nur das Datum-Format, nicht die exakte Zeitzone
        self::assertStringStartsWith('2024-01-15T10:30:00', $result['dateOfCrime']);
        self::assertSame('Test Location', $result['location']);
        self::assertIsArray($result['images']);
        self::assertArrayHasKey('accessibility', $result);
        self::assertTrue($result['accessibility']['hasImages']);
        self::assertSame(1, $result['accessibility']['imagesWithAltText']);
    }

    #[Test]
    public function jsonSerializeHandlesNullDateOfCrime(): void
    {
        $dto = new CaseDto(
            'Test Case',
            'CASE-123',
            'Test description',
            null,
            ''
        );
        
        $result = $dto->jsonSerialize();
        
        self::assertNull($result['dateOfCrime']);
    }
}

