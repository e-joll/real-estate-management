<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Director\AppointmentCrudController;
use App\Entity\Appointment;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CUSTOMER')]
class CustomerDashboardController extends DashboardController
{
    #[Route('/customer', name: 'customer_dashboard')]
    public function index(): Response
    {
        return $this->render('admin/customer_dashboard.html.twig');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Rendez-vous', 'fas fa-calendar', Appointment::class)
            ->setController(AppointmentCrudController::class);
    }
}
