<?php

namespace App\Controller;

use App\Entity\Department;
use App\Form\DepartmentType;
use App\Repository\DepartmentRepository;
use App\Security\Permissions\AppPermissions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/departments')]
#[IsGranted(AppPermissions::DEPARTMENT_VIEW)]
final class DepartmentController extends AbstractController
{
    #[Route(name: 'app_department_index', methods: ['GET'])]
    public function index(DepartmentRepository $departmentRepository): Response
    {
        $employeeDepartment = $this->getUser()?->getEmployee()?->getDepartment();

        // По умолчанию пользователь видит только свой отдел.
        // Полный список доступен тем, у кого есть право управлять всеми отделами или администрировать их.
        $departments = [];
        if ($this->isGranted(AppPermissions::DEPARTMENT_ADMIN) || $this->isGranted(AppPermissions::DEPARTMENT_MANAGE_ANY)) {
            $departments = $departmentRepository->findActive();
        } elseif (null !== $employeeDepartment && $employeeDepartment->isActive()) {
            $departments = [$employeeDepartment];
        }

        return $this->render('department/index.html.twig', [
            'departments' => $departments,
            'show_all_departments' => $this->isGranted(AppPermissions::DEPARTMENT_ADMIN) || $this->isGranted(AppPermissions::DEPARTMENT_MANAGE_ANY),
        ]);
    }

    #[Route('/new', name: 'app_department_new', methods: ['GET', 'POST'])]
    #[IsGranted(AppPermissions::DEPARTMENT_ADMIN)]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $department = new Department();
        $form = $this->createForm(DepartmentType::class, $department);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($department);
            $entityManager->flush();

            return $this->redirectToRoute('app_department_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('department/new.html.twig', [
            'department' => $department,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_department_show', methods: ['GET'])]
    public function show(Department $department): Response
    {
        $this->denyAccessUnlessGranted(AppPermissions::DEPARTMENT_VIEW, $department);

        return $this->render('department/show.html.twig', [
            'department' => $department,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_department_edit', methods: ['GET', 'POST'])]
    #[IsGranted(AppPermissions::DEPARTMENT_ADMIN)]
    public function edit(Request $request, Department $department, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DepartmentType::class, $department);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_department_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('department/edit.html.twig', [
            'department' => $department,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_department_delete', methods: ['POST'])]
    #[IsGranted(AppPermissions::DEPARTMENT_ADMIN)]
    public function delete(Request $request, Department $department, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$department->getId(), $request->getPayload()->getString('_token'))) {
            $department->setIsActive(false);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_department_index', [], Response::HTTP_SEE_OTHER);
    }
}