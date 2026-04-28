<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Cross-Origin Resource Sharing (CORS) Configuration
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
 */
class Cors extends BaseConfig
{
    /**
     * The default allowed origins.
     *
     * In development, allows all common localhost ports
     * In production, requires explicit configuration via env variables
     */
    public function getAllowedOrigins(): array
    {
        if (ENVIRONMENT === 'production') {
            $frontendUrl = env('app.frontendURL') ?: getenv('APP_FRONTEND_URL');
            if ($frontendUrl) {
                return [$frontendUrl];
            }
            return [];
        }

        // Development: Allow common localhost ports
        return [
            'http://localhost:5173',
            'http://localhost:5174',
            'http://localhost:5175',
            'http://localhost:3000',
            'http://localhost:3001',
            'http://localhost:8081',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:5174',
            'http://127.0.0.1:5175',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:3001',
            'http://127.0.0.1:8081',
        ];
    }

    /**
     * The default allowed origins - statically initialized for development
     * Will be overridden by getAllowedOrigins() method in CorsFilter
     */
    public array $allowedOrigins = [
        'http://localhost:5173',
        'http://localhost:5174',
        'http://localhost:5175',
        'http://localhost:3000',
        'http://localhost:3001',
        'http://localhost:8081',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:5174',
        'http://127.0.0.1:5175',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
        'http://127.0.0.1:8081',
    ];

    /**
     * The default allowed headers.
     */
    public array $allowedHeaders = [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'Origin',
        'X-CSRF-TOKEN',
    ];

    /**
     * The default allowed methods.
     */
    public array $allowedMethods = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS',
    ];

    /**
     * The default exposed headers.
     */
    public array $exposedHeaders = [];

    /**
     * Whether credentials (cookies, authorization headers, or TLS client
     * certificates) should be exposed.
     */
    public bool $supportsCredentials = true;

    /**
     * The maximum time in seconds that a preflight request can be cached.
     */
    public int $maxAge = 7200;

    /**
     * Whether to send the Vary: Origin header.
     */
    public bool $varyHeader = false;
}