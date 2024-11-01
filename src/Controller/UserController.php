<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/api/buyers', name: 'api_buyer')]
    public function search(
        Request $request,
        UserRepository $userRepository): JsonResponse
    {
        $id = $request->query->get('id');
        $email = $request->query->get('email');
        $firstName = $request->query->get('firstName');
        $lastName = $request->query->get('lastName');

        $buyers = $userRepository->findByCriteria(
            $id ?: "",
            $email ?: "",
            $firstName ?: "",
            $lastName ?: "",
        );

        // Sérialisation manuelle des données
        $buyersArray = [];
        foreach ($buyers as $buyer) {
            $buyersArray[] = [
                'id' => $buyer->getId(),
                'email' => $buyer->getEmail(),
                'firstName' => $buyer->getFirstName(),
                'lastName' => $buyer->getLastName(),
            ];
        }

        return new JsonResponse($buyersArray);
    }

    #[Route('/index', name: 'index')]
    public function index(UserRepository $userRepository)
    {
        $buyers = $userRepository->findByCriteria(
            "",
            "",
            "",
            "",
        );

        return $this->render('user/index.html.twig', ['buyers' => $buyers]);
    }
}
