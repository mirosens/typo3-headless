<?php

declare(strict_types=1);

namespace Vendor\FahnCore\Tests\Functional\Api;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * A.9.4: Functional Test für Page API Endpoint (typeNum 834)
 * 
 * Validiert die JSON-Ausgabe der Headless API mit internen Requests.
 * Nutzt executeFrontendRequest statt externer HTTP-Requests.
 */
final class PageApiEndpointTest extends FunctionalTestCase
{
    /**
     * Instance Path - zeigt auf das TYPO3 Root-Verzeichnis
     * Muss gesetzt werden, bevor parent::setUp() aufgerufen wird
     */
    protected string $instancePath;

    /**
     * Extensions, die für den Test geladen werden müssen
     */
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/fahn_core',
        'typo3conf/ext/headless',
    ];

    /**
     * Core Extensions, die geladen werden müssen
     */
    protected array $coreExtensionsToLoad = [
        'core',
        'frontend',
        'extbase',
        'fluid',
    ];

    protected function setUp(): void
    {
        // Setze instancePath absolut zum TYPO3 Root
        // 3 Ebenen hoch: Tests/Functional/Api -> Tests -> fahn_core -> packages -> Root
        $this->instancePath = realpath(__DIR__ . '/../../../../') ?: __DIR__ . '/../../../../';
        parent::setUp();

        // 1. Importiere Testdaten
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/SysTemplate.csv');

        // 2. Setze Backend-User (optional, falls API geschützt)
        $this->setUpBackendUserFromFixture(1);

        // 3. Konfiguriere Frontend Root Page mit TypoScript
        // Dies ist essenziell für Routing und Headless-Ausgaben
        $this->setUpFrontendRootPage(
            1, // Root Page ID aus Pages.csv
            [
                'constants' => [
                    'EXT:fahn_core/Configuration/TypoScript/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fahn_core/Configuration/TypoScript/setup.typoscript',
                ],
            ]
        );
    }

    #[Test]
    public function pageApiReturnsValidJsonStructure(): void
    {
        // 1. Definiere den Request auf den API-Endpoint
        // typeNum 834 wird über ?type=834 oder Routing erreicht
        $uri = new Uri('https://example.com/?id=1&type=834');
        $request = new ServerRequest($uri, 'GET');

        // 2. Führe den Request intern aus (OHNE externen Webserver)
        // Dies durchläuft den gesamten TYPO3 Middleware Stack
        $response = $this->executeFrontendRequest($request);

        // 3. Validiere den Response-Status
        self::assertSame(200, $response->getStatusCode(), 'API sollte 200 OK liefern');
        self::assertStringContainsString(
            'application/json',
            $response->getHeaderLine('Content-Type'),
            'Response sollte JSON Content-Type haben'
        );

        // 4. Validiere den JSON-Body (Basis-Check)
        $body = (string)$response->getBody();
        $json = json_decode($body, true);

        self::assertIsArray($json, 'Response muss valides JSON sein');
        self::assertNotEmpty($json, 'Response sollte nicht leer sein');
    }

    #[Test]
    public function pageApiMatchesJsonSchemaContract(): void
    {
        $uri = new Uri('https://example.com/?id=1&type=834');
        $request = new ServerRequest($uri, 'GET');
        $response = $this->executeFrontendRequest($request);

        self::assertSame(200, $response->getStatusCode());

        $body = (string)$response->getBody();
        $data = json_decode($body, false); // Opis benötigt Objekt, nicht Array

        // JSON Schema aus docs/api/schema/v1/page.schema.json
        $schema = [
            'type' => 'object',
            'required' => ['id', 'slug', 'locale', 'title', 'meta', 'content'],
            'properties' => [
                'id' => ['type' => 'string', 'minLength' => 1],
                'slug' => ['type' => 'string'],
                'locale' => ['type' => 'string'],
                'title' => ['type' => 'string'],
                'meta' => ['type' => 'object'],
                'content' => ['type' => 'array'],
            ],
            'additionalProperties' => false,
        ];

        // Validiere mit JSON Schema (falls opis/json-schema installiert)
        if (class_exists(\Opis\JsonSchema\Validator::class)) {
            $validator = new \Opis\JsonSchema\Validator();
            $result = $validator->validate($data, $schema);

            if (!$result->isValid()) {
                $error = $result->error();
                $formatter = new \Opis\JsonSchema\Errors\ErrorFormatter();
                $errors = $formatter->format($error);
                self::fail('Schema Validation Failed: ' . json_encode($errors, JSON_PRETTY_PRINT));
            }

            self::assertTrue($result->isValid(), 'Response sollte dem JSON Schema entsprechen');
        } else {
            // Fallback: Manuelle Validierung
            self::assertArrayHasKey('id', (array)$data, 'Response sollte "id" enthalten');
            self::assertArrayHasKey('slug', (array)$data, 'Response sollte "slug" enthalten');
            self::assertArrayHasKey('title', (array)$data, 'Response sollte "title" enthalten');
        }
    }
}

