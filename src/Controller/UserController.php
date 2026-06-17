<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\DepartmentRepository;
use App\Repository\UserRepository;
use App\Security\Permissions\AppPermissions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/users')]
#[IsGranted(AppPermissions::USER_VIEW)]
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(
        Request $request,
        UserRepository $userRepository,
        DepartmentRepository $departmentRepository
    ): Response {
        $currentUser = $this->getUser();
        $users = [];
        $sortField = $request->query->getString('sort_field', UserRepository::SORT_FIELD_FIO);
        $sortDirection = $request->query->getString('sort_direction', 'ASC');
        $fioFilter = trim($request->query->getString('fio_filter', ''));
        $departmentFilter = $request->query->get('department_filter');
        $departmentFilterId = is_scalar($departmentFilter) && '' !== trim((string) $departmentFilter)
            ? (int) $departmentFilter
            : null;
        $availableDepartments = [];

        if ($this->isGranted(AppPermissions::USER_ADMIN)) {
            $users = $userRepository->findForListing(
                null,
                '' !== $fioFilter ? $fioFilter : null,
                $departmentFilterId,
                $sortField,
                $sortDirection
            );
            $availableDepartments = $departmentRepository->findActive();
        } elseif ($this->isGranted(AppPermissions::USER_VIEW_ALL)) {
            $department = $currentUser?->getEmployee()?->getDepartment();
            if (null !== $department) {
                $users = $userRepository->findForListing(
                    $department,
                    '' !== $fioFilter ? $fioFilter : null,
                    $departmentFilterId,
                    $sortField,
                    $sortDirection
                );
                $availableDepartments = [$department];
            } elseif (null !== $currentUser) {
                $users = [$currentUser];
            }
        } elseif ($this->isGranted(AppPermissions::USER_VIEW) && null !== $currentUser) {
            $users = [$currentUser];
        }

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'available_departments' => $availableDepartments,
            'current_sort_field' => $sortField,
            'current_sort_direction' => 'DESC' === strtoupper($sortDirection) ? 'DESC' : 'ASC',
            'current_fio_filter' => $fioFilter,
            'current_department_filter' => $departmentFilterId,
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    #[IsGranted(AppPermissions::USER_ADMIN)]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('password')->getData();
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        $this->denyAccessUnlessGranted(AppPermissions::USER_VIEW, $user);

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isGranted(AppPermissions::USER_MANAGE_ALL, $user)) {
        } elseif ($this->isGranted(AppPermissions::USER_MANAGE_OWN, $user)) {
        } else {
            $this->denyAccessUnlessGranted(AppPermissions::USER_ADMIN, $user);
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('password')->getData();
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    #[IsGranted(AppPermissions::USER_ADMIN)]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $user->setIsActive(false);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
