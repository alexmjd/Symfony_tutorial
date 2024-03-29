<?php

namespace App\Controller;

use App\Security\UserConfirmationService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/")
 */
class DefaultController extends AbstractController {
        
/**
 * @Route("/", name="default_index")
 *
 * @return JsonResponse
 */
    public function index() {
        return new JsonResponse([
            'action' => 'index',
            'time' => time()
        ]);
    }

    /**
     * @Route("/confirm-user/{token}", name="default_confirm_token")
     */
    public function confirmUser(
        string $token,
        UserConfirmationService $userConfirmationService
    ) {
        $userConfirmationService->confirmUser($token);

        return $this->redirectToRoute('default_index');
    }
}

?>