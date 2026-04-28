<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Cors as CorsConfig;

class CorsFilter implements FilterInterface
{
    private function resolveOrigin(RequestInterface $request): ?string
    {
        $origin = $request->getHeaderLine('Origin');
        if ($origin === '') {
            return null;
        }

        $config = new CorsConfig();
        // Get allowed origins dynamically (handles production env URLs)
        $allowedOrigins = $config->getAllowedOrigins();
        
        if (in_array('*', $allowedOrigins, true)) {
            return $origin;
        }

        if (in_array($origin, $allowedOrigins, true)) {
            return $origin;
        }

        // Fallback regex for any localhost/127.0.0.1 on any port
        if (preg_match('#^http://(localhost|127\.0\.0\.1):\d+$#', $origin)) {
            return $origin;
        }

        return null;
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        $origin = $this->resolveOrigin($request);

        // Set CORS headers for all requests
        if ($origin) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
        }
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE, PATCH');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');

        // Handle preflight OPTIONS request (case-insensitive)
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            http_response_code(200);
            exit(0);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $origin = $this->resolveOrigin($request);

        // Add CORS headers to response
        if ($origin) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
            $response->setHeader('Vary', 'Origin');
        }
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE, PATCH');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN');
        $response->setHeader('Access-Control-Allow-Credentials', 'true');
        
        return $response;
    }
}
