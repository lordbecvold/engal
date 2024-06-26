<?php

namespace App\Controller;

use OpenApi\Attributes\Tag;
use OpenApi\Attributes\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class IndexController
 *
 * Main app index Controller for check api status
 *
 * @package App\Controller
 */
class IndexController extends AbstractController
{
    /**
     * Index init action to return a JSON response.
     *
     * Return backend status with basic app config
     *
     * @return JsonResponse Returns a JSON response with status, code, and backend version.
     */
    #[Tag(name: "Index")]
    #[Response(response: 200, description: 'The backend app version and status.')]
    #[Route(['/', '/api'], methods: ['GET'], name: 'index')]
    public function index(): JsonResponse
    {
        return $this->json([
            'status' => 'success',
            'code' => JsonResponse::HTTP_OK,
            'message' => 'Engal API is loaded success',
            'backend_version' => $_ENV['APP_VERSION'],
            'security_policy' => [
                'REGISTER_ENABLED' => $_ENV['REGISTER_ENABLED'],
                'MIN_USERNAME_LENGTH' => intval($_ENV['MIN_USERNAME_LENGTH']),
                'MAX_USERNAME_LENGTH' => intval($_ENV['MAX_USERNAME_LENGTH']),
                'MIN_PASSWORD_LENGTH' => intval($_ENV['MIN_PASSWORD_LENGTH']),
                'MAX_PASSWORD_LENGTH' => intval($_ENV['MAX_PASSWORD_LENGTH'])
            ]
        ], JsonResponse::HTTP_OK);
    }
}
