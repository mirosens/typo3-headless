<?php

declare(strict_types=1);

namespace Fahn\CoreFahndung\Controller;

use Fahn\CoreFahndung\Domain\Model\Fahndung;
use Fahn\CoreFahndung\Domain\Repository\FahndungRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * API Controller für Fahndungs-Ressourcen
 * Stellt Endpunkte für CRUD-Operationen bereit und handhabt die JSON-Serialisierung.
 * 
 * Features:
 * - Serverseitige Paginierung
 * - Kombinierte Suchlogik
 * - Strikte Input-Validierung und XSS-Maskierung
 * - PSR-3 Logging
 */
class FahndungController extends ActionController
{
    public function __construct(
        private readonly FahndungRepository $fahndungRepository,
        private readonly PersistenceManager $persistenceManager,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Listet Fahndungen mit Paginierung, Kategoriefilter und Volltextsuche.
     * GET /?tx_fahncorefahndung_api[action]=list
     */
    public function listAction(): ResponseInterface
    {
        try {
            // 1. Sanitization & Defaults
            // Paginierungsparameter werden auf Integers gecastet und begrenzt,
            // um Datenbanklast und SQL-Injection zu verhindern.
            $page = max(1, (int)($this->request->getArgument('page') ?? 1));
            $limit = min(100, max(1, (int)($this->request->getArgument('limit') ?? 10)));
            $offset = ($page - 1) * $limit;
            
            $categoryUid = $this->request->hasArgument('category') 
               ? (int)$this->request->getArgument('category') 
                : null;
                
            $search = $this->request->hasArgument('search') 
               ? trim((string)$this->request->getArgument('search')) 
                : null;

            // 2. Dispatching an Repository-Methoden
            // Entscheidungsweg für die effizienteste Abfragestrategie.
            if ($categoryUid) {
                $fahndungen = $this->fahndungRepository->findByCategory($categoryUid, $limit, $offset);
            } elseif ($search) {
                $fahndungen = $this->fahndungRepository->findBySearchTerm($search, $limit, $offset);
            } else {
                $fahndungen = $this->fahndungRepository->findActive($limit, $offset);
            }

            // 3. Metadaten
            $total = $this->fahndungRepository->countAll();
            
            // 4. Serialisierung
            // Manuelle Umwandlung in Arrays statt JSON-Serialisierung ganzer Objekte,
            // um Kontrolle über das Datenformat zu behalten.
            $items = [];
            foreach ($fahndungen as $fahndung) {
                $items[] = $this->serializeFahndung($fahndung);
            }

            return $this->jsonResponseWithData([
                'success' => true,
                'data' => $items,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'totalPages' => (int) ceil($total / $limit),
                ],
            ]);

        } catch (\Exception $e) {
            // Security: Niemals interne Exception-Details an die API ausgeben.
            $this->logger->error('Fahndung list failed', ['error' => $e->getMessage()]);
            return $this->jsonResponseWithData(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Zeigt eine einzelne Fahndung an.
     * GET /?tx_fahncorefahndung_api[action]=show&tx_fahncorefahndung_api[uid]=123
     */
    public function showAction(): ResponseInterface
    {
        try {
            $uid = (int)$this->request->getArgument('uid');
            $fahndung = $this->fahndungRepository->findByUid($uid);

            // Prüfung auf Existenz UND Veröffentlichungsstatus
            if (!$fahndung || !$fahndung->getIsPublished()) {
                return $this->jsonResponseWithData(['error' => 'Fahndung not found'], 404);
            }

            return $this->jsonResponseWithData([
                'success' => true,
                'data' => $this->serializeFahndung($fahndung, true),
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Fahndung show failed', ['uid' => $uid ?? 0, 'error' => $e->getMessage()]);
            return $this->jsonResponseWithData(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Erstellt eine neue Fahndung (Geschützt).
     * POST /?tx_fahncorefahndung_api[action]=create
     */
    public function createAction(): ResponseInterface
    {
        // Auth Check vor jeglicher Verarbeitung
        if (!$this->isUserLoggedIn()) {
            return $this->jsonResponseWithData(['error' => 'Unauthorized'], 401);
        }

        try {
            $rawBody = $this->request->getBody()->getContents();
            $data = json_decode($rawBody, true);

            // 1. Validierung
            if (empty($data['title']) || strlen($data['title']) < 3) {
                return $this->jsonResponseWithData(['error' => 'Title must be at least 3 characters'], 400);
            }
            if (empty($data['description'])) {
                return $this->jsonResponseWithData(['error' => 'Description is required'], 400);
            }

            // 2. Erstellung und XSS-Prävention
            $fahndung = GeneralUtility::makeInstance(Fahndung::class);
            // htmlspecialchars verhindert Stored XSS
            $fahndung->setTitle(htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8'));
            $fahndung->setDescription(htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8'));
            $fahndung->setCaseId($data['caseId'] ?? '');
            $fahndung->setLocation($data['location'] ?? '');
            if (isset($data['dateOfCrime'])) {
                $fahndung->setDateOfCrime(new \DateTime($data['dateOfCrime']));
            }
            // Standardmäßig false, wenn nicht explizit gesetzt
            $fahndung->setIsPublished((bool)($data['isPublished'] ?? false));

            $this->fahndungRepository->add($fahndung);
            $this->persistenceManager->persistAll();

            $this->logger->info('Fahndung created', ['uid' => $fahndung->getUid()]);

            return $this->jsonResponseWithData([
                'success' => true,
                'data' => $this->serializeFahndung($fahndung)
            ], 201); // 201 Created

        } catch (\Exception $e) {
            $this->logger->error('Fahndung create failed', ['error' => $e->getMessage()]);
            return $this->jsonResponseWithData(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Aktualisiert eine bestehende Fahndung (Geschützt).
     * PUT /?tx_fahncorefahndung_api[action]=update
     */
    public function updateAction(): ResponseInterface
    {
        if (!$this->isUserLoggedIn()) {
            return $this->jsonResponseWithData(['error' => 'Unauthorized'], 401);
        }

        try {
            $uid = (int)$this->request->getArgument('uid');
            $fahndung = $this->fahndungRepository->findByUid($uid);

            if (!$fahndung) {
                return $this->jsonResponseWithData(['error' => 'Fahndung not found'], 404);
            }

            $rawBody = $this->request->getBody()->getContents();
            $data = json_decode($rawBody, true);

            // Selektives Update der Felder
            if (isset($data['title'])) {
                $fahndung->setTitle(htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8'));
            }
            if (isset($data['description'])) {
                $fahndung->setDescription(htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8'));
            }
            if (isset($data['caseId'])) {
                $fahndung->setCaseId($data['caseId']);
            }
            if (isset($data['location'])) {
                $fahndung->setLocation($data['location']);
            }
            if (isset($data['isPublished'])) {
                $fahndung->setIsPublished((bool)$data['isPublished']);
            }
            if (isset($data['dateOfCrime'])) {
                $fahndung->setDateOfCrime(new \DateTime($data['dateOfCrime']));
            }

            $this->fahndungRepository->update($fahndung);
            $this->persistenceManager->persistAll();

            $this->logger->info('Fahndung updated', ['uid' => $uid]);

            return $this->jsonResponseWithData([
                'success' => true,
                'data' => $this->serializeFahndung($fahndung)
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Fahndung update failed', ['error' => $e->getMessage()]);
            return $this->jsonResponseWithData(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Löscht eine Fahndung (Geschützt).
     * DELETE /?tx_fahncorefahndung_api[action]=delete
     */
    public function deleteAction(): ResponseInterface
    {
        if (!$this->isUserLoggedIn()) {
            return $this->jsonResponseWithData(['error' => 'Unauthorized'], 401);
        }

        try {
            $uid = (int)$this->request->getArgument('uid');
            $fahndung = $this->fahndungRepository->findByUid($uid);

            if (!$fahndung) {
                return $this->jsonResponseWithData(['error' => 'Fahndung not found'], 404);
            }

            $this->fahndungRepository->remove($fahndung);
            $this->persistenceManager->persistAll();

            $this->logger->info('Fahndung deleted', ['uid' => $uid]);

            return $this->jsonResponseWithData(['success' => true]);

        } catch (\Exception $e) {
            $this->logger->error('Fahndung delete failed', ['error' => $e->getMessage()]);
            return $this->jsonResponseWithData(['error' => 'Internal Server Error'], 500);
        }
    }

    // --- HELPER METHODS ---

    /**
     * Prüft, ob ein gültiger Frontend-User (Session) existiert.
     */
    private function isUserLoggedIn(): bool
    {
        return isset($GLOBALS['TSFE']->fe_user->user['uid']) 
            && (int)$GLOBALS['TSFE']->fe_user->user['uid'] > 0;
    }

    /**
     * Transformiert das Domain-Model in ein JSON-konformes Array.
     * Verhindert Recursion und kontrolliert die Ausgabe.
     */
    private function serializeFahndung(Fahndung $fahndung, bool $detailed = false): array
    {
        $data = [
            'uid' => $fahndung->getUid(),
            'title' => $fahndung->getTitle(),
            'description' => $fahndung->getDescription(),
            'caseId' => $fahndung->getCaseId(),
            'location' => $fahndung->getLocation(),
            'dateOfCrime' => $fahndung->getDateOfCrime()?->format('c'),
            'isPublished' => $fahndung->getIsPublished(),
        ];

        if ($detailed) {
            // Bilder und Kategorien nur in Detailansicht laden (Performance)
            $images = [];
            foreach ($fahndung->getImages() as $image) {
                $images[] = [
                    'uid' => $image->getUid(),
                    'publicUrl' => $image->getOriginalResource()?->getPublicUrl() ?? '',
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

    /**
     * Standardisierte JSON-Antwort mit CORS-Headern.
     */
    protected function jsonResponseWithData(array $data, int $status = 200): ResponseInterface
    {
        $this->response->setStatus($status);
        $this->response->setHeader('Content-Type', 'application/json; charset=utf-8');
        // Environment-Variable für Production nutzen, Fallback für Localhost Dev
        $corsOrigin = $_ENV['TYPO3_CORS_ALLOWED_ORIGINS'] ?? 'http://localhost:3000';
        $origins = explode(',', $corsOrigin);
        $origin = $origins[0] ?? 'http://localhost:3000';
        $this->response->setHeader('Access-Control-Allow-Origin', $origin);
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        // Wichtig für Cookies!
        $this->response->setHeader('Access-Control-Allow-Credentials', 'true');
        
        $this->response->setContent(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        return $this->response;
    }
}

