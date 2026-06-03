<?php

namespace App\Controller;

use App\Entity\EventReportPublication;
use App\Repository\EventReportPublicationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RssFeedController extends AbstractController
{
    #[Route('/rss/reports.xml', name: 'app_rss_reports', methods: ['GET'])]
    public function reports(EventReportPublicationRepository $publicationRepository): Response
    {
        $publications = $publicationRepository->findPublishedForPlatform(EventReportPublication::PLATFORM_RSS);

        $response = $this->render('rss/reports.xml.twig', [
            'publications' => $publications,
        ]);
        $response->headers->set('Content-Type', 'application/rss+xml; charset=UTF-8');

        return $response;
    }
}
