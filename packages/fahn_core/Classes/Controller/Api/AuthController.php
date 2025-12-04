<?php

declare(strict_types=1);

namespace Fahn\Core\Controller\Api;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class AuthController extends ActionController
{
    private Configuration $jwtConfig;
    
    public function __construct()
    {
        parent::__construct();
        try {
            $secret = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] ?? 'change-me-in-production';
            $this->jwtConfig = Configuration::forSymmetricSigner(
                new Sha256(),
                InMemory::plainText($secret)
            );
        } catch (\Exception $e) {
            // Fallback for development
            $this->jwtConfig = null;
        }
    }

    /**
     * POST /api/auth/login
     * Body: {"username": "admin", "password": "password"}
     */
    public function loginAction(): ResponseInterface
    {
        try {
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
        
        if (!isset($data['username']) || !isset($data['password'])) {
            return new JsonResponse(['error' => 'Username and password required'], 400);
        }
        
        // Find backend user
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('be_users');
        
        $user = $queryBuilder
            ->select('*')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->eq(
                    'username',
                    $queryBuilder->createNamedParameter($data['username'])
                ),
                $queryBuilder->expr()->eq('disable', 0),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->executeQuery()
            ->fetchAssociative();
        
        if (!$user) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }
        
        // Verify password
        $passwordHashFactory = GeneralUtility::makeInstance(PasswordHashFactory::class);
        $hashInstance = $passwordHashFactory->get($user['password'], 'BE');
        
        if (!$hashInstance->checkPassword($data['password'], $user['password'])) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }
        
        // Generate JWT token
        $now = new \DateTimeImmutable();
        $token = $this->jwtConfig->builder()
            ->issuedBy('fahn-core-typo3')
            ->permittedFor('fahn-core-frontend')
            ->identifiedBy(bin2hex(random_bytes(16)))
            ->issuedAt($now)
            ->expiresAt($now->modify('+8 hours'))
            ->withClaim('uid', (int)$user['uid'])
            ->withClaim('username', $user['username'])
            ->withClaim('admin', (bool)$user['admin'])
            ->withClaim('email', $user['email'] ?? '')
            ->withClaim('realName', $user['realName'] ?? '')
            ->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey());
        
        return new JsonResponse([
            'token' => $token->toString(),
            'expires_in' => 28800, // 8 hours
            'user' => [
                'uid' => (int)$user['uid'],
                'username' => $user['username'],
                'email' => $user['email'] ?? '',
                'realName' => $user['realName'] ?? '',
                'admin' => (bool)$user['admin']
            ]
        ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Authentication error: ' . $e->getMessage(),
                'debug' => true
            ], 500);
        }
    }

    /**
     * POST /api/auth/validate
     * Header: Authorization: Bearer <token>
     */
    public function validateAction(): ResponseInterface
    {
        // Set CORS headers
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return new JsonResponse([]);
        }
        
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return new JsonResponse(['valid' => false, 'error' => 'No token provided'], 401);
        }
        
        try {
            $token = $this->jwtConfig->parser()->parse($matches[1]);
            $constraints = $this->jwtConfig->validationConstraints();
            
            if (!$this->jwtConfig->validator()->validate($token, ...$constraints)) {
                return new JsonResponse(['valid' => false, 'error' => 'Invalid token'], 401);
            }
            
            return new JsonResponse([
                'valid' => true,
                'user' => [
                    'uid' => $token->claims()->get('uid'),
                    'username' => $token->claims()->get('username'),
                    'admin' => $token->claims()->get('admin'),
                ]
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['valid' => false, 'error' => 'Token parsing failed'], 401);
        }
    }
}