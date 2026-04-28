<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Config\Cors as CorsConfig;

class CorsController extends Controller
{
    private function resolveOrigin(RequestInterface $request): ?string
    {
        $origin = $request->getHeaderLine('Origin');
        if ($origin === '') {
            return null;
        }

        $config = new CorsConfig();
        if (in_array('*', $config->allowedOrigins, true)) {
            return $origin;
        }

        if (in_array($origin, $config->allowedOrigins, true)) {
            return $origin;
        }

        if (preg_match('#^http://(localhost|127\.0\.0\.1):\d+$#', $origin)) {
            return $origin;
        }

        return null;
    }

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }

    /**
     * Handles all preflight OPTIONS requests
     */
    public function preflight(...$args)
    {
        $origin = $this->resolveOrigin($this->request);
        if ($origin) {
            $this->response->setHeader('Access-Control-Allow-Origin', $origin);
            $this->response->setHeader('Vary', 'Origin');
        }
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN');
        $this->response->setHeader('Access-Control-Allow-Credentials', 'true');
        $this->response->setHeader('Access-Control-Max-Age', '86400');
        $this->response->setStatusCode(200);
        $this->response->setBody('');

        return $this->response;
    }
}
