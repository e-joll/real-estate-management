<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/redirect', name: 'app_redirect')]
    public function redirectBasedOnRole(): RedirectResponse
    {
        if ($this->isGranted('ROLE_DIRECTOR')) {
            return new RedirectResponse($this->generateUrl('director_dashboard'));
        } elseif ($this->isGranted('ROLE_AGENT')) {
            return new RedirectResponse($this->generateUrl('agent_dashboard'));
        } elseif ($this->isGranted('ROLE_CUSTOMER')) {
            return new RedirectResponse($this->generateUrl('customer_dashboard'));
        } else {
            return new RedirectResponse($this->generateUrl('app_login'));
        }
    }
}
