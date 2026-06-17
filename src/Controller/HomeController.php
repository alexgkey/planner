<?php

namespace App\Controller;

use App\Security\Permissions\AppPermissions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;

#[Route('/')]
final class HomeController extends AbstractController
{
    #[Route(name: 'app_home')]
    public function index(): RedirectResponse
    {
        if ($this->getUser()) {
            return match (true) {
                $this->isGranted(AppPermissions::EVENT_VIEW) => $this->redirectToRoute('app_event_index'),
                $this->isGranted(AppPermissions::TIMESHEET_VIEW) => $this->redirectToRoute('app_timesheet_index'),
                $this->isGranted(AppPermissions::DEPARTMENT_VIEW) => $this->redirectToRoute('app_department_index'),
                $this->isGranted(AppPermissions::EMPLOYEE_VIEW) => $this->redirectToRoute('app_employee_index'),
                $this->isGranted(AppPermissions::USER_VIEW) => $this->redirectToRoute('app_user_index'),
                default => $this->redirectToRoute('app_login'),
            };
        }

        return $this->redirectToRoute('app_login');
    }
}
