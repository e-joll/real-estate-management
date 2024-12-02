<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Director\AppointmentCrudController;
use App\Controller\Admin\Director\FeatureCrudController;
use App\Controller\Admin\Director\InquiryCrudController;
use App\Controller\Admin\Director\NotificationCrudController;
use App\Controller\Admin\Director\PropertyCrudController;
use App\Controller\Admin\Director\PurchaseCrudController;
use App\Controller\Admin\Director\UserCrudController;
use App\Entity\Appointment;
use App\Entity\Feature;
use App\Entity\Inquiry;
use App\Entity\Notification;
use App\Entity\Property;
use App\Entity\Purchase;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_AGENT')]
class AgentDashboardController extends DashboardController
{
    #[Route('/agent', name: 'agent_dashboard')]
    public function index(): Response
    {
        return $this->render('admin/agent_dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Real Estate Management');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-user', User::class)
            ->setController(UserCrudController::class);
        yield MenuItem::linkToCrud('Propriétés', 'fas fa-city', Property::class)
            ->setController(PropertyCrudController::class);
        yield MenuItem::linkToCrud('Caractéristiques', 'fas fa-cogs', Feature::class)
            ->setController(FeatureCrudController::class);
        yield MenuItem::linkToCrud('Achats', 'fas fa-cart-shopping', Purchase::class)
            ->setController(PurchaseCrudController::class);
        yield MenuItem::linkToCrud('Rendez-vous', 'fas fa-calendar', Appointment::class)
            ->setController(AppointmentCrudController::class);
        yield MenuItem::linkToCrud('Demandes', 'fas fa-question-circle', Inquiry::class)
            ->setController(InquiryCrudController::class);
        yield MenuItem::linkToCrud('Notifications', 'fas fa-bell', Notification::class)
            ->setController(NotificationCrudController::class);
    }
}
