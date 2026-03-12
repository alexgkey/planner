<?php

namespace App\Controller;

use App\Entity\Department;
use App\Entity\Event;
use App\Entity\Enum\EventStatus;
use App\Form\EventType;
use App\Repository\DepartmentRepository;
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
    public function index(Request $request, EventRepository $eventRepository, DepartmentRepository $departmentRepository): Response
    {
        $employee = $this->getUser()?->getEmployee();
        $department = $employee?->getDepartment();
        $canViewAny = $this->isGranted(AppPermissions::EVENT_VIEW_ANY)
            || $this->isGranted(AppPermissions::EVENT_MANAGE_ANY)
            || $this->isGranted(AppPermissions::EVENT_ADMIN);

        $events = [];
        $departmentOptions = [];
        $monthOptions = [];
        $selectedDepartmentIds = [];
        $selectedMonths = [];

        if ($canViewAny) {
            $events = $eventRepository->findActiveByDepartment();
            $departmentOptions = $departmentRepository->findActive();
            $monthOptions = $this->buildMonthOptions();

            $defaultDepartmentIds = array_map(
                static fn (Department $department): int => $department->getId(),
                $departmentOptions
            );
            $defaultMonths = array_keys($monthOptions);

            $selectedDepartmentIds = $request->query->all('departments');
            $selectedDepartmentIds = array_map('intval', is_array($selectedDepartmentIds) ? $selectedDepartmentIds : []);
            $selectedDepartmentIds = array_values(array_intersect($selectedDepartmentIds, $defaultDepartmentIds));
            if ([] === $selectedDepartmentIds) {
                $selectedDepartmentIds = $defaultDepartmentIds;
            }

            $selectedMonths = $request->query->all('months');
            $selectedMonths = is_array($selectedMonths) ? array_values(array_intersect($selectedMonths, $defaultMonths)) : [];
            if ([] === $selectedMonths) {
                $selectedMonths = $defaultMonths;
            }

            $events = array_values(array_filter($events, function (Event $event) use ($selectedDepartmentIds, $selectedMonths): bool {
                $eventDepartmentId = $event->getDepartment()?->getId();
                if (null !== $eventDepartmentId && !in_array($eventDepartmentId, $selectedDepartmentIds, true)) {
                    return false;
                }

                $eventDate = $event->getDate();
                if (null === $eventDate) {
                    return true;
                }

                return in_array($eventDate->format('Y-m'), $selectedMonths, true);
            }));
        } elseif ($department) {
            $events = $eventRepository->findActiveByDepartment($department);
        }

        return $this->render('event/index.html.twig', [
            'events' => $events,
            'can_filter_events' => $canViewAny,
            'department_options' => $departmentOptions,
            'month_options' => $monthOptions,
            'selected_department_ids' => $selectedDepartmentIds,
            'selected_months' => $selectedMonths,
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
        $this->denyAccessUnlessGranted(AppPermissions::EVENT_VIEW, $event);

        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
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
        if (!$this->isGranted(AppPermissions::EVENT_MANAGE_ANY, $event)) {
            $this->denyAccessUnlessGranted(AppPermissions::EVENT_MANAGE_OWN, $event);
        }

        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->getPayload()->getString('_token'))) {
            $event->setIsActive(false);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Показываем все месяцы текущего года с января.
     * Если до конца года осталось меньше трех месяцев, добавляем первые месяцы следующего года.
     *
     * @return array<string, string>
     */
    private function buildMonthOptions(): array
    {
        $options = [];
        $today = new \DateTimeImmutable('today');
        $currentYear = (int) $today->format('Y');
        $currentMonth = (int) $today->format('n');
        $remainingMonths = 12 - $currentMonth + 1;
        $nextYearMonthsToShow = max(0, 3 - $remainingMonths);

        for ($month = 1; $month <= 12; $month++) {
            $date = new \DateTimeImmutable(sprintf('%d-%02d-01', $currentYear, $month));
            $options[$date->format('Y-m')] = $date->format('F Y');
        }

        for ($month = 1; $month <= $nextYearMonthsToShow; $month++) {
            $date = new \DateTimeImmutable(sprintf('%d-%02d-01', $currentYear + 1, $month));
            $options[$date->format('Y-m')] = $date->format('F Y');
        }

        return $options;
    }
}