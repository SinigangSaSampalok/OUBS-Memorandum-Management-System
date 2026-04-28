<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Config\Cors as CorsConfig;

class BaseController extends Controller
{
    protected $helpers = [];

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

        $origin = $this->resolveOrigin($request);

        // Set CORS headers on EVERY response
        if ($origin) {
            $this->response->setHeader('Access-Control-Allow-Origin', $origin);
            $this->response->setHeader('Vary', 'Origin');
        }
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
        $this->response->setHeader('Access-Control-Allow-Credentials', 'true');
        $this->response->setHeader('Access-Control-Max-Age', '86400');

        // Handle preflight OPTIONS requests
        if ($this->request->getMethod() === 'options') {
            $this->response->setStatusCode(200);
            $this->response->setBody('');
            $this->response->send();
            exit;
        }
    }
}
