<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class InstructionController extends AbstractController
{
    #[Route('/instructions', name: 'app_instructions')]
    public function index(): Response
    {
        return $this->render('instruction/index.html.twig');
    }
}
