<?php

declare(strict_types=1);

namespace Vendor\FahnCore\Tests\Functional\Api;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * A.9.4: Functional Test für Navigation API Endpoint (typeNum 835)
 */
final class NavigationApiEndpointTest extends FunctionalTestCase
{
    /**
     * Instance Path - zeigt auf das TYPO3 Root-Verzeichnis
     * Muss gesetzt werden, bevor parent::setUp() aufgerufen wird
     */
    protected string $instancePath;

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/fahn_core',
        'typo3conf/ext/headless',
    ];

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

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/SysTemplate.csv');
        $this->setUpBackendUserFromFixture(1);

        $this->setUpFrontendRootPage(
            1,
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
    public function navigationApiReturnsValidJsonStructure(): void
    {
        $uri = new Uri('https://example.com/?id=1&type=835');
        $request = new ServerRequest($uri, 'GET');
        $response = $this->executeFrontendRequest($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $body = (string)$response->getBody();
        $json = json_decode($body, true);

        self::assertIsArray($json, 'Response muss valides JSON sein');
    }

    #[Test]
    public function navigationApiMatchesJsonSchemaContract(): void
    {
        $uri = new Uri('https://example.com/?id=1&type=835');
        $request = new ServerRequest($uri, 'GET');
        $response = $this->executeFrontendRequest($request);

        self::assertSame(200, $response->getStatusCode());

        $body = (string)$response->getBody();
        $data = json_decode($body, false);

        // Schema aus docs/api/schema/v1/navigation.schema.json
        $schema = [
            'type' => 'object',
            'required' => ['menus'],
            'properties' => [
                'version' => ['type' => 'integer', 'minimum' => 1],
                'menus' => ['type' => 'object'],
            ],
            'additionalProperties' => false,
        ];

        if (class_exists(\Opis\JsonSchema\Validator::class)) {
            $validator = new \Opis\JsonSchema\Validator();
            $result = $validator->validate($data, $schema);

            if (!$result->isValid()) {
                $error = $result->error();
                $formatter = new \Opis\JsonSchema\Errors\ErrorFormatter();
                $errors = $formatter->format($error);
                self::fail('Schema Validation Failed: ' . json_encode($errors, JSON_PRETTY_PRINT));
            }

            self::assertTrue($result->isValid());
        } else {
            // Fallback
            self::assertArrayHasKey('menus', (array)$data);
        }
    }
}

