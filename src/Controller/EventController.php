<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Enum\EventStatus;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Security\Permissions\AppPermissions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/events')]
#[IsGranted(AppPermissions::EVENT_VIEW)]
class EventController extends AbstractController
{
    #[Route(name: 'app_event_index', methods: ['GET'])]
    public function index(EventRepository $eventRepository): Response
    {
        $employee = $this->getUser()?->getEmployee();
        $department = $employee?->getDepartment();

        // Пользователь с правом просмотра всех мероприятий видит общий список.
        // Остальные пользователи видят только мероприятия своего отдела.
        $events = [];
        if ($this->isGranted(AppPermissions::EVENT_ADMIN) || $this->isGranted(AppPermissions::EVENT_VIEW_ANY)) {
            $events = $eventRepository->findActiveByDepartment();
        } elseif ($department) {
            $events = $eventRepository->findActiveByDepartment($department);
        }

        return $this->render('event/index.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    #[IsGranted(AppPermissions::EVENT_MANAGE_OWN)]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $department = $user?->getEmployee()?->getDepartment();
        if (null === $department) {
            throw $this->createAccessDeniedException('User department is required to create events.');
        }

        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Отдел и создатель берутся из текущего пользователя,
            // чтобы нельзя было создать мероприятие от имени другого отдела.
            $event->setCreator($user);
            $event->setDepartment($department);
            $entityManager->persist($event);
            $entityManager->flush();

            return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/new.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_event_show', methods: ['GET'])]
    public function show(Event $event): Response
    {
        // Здесь важна объектная проверка: даже если пользователь знает URL,
        // voter отдельно решит, можно ли ему видеть именно это мероприятие.
        $this->denyAccessUnlessGranted(AppPermissions::EVENT_VIEW, $event);

        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        // Сначала проверяем более широкое право на управление всеми мероприятиями.
        // Если его нет, тогда проверяем право на управление только своим отделом.
        if (!$this->isGranted(AppPermissions::EVENT_MANAGE_ANY, $event)) {
            $this->denyAccessUnlessGranted(AppPermissions::EVENT_MANAGE_OWN, $event);
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/edit.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/cancel', name: 'app_event_cancel', methods: ['POST'])]
    public function cancel(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        // Отмена подчиняется тем же правилам доступа, что и редактирование.
        if (!$this->isGranted(AppPermissions::EVENT_MANAGE_ANY, $event)) {
            $this->denyAccessUnlessGranted(AppPermissions::EVENT_MANAGE_OWN, $event);
        }

        if ($this->isCsrfTokenValid('cancel'.$event->getId(), $request->getPayload()->getString('_token'))) {
            $event->setStatus(EventStatus::CANCELLED);
            $entityManager->flush();
            $this->addFlash('warning', 'Мероприятие было отменено.');
        }

        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }

    #[Route('/{id}/restore', name: 'app_event_restore', methods: ['POST'])]
    #[IsGranted(AppPermissions::EVENT_ADMIN)]
    public function restore(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        // Восстановление отмененного мероприятия доступно только администратору мероприятий.
        if ($this->isCsrfTokenValid('restore'.$event->getId(), $request->getPayload()->getString('_token'))) {
            $event->setStatus(EventStatus::PLANNED);
            $entityManager->flush();
            $this->addFlash('success', 'Мероприятие было восстановлено.');
        }

        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }

    #[Route('/{id}', name: 'app_event_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        // Удаление доступно по тем же правилам, что и редактирование.
        if (!$this->isGranted(AppPermissions::EVENT_MANAGE_ANY, $event)) {
            $this->denyAccessUnlessGranted(AppPermissions::EVENT_MANAGE_OWN, $event);
        }

        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->getPayload()->getString('_token'))) {
            // Запись не удаляется физически: мы просто скрываем ее из активных списков.
            $event->setIsActive(false);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
    }
}