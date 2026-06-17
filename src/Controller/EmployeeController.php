<?php

namespace App\Controller;

use App\Entity\Employee;
use App\Form\EmployeeType;
use App\Repository\DepartmentRepository;
use App\Repository\EmployeeRepository;
use App\Security\Permissions\AppPermissions;
use App\Security\Voter\EmployeeVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employees')]
#[IsGranted(AppPermissions::EMPLOYEE_VIEW)]
class EmployeeController extends AbstractController
{
    #[Route('/', name: 'app_employee_index', methods: ['GET'])]
    public function index(
        Request $request,
        EmployeeRepository $employeeRepository,
        DepartmentRepository $departmentRepository
    ): Response
    {
        $currentEmployee = $this->getUser()?->getEmployee();
        $currentDepartment = $currentEmployee?->getDepartment();
        $sortField = $request->query->getString('sort_field', EmployeeRepository::SORT_FIELD_FIO);
        $sortDirection = $request->query->getString('sort_direction', 'ASC');
        $fioFilter = trim($request->query->getString('fio_filter', ''));
        $departmentFilter = $request->query->get('department_filter');
        $departmentFilterId = is_scalar($departmentFilter) && '' !== trim((string) $departmentFilter)
            ? (int) $departmentFilter
            : null;
        $availableDepartments = [];

        $employees = [];
        if ($this->isGranted(AppPermissions::EMPLOYEE_ADMIN) || $this->isGranted(AppPermissions::EMPLOYEE_VIEW_ANY) || $this->isGranted(AppPermissions::EMPLOYEE_MANAGE_ANY)) {
            $employees = $employeeRepository->findActiveForListing(
                null,
                '' !== $fioFilter ? $fioFilter : null,
                $departmentFilterId,
                $sortField,
                $sortDirection
            );
            $availableDepartments = $departmentRepository->findActive();
        } elseif ($this->isGranted(AppPermissions::EMPLOYEE_VIEW_DEPARTMENT) || $this->isGranted(AppPermissions::EMPLOYEE_MANAGE_DEPARTMENT)) {
            if (null !== $currentDepartment) {
                $employees = $employeeRepository->findActiveForListing(
                    $currentDepartment,
                    '' !== $fioFilter ? $fioFilter : null,
                    $departmentFilterId,
                    $sortField,
                    $sortDirection
                );
                $availableDepartments = [$currentDepartment];
            }
        } elseif (null !== $currentEmployee && $currentEmployee->isActive()) {
            $employees = [$currentEmployee];
        }

        return $this->render('employee/index.html.twig', [
            'employees' => $employees,
            'available_departments' => $availableDepartments,
            'current_sort_field' => $sortField,
            'current_sort_direction' => 'DESC' === strtoupper($sortDirection) ? 'DESC' : 'ASC',
            'current_fio_filter' => $fioFilter,
            'current_department_filter' => $departmentFilterId,
            'show_department_scope' => $this->isGranted(AppPermissions::EMPLOYEE_VIEW_DEPARTMENT)
                || $this->isGranted(AppPermissions::EMPLOYEE_VIEW_ANY)
                || $this->isGranted(AppPermissions::EMPLOYEE_MANAGE_DEPARTMENT)
                || $this->isGranted(AppPermissions::EMPLOYEE_MANAGE_ANY)
                || $this->isGranted(AppPermissions::EMPLOYEE_ADMIN),
            'show_all_scope' => $this->isGranted(AppPermissions::EMPLOYEE_VIEW_ANY)
                || $this->isGranted(AppPermissions::EMPLOYEE_MANAGE_ANY)
                || $this->isGranted(AppPermissions::EMPLOYEE_ADMIN),
        ]);
    }

    #[Route('/new', name: 'app_employee_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $currentDepartment = $this->getUser()?->getEmployee()?->getDepartment();
        $employee = new Employee();

        if ($this->isGranted(AppPermissions::EMPLOYEE_ADMIN) || $this->isGranted(AppPermissions::EMPLOYEE_MANAGE_ANY)) {
            $formOptions = [
                'include_fio' => true,
                'include_department' => true,
                'include_work_start_date' => true,
            ];
        } elseif ($this->isGranted(AppPermissions::EMPLOYEE_MANAGE_DEPARTMENT)) {
            if (null === $currentDepartment) {
                throw $this->createAccessDeniedException('Текущий отдел пользователя не определен.');
            }

            $formOptions = [
                'include_fio' => true,
                'include_department' => false,
                'include_work_start_date' => true,
            ];
        } else {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(EmployeeType::class, $employee, $formOptions);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isGranted(AppPermissions::EMPLOYEE_MANAGE_DEPARTMENT) && !$this->isGranted(AppPermissions::EMPLOYEE_MANAGE_ANY) && !$this->isGranted(AppPermissions::EMPLOYEE_ADMIN)) {
                $employee->setDepartment($currentDepartment);
            }

            $entityManager->persist($employee);
            $entityManager->flush();

            return $this->redirectToRoute('app_employee_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('employee/new.html.twig', [
            'employee' => $employee,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_employee_show', methods: ['GET'])]
    public function show(Employee $employee): Response
    {
        $this->denyAccessUnlessGranted(AppPermissions::EMPLOYEE_VIEW, $employee);

        return $this->render('employee/show.html.twig', [
            'employee' => $employee,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_employee_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Employee $employee, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(EmployeeVoter::EDIT, $employee);

        $isAdmin = $this->isGranted(AppPermissions::EMPLOYEE_ADMIN);
        $formOptions = [
            'include_fio' => $isAdmin,
            'include_department' => $isAdmin,
            'include_work_start_date' => $isAdmin,
        ];

        $form = $this->createForm(EmployeeType::class, $employee, $formOptions);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_employee_show', ['id' => $employee->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('employee/edit.html.twig', [
            'employee' => $employee,
            'form' => $form,
            'is_phone_only_edit' => !$isAdmin,
        ]);
    }

    #[Route('/{id}', name: 'app_employee_delete', methods: ['POST'])]
    public function delete(Request $request, Employee $employee, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(EmployeeVoter::DELETE, $employee);

        if ($this->isCsrfTokenValid('delete'.$employee->getId(), $request->getPayload()->getString('_token'))) {
            $employee->setIsActive(false);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_employee_index', [], Response::HTTP_SEE_OTHER);
    }
}
