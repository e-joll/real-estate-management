<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Director\AppointmentCrudController;
use App\Controller\Admin\Director\InquiryCrudController;
use App\Controller\Admin\Director\NotificationCrudController;
use App\Controller\Admin\Director\PropertyCrudController;
use App\Entity\Appointment;
use App\Entity\Inquiry;
use App\Entity\Property;
use App\Repository\NotificationRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use phpDocumentor\Reflection\Types\This;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CUSTOMER')]
class CustomerDashboardController extends DashboardController
{
    public function __construct(
        private readonly Security $security,
        private readonly NotificationRepository $notificationRepository)
    {
    }

    #[Route('/customer', name: 'customer_dashboard')]
    public function index(): Response
    {
        return $this->render('admin/customer_dashboard.html.twig');
    }

    public function configureMenuItems(): iterable
    {
        $unreadCount = $this->notificationRepository->countUnreadNotifications($this->security->getUser());

        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Propriétés', 'fas fa-city', Property::class)
            ->setController(PropertyCrudController::class);
        yield MenuItem::linkToCrud('Notifications', 'fas fa-bell', Appointment::class)
            ->setController(NotificationCrudController::class)
            ->setBadge($unreadCount > 0 ? $unreadCount : null);
        yield MenuItem::linkToCrud('Rendez-vous', 'fas fa-calendar', Appointment::class)
            ->setController(AppointmentCrudController::class);
        yield MenuItem::linkToCrud('Demandes', 'fas fa-question-circle', Inquiry::class)
            ->setController(InquiryCrudController::class);
    }
}
