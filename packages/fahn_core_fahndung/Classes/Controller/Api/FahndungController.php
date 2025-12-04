<?php

declare(strict_types=1);

namespace Fahn\CoreFahndung\Controller\Api;

use Fahn\CoreFahndung\Domain\Model\Fahndung;
use Fahn\CoreFahndung\Domain\Repository\FahndungRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class FahndungController extends ActionController
{
    public function __construct(
        private readonly FahndungRepository $fahndungRepository,
        private readonly PersistenceManager $persistenceManager
    ) {}

    /**
     * GET /api/fahndungen?page=1&limit=10&category=1&search=term
     */
    public function listAction(): ResponseInterface
    {
        // Set CORS headers
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return new JsonResponse([]);
        }
        
        $page = (int)($this->request->getArgument('page') ?? 1);
        $limit = min((int)($this->request->getArgument('limit') ?? 10), 100);
        $categoryUid = $this->request->hasArgument('category') 
            ? (int)$this->request->getArgument('category') 
            : null;
        $search = $this->request->hasArgument('search') 
            ? (string)$this->request->getArgument('search') 
            : null;
        
        $offset = ($page - 1) * $limit;
        
        // Use repository methods
        if ($categoryUid) {
            $fahndungen = $this->fahndungRepository->findByCategory($categoryUid);
        } else {
            $fahndungen = $this->fahndungRepository->findActive();
        }
        
        // Apply pagination manually (since TYPO3 QueryResult doesn't have built-in pagination)
        $allItems = $fahndungen->toArray();
        $total = count($allItems);
        $items = array_slice($allItems, $offset, $limit);
        
        $serializedItems = [];
        foreach ($items as $fahndung) {
            $serializedItems[] = $this->serializeFahndung($fahndung);
        }
        
        return new JsonResponse([
            'items' => $serializedItems,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int)ceil($total / $limit),
            ]
        ]);
    }

    /**
     * GET /api/fahndungen/{uid}
     */
    public function showAction(): ResponseInterface
    {
        // Set CORS headers
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return new JsonResponse([]);
        }
        
        $uid = (int)$this->request->getArgument('uid');
        $fahndung = $this->fahndungRepository->findByUid($uid);
        
        if (!$fahndung) {
            return new JsonResponse(['error' => 'Fahndung not found'], 404);
        }
        
        return new JsonResponse($this->serializeFahndung($fahndung, true));
    }

    /**
     * POST /api/fahndungen
     * JWT Protected
     */
    public function createAction(): ResponseInterface
    {
        // Set CORS headers
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return new JsonResponse([]);
        }
        
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);
        
        if (!isset($data['title']) || !isset($data['description'])) {
            return new JsonResponse(['error' => 'Title and description required'], 400);
        }
        
        $fahndung = GeneralUtility::makeInstance(Fahndung::class);
        $fahndung->setTitle($data['title']);
        $fahndung->setDescription($data['description']);
        $fahndung->setCaseId($data['caseId'] ?? '');
        $fahndung->setLocation($data['location'] ?? '');
        
        if (isset($data['dateOfCrime'])) {
            try {
                $fahndung->setDateOfCrime(new \DateTime($data['dateOfCrime']));
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Invalid date format'], 400);
            }
        }
        
        $fahndung->setIsPublished($data['isPublished'] ?? false);
        
        // Handle image UIDs if provided
        if (isset($data['imageUids']) && is_array($data['imageUids'])) {
            // Add FileReferences (implementation depends on your FAL setup)
            // $this->addFileReferencesToFahndung($fahndung, $data['imageUids']);
        }
        
        $this->fahndungRepository->add($fahndung);
        $this->persistenceManager->persistAll();
        
        return new JsonResponse([
            'success' => true,
            'uid' => $fahndung->getUid(),
            'fahndung' => $this->serializeFahndung($fahndung)
        ], 201);
    }

    /**
     * PUT /api/fahndungen/{uid}
     * JWT Protected
     */
    public function updateAction(): ResponseInterface
    {
        // Set CORS headers
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'PUT, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return new JsonResponse([]);
        }
        
        $uid = (int)$this->request->getArgument('uid');
        $fahndung = $this->fahndungRepository->findByUid($uid);
        
        if (!$fahndung) {
            return new JsonResponse(['error' => 'Fahndung not found'], 404);
        }
        
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);
        
        if (isset($data['title'])) $fahndung->setTitle($data['title']);
        if (isset($data['description'])) $fahndung->setDescription($data['description']);
        if (isset($data['caseId'])) $fahndung->setCaseId($data['caseId']);
        if (isset($data['location'])) $fahndung->setLocation($data['location']);
        if (isset($data['isPublished'])) $fahndung->setIsPublished($data['isPublished']);
        
        if (isset($data['dateOfCrime'])) {
            try {
                $fahndung->setDateOfCrime(new \DateTime($data['dateOfCrime']));
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Invalid date format'], 400);
            }
        }
        
        $this->fahndungRepository->update($fahndung);
        $this->persistenceManager->persistAll();
        
        return new JsonResponse([
            'success' => true,
            'fahndung' => $this->serializeFahndung($fahndung)
        ]);
    }

    /**
     * DELETE /api/fahndungen/{uid}
     * JWT Protected
     */
    public function deleteAction(): ResponseInterface
    {
        // Set CORS headers
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'DELETE, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return new JsonResponse([]);
        }
        
        $uid = (int)$this->request->getArgument('uid');
        $fahndung = $this->fahndungRepository->findByUid($uid);
        
        if (!$fahndung) {
            return new JsonResponse(['error' => 'Fahndung not found'], 404);
        }
        
        $this->fahndungRepository->remove($fahndung);
        $this->persistenceManager->persistAll();
        
        return new JsonResponse(['success' => true]);
    }

    private function serializeFahndung(Fahndung $fahndung, bool $detailed = false): array
    {
        $data = [
            'uid' => $fahndung->getUid(),
            'title' => $fahndung->getTitle(),
            'description' => $fahndung->getDescription(),
            'caseId' => $fahndung->getCaseId(),
            'location' => $fahndung->getLocation(),
            'dateOfCrime' => $fahndung->getDateOfCrime()?->format('Y-m-d'),
            'isPublished' => $fahndung->getIsPublished(),
        ];
        
        if ($detailed) {
            // Add images, categories, etc.
            $images = [];
            foreach ($fahndung->getImages() as $image) {
                $images[] = [
                    'uid' => $image->getUid(),
                    'url' => $image->getOriginalResource()->getPublicUrl(),
                    'title' => $image->getTitle(),
                ];
            }
            $data['images'] = $images;
            
            $categories = [];
            foreach ($fahndung->getCategories() as $category) {
                $categories[] = [
                    'uid' => $category->getUid(),
                    'title' => $category->getTitle(),
                ];
            }
            $data['categories'] = $categories;
        }
        
        return $data;
    }
}