<?php

namespace App\Controller\Api;

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
    #[IsGranted('ROLE_MANAGER')]
    public function events(EventRepository $eventRepository, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        // Извлекаем только активные мероприятия
        $events = $eventRepository->findBy(['isActive' => true]);
        $data = [];

        foreach ($events as $event) {
            // Пропускаем события без даты
            if (!$event->getDate()) {
                continue;
            }

            $data[] = [
                'title' => $event->getTitle(),
                'start' => $event->getDate()->format('Y-m-d'),
                // Мы можем добавить и время, если оно у вас появится
                // 'end' => $event->getEndDate()->format('Y-m-d H:i:s'),
                'url' => $urlGenerator->generate('app_event_show', ['id' => $event->getId()]),
                // Дополнительные данные, которые можно использовать на фронтенде
                'extendedProps' => [
                    'department' => $event->getDepartment() ? $event->getDepartment()->getTitle() : null,
                    'venue' => $event->getVenue(),
                ]
            ];
        }

        return new JsonResponse($data);
    }
}
