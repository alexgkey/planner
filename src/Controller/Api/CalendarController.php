<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class CalendarController extends AbstractController
{
    #[Route('/events', name: 'api_events', methods: ['GET'])]
    #[IsGranted('ROLE_CALENDAR_VIEWER')]
    public function events(EventRepository $eventRepository, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $events = $eventRepository->findBy(['isActive' => true]);
        $data = [];

        foreach ($events as $event) {
            if (!$event->getDate()) {
                continue;
            }

            $department = $event->getDepartment();
            $color = $department ? $department->getColor() : '#3788d8'; // Цвет по умолчанию, если у отдела нет цвета

            $data[] = [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'start' => $event->getDate()->format('Y-m-d'),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff', // Гарантируем белый цвет текста
                'extendedProps' => [
                    'department' => $department ? $department->getTitle() : null,
                ]
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/event/{id}/details', name: 'api_event_details', methods: ['GET'])]
    #[IsGranted('ROLE_CALENDAR_VIEWER')]
    public function eventDetails(Event $event): JsonResponse
    {
        $html = $this->renderView('event/_modal_content.html.twig', [
            'event' => $event
        ]);

        return new JsonResponse(['html' => $html]);
    }
}
