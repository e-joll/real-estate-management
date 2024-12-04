<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class DashboardController extends AbstractDashboardController
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Gestion immobilière');
    }

    public function configureCrud(): Crud
    {
        return parent::configureCrud()/*->setTimezone($this->getUser()->getPreferredTimeZone())*/;
    }

    protected function setDashboardControllerFqcnIfNotSet(): void
    {
        // Retrieve the current request from the request stack
        $context = $this->requestStack->getCurrentRequest();
        // Check if the dashboard controller FQCN (Fully Qualified Class Name) is already set in the request attributes
        if (!$context->attributes->get(EA::DASHBOARD_CONTROLLER_FQCN)) {
            // If not set, define the dashboard controller FQCN as 'App\Controller\Admin\DirectorDashboardController'
            $context->attributes->set(EA::DASHBOARD_CONTROLLER_FQCN, get_class($this));
        }
    }
}
