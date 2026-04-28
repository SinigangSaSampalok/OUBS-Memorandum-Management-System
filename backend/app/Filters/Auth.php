<?php
namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class Auth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return service('response')->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $token = $matches[1];

        // Tokens are base64-encoded JSON (not JWT)
        $decoded = base64_decode($token, true);
        if ($decoded === false) {
            return service('response')->setStatusCode(401)->setJSON(['error' => 'Invalid token']);
        }

        $payload = json_decode($decoded, true);
        if (!is_array($payload)) {
            return service('response')->setStatusCode(401)->setJSON(['error' => 'Invalid token']);
        }

        if (isset($payload['exp']) && time() > (int) $payload['exp']) {
            return service('response')->setStatusCode(401)->setJSON(['error' => 'Token expired']);
        }

        // You can optionally set user info in request for controllers
        $request->user = $payload;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing needed after
    }
}
