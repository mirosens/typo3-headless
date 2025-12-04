<?php

declare(strict_types=1);

namespace Fahn\Core\Controller\Api;

use Fahn\CoreFahndung\Domain\Repository\FahndungRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class DashboardController extends ActionController
{
    public function __construct(
        private readonly FahndungRepository $fahndungRepository
    ) {}

    /**
     * GET /api/dashboard/stats
     */
    public function statsAction(): ResponseInterface
    {
        // Set CORS headers
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return new JsonResponse([]);
        }
        
        $activeFahndungen = $this->fahndungRepository->findActive();
        $activeFahndungenCount = count($activeFahndungen->toArray());
        
        // Mock data for now - you can implement real logic later
        $openHinweise = 0;
        $lastActivity = null;
        
        return new JsonResponse([
            'activeFahndungen' => $activeFahndungenCount,
            'openHinweise' => $openHinweise,
            'lastActivity' => $lastActivity,
        ]);
    }
}