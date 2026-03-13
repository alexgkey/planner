<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\Enum\EventStatus;
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
            $startsAt = $event->getStartsAt();
            if (null === $startsAt) {
                continue;
            }

            $department = $event->getDepartment();
            $color = $department ? $department->getColor() : '#3788d8';
            $hasTime = null !== $event->getTime();

            $data[] = [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'start' => $hasTime ? $startsAt->format('Y-m-d\TH:i:s') : $startsAt->format('Y-m-d'),
                'allDay' => !$hasTime,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'url' => $urlGenerator->generate('app_event_show', ['id' => $event->getId()]),
                'extendedProps' => [
                    'department' => $department ? $department->getTitle() : null,
                    'isCompleted' => $event->getStatus() === EventStatus::COMPLETED,
                    'isCancelled' => $event->getStatus() === EventStatus::CANCELLED,
                ],
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/event/{id}/details', name: 'api_event_details', methods: ['GET'])]
    #[IsGranted('ROLE_CALENDAR_VIEWER')]
    public function eventDetails(Event $event): JsonResponse
    {
        $html = $this->renderView('event/_modal_content.html.twig', [
            'event' => $event,
        ]);

        return new JsonResponse(['html' => $html]);
    }
}