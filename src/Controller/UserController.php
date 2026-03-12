<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
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
    public function index(UserRepository $userRepository): Response
    {
        $currentUser = $this->getUser();
        $users = [];

        if ($this->isGranted(AppPermissions::USER_ADMIN)) {
            // Админ видит всех
            $users = $userRepository->findAll();
        } elseif ($this->isGranted(AppPermissions::USER_VIEW_ALL)) {
            // Пользователь с правом VIEW_ALL видит всех в своем отделе
            $department = $currentUser->getEmployee()?->getDepartment();
            if ($department) {
                $users = $userRepository->findByDepartment($department);
            } else {
                $users = [$currentUser]; // Если у самого нет отдела, видит только себя
            }
        } elseif ($this->isGranted(AppPermissions::USER_VIEW)) {
            // Пользователь с базовым правом видит только себя
            $users = [$currentUser];
        }

        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    #[IsGranted(AppPermissions::USER_ADMIN)] // Только админ может создавать новых пользователей
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
        // Voter проверит, можно ли смотреть этого пользователя
        $this->denyAccessUnlessGranted(AppPermissions::USER_VIEW, $user);

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        // Voter проверит, можно ли редактировать этого пользователя
        if ($this->isGranted(AppPermissions::USER_MANAGE_ALL, $user)) {
            // Редактирование в рамках отдела
        } elseif ($this->isGranted(AppPermissions::USER_MANAGE_OWN, $user)) {
            // Редактирование своего профиля
        } else {
            $this->denyAccessUnlessGranted(AppPermissions::USER_ADMIN, $user); // Или админ
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
    #[IsGranted(AppPermissions::USER_ADMIN)] // Только админ может удалять
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $user->setIsActive(false);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
