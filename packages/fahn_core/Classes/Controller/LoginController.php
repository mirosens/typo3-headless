<?php

declare(strict_types=1);

namespace Fahn\Core\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Authentication\LoginType;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Handles Authentication via Standard TYPO3 Sessions
 * Integriert Brute-Force Schutz.
 */
class LoginController extends ActionController
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_DURATION = 600; // 10 minutes

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Context $context
    ) {}

    /**
     * Login Action
     * POST /?tx_fahncore_login[action]=login
     */
    public function loginAction(): ResponseInterface
    {
        try {
            $rawBody = $this->request->getBody()->getContents();
            $data = json_decode($rawBody, true);

            // 1. Validierung
            if (empty($data['username']) || empty($data['password'])) {
                return $this->jsonResponseWithData(['error' => 'Username and password required'], 400);
            }

            // 2. Brute-Force Check
            if ($this->isIpLocked()) {
                $this->logger->warning('Login blocked due to rate limit', ['ip' => $this->getClientIp()]);
                return $this->jsonResponseWithData(['error' => 'Too many login attempts. Please try again later.'], 429);
            }

            // 3. Authentifizierung gegen Datenbank
            $loginData = [
                'uname' => $data['username'],
                'uident' => $data['password'],
                'status' => LoginType::LOGIN,
            ];

            // Wir initialisieren den Auth-Prozess manuell, um volle Kontrolle zu haben
            // Verwende TSFE->fe_user falls verfügbar, sonst neue Instanz
            if (isset($GLOBALS['TSFE']) && $GLOBALS['TSFE']->fe_user instanceof FrontendUserAuthentication) {
                $feUser = $GLOBALS['TSFE']->fe_user;
            } else {
                $feUser = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
                $feUser->start();
            }
            $feUser->checkPid = false; // User überall finden
            $info = $feUser->getAuthInfoArray();
            $user = $feUser->fetchUserRecord($info['db_user'], $loginData['uname']);

            // User existiert nicht
            if (!$user) {
                $this->incrementLoginAttempts();
                $this->logger->warning('Login failed - user not found', ['username' => $data['username']]);
                return $this->jsonResponseWithData(['error' => 'Invalid credentials'], 401);
            }

            // Passwort falsch
            if (!$feUser->compareUident($user, $loginData['uident'])) {
                $this->incrementLoginAttempts();
                $this->logger->warning('Login failed - wrong password', ['username' => $data['username']]);
                return $this->jsonResponseWithData(['error' => 'Invalid credentials'], 401);
            }

            // 4. Session erstellen (Erzeugt fe_typo_user Cookie)
            $feUser->createUserSession($user);
            $feUser->user = $user;
            $feUser->setAndSaveSessionData('user', $user);
            // Session speichern
            $feUser->storeSessionData();
            
            // 5. Cleanup & Log
            $this->resetLoginAttempts();
            $this->logger->info('User logged in successfully', ['uid' => $user['uid']]);

            return $this->jsonResponseWithData([
                'success' => true,
                'data' => [
                    'user' => [
                        'uid' => (int)$user['uid'],
                        'username' => $user['username'],
                        'email' => $user['email'] ?? '',
                        'name' => $user['name'] ?? $user['username'],
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Login error', ['error' => $e->getMessage()]);
            return $this->jsonResponseWithData(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Check Session State (Für SPA Initialisierung)
     * GET /?tx_fahncore_login[action]=session
     */
    public function sessionAction(): ResponseInterface
    {
        try {
            // Verwende TSFE->fe_user falls verfügbar, sonst Context API
            $user = null;
            if (isset($GLOBALS['TSFE']) && $GLOBALS['TSFE']->fe_user instanceof FrontendUserAuthentication) {
                $feUser = $GLOBALS['TSFE']->fe_user;
                $user = $feUser->user ?? null;
            } else {
                $isLoggedIn = $this->context->getPropertyFromAspect('frontend.user', 'isLoggedIn', false);
                if ($isLoggedIn) {
                    $userUid = $this->context->getPropertyFromAspect('frontend.user', 'id', 0);
                    if ($userUid > 0) {
                        $feUser = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
                        $feUser->start();
                        $user = $feUser->user ?? null;
                    }
                }
            }

            if (!$user || empty($user['uid'])) {
                return $this->jsonResponseWithData([
                    'success' => true,
                    'data' => [
                        'authenticated' => false,
                        'user' => null,
                    ],
                ]);
            }

            return $this->jsonResponseWithData([
                'success' => true,
                'data' => [
                    'authenticated' => true,
                    'user' => [
                        'uid' => (int)$user['uid'],
                        'username' => $user['username'],
                        'email' => $user['email'] ?? '',
                        'name' => $user['name'] ?? $user['username'],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponseWithData(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Logout
     * POST /?tx_fahncore_login[action]=logout
     */
    public function logoutAction(): ResponseInterface
    {
        // Invalidiert Session in DB und löscht Cookie
        if (isset($GLOBALS['TSFE']) && $GLOBALS['TSFE']->fe_user instanceof FrontendUserAuthentication) {
            $feUser = $GLOBALS['TSFE']->fe_user;
        } else {
            $feUser = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
            $feUser->start();
        }
        $feUser->logoff();
        
        // 204 No Content für Logout (kein Body nötig)
        $corsOrigin = $_ENV['TYPO3_CORS_ALLOWED_ORIGINS'] ?? 'http://localhost:3000';
        $origins = explode(',', $corsOrigin);
        $origin = $origins[0] ?? 'http://localhost:3000';
        
        $response = new JsonResponse('', 204);
        $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        $response = $response->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response = $response->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        
        return $response;
    }

    // --- SECURITY HELPERS ---

    /**
     * Prüft, ob die IP gesperrt ist (via Caching Framework)
     */
    private function isIpLocked(): bool
    {
        $ip = $this->getClientIp();
        $cacheKey = 'login_attempts_' . md5($ip);
        $cache = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('fahn_core_login');
        $attempts = $cache->get($cacheKey);
        
        return $attempts && (int)$attempts >= self::MAX_LOGIN_ATTEMPTS;
    }

    private function incrementLoginAttempts(): void
    {
        $ip = $this->getClientIp();
        $cacheKey = 'login_attempts_' . md5($ip);
        $cache = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('fahn_core_login');
        
        $attempts = (int)($cache->get($cacheKey) ?? 0);
        $attempts++;
        
        $cache->set($cacheKey, $attempts, [], self::LOCKOUT_DURATION);
    }

    private function resetLoginAttempts(): void
    {
        $ip = $this->getClientIp();
        $cacheKey = 'login_attempts_' . md5($ip);
        $cache = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('fahn_core_login');
        $cache->remove($cacheKey);
    }

    private function getClientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    protected function jsonResponseWithData(array $data, int $status = 200): ResponseInterface
    {
        $corsOrigin = $_ENV['TYPO3_CORS_ALLOWED_ORIGINS'] ?? 'http://localhost:3000';
        $origins = explode(',', $corsOrigin);
        $origin = $origins[0] ?? 'http://localhost:3000';
        
        $response = new JsonResponse($data, $status);
        $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        $response = $response->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response = $response->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        
        return $response;
    }
}

