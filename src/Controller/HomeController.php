<?php

namespace App\Controller;

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
            return $this->redirectToRoute('app_event_index');
        }

        return $this->redirectToRoute('app_login');
    }

}
