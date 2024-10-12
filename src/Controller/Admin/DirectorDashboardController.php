<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Director\AppointmentCrudController;
use App\Controller\Admin\Director\FeatureCrudController;
use App\Controller\Admin\Director\InquiryCrudController;
use App\Controller\Admin\Director\NotificationCrudController;
use App\Controller\Admin\Director\PropertyCrudController;
use App\Controller\Admin\Director\UserCrudController;
use App\Entity\Appointment;
use App\Entity\Feature;
use App\Entity\Inquiry;
use App\Entity\Notification;
use App\Entity\Property;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_DIRECTOR')]
class DirectorDashboardController extends DashboardController
{
    #[Route('/director', name: 'director_dashboard')]
    public function index(): Response
    {
        // return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        return $this->render('admin/admin_dashboard.html.twig');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-user', User::class)
            ->setController(UserCrudController::class);
        yield MenuItem::linkToCrud('Caractéristiques', 'fas fa-cogs', Feature::class)
            ->setController(FeatureCrudController::class);
        yield MenuItem::linkToCrud('Propriétés', 'fas fa-home', Property::class)
            ->setController(PropertyCrudController::class);
        yield MenuItem::linkToCrud('Demandes', 'fas fa-question-circle', Inquiry::class)
            ->setController(InquiryCrudController::class);
        yield MenuItem::linkToCrud('Rendez-vous', 'fas fa-calendar', Appointment::class)
            ->setController(AppointmentCrudController::class);
        yield MenuItem::linkToCrud('Notifications', 'fas fa-bell', Notification::class)
            ->setController(NotificationCrudController::class);
    }
}
