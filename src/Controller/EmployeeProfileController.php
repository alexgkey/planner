<?php

namespace App\Controller;

use App\Entity\Employee;
use App\Entity\EmployeeAchievement;
use App\Entity\EmployeeAward;
use App\Entity\EmployeeEducation;
use App\Entity\EmployeeTraining;
use App\Form\EmployeeAchievementType;
use App\Form\EmployeeAwardType;
use App\Form\EmployeeEducationType;
use App\Form\EmployeeTrainingType;
use App\Security\Voter\EmployeeVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/employees')]
class EmployeeProfileController extends AbstractController
{
    #[Route('/{id}/awards/new', name: 'app_employee_award_new', methods: ['GET', 'POST'])]
    public function newAward(Employee $employee, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(EmployeeVoter::EDIT, $employee);

        $award = (new EmployeeAward())->setEmployee($employee);
        $form = $this->createForm(EmployeeAwardType::class, $award);
        $form->handleRequest($request);

        if ($this->handleSubmittedForm($form, $entityManager, $award)) {
            return $this->redirectToRoute('app_employee_show', ['id' => $employee->getId()]);
        }

        return $this->renderProfileForm('employee/profile_form.html.twig', $form, 'Новая награда или звание', $employee);
    }

    #[Route('/awards/{id}/edit', name: 'app_employee_award_edit', methods: ['GET', 'POST'])]
    public function editAward(EmployeeAward $award, Request $request, EntityManagerInterface $entityManager): Response
    {
        $employee = $award->getEmployee();
        $this->denyAccessUnlessGranted(EmployeeVoter::EDIT, $employee);

        $form = $this->createForm(EmployeeAwardType::class, $award);
        $form->handleRequest($request);

        if ($this->handleSubmittedForm($form, $entityManager)) {
            return $this->redirectToRoute('app_employee_show', ['id' => $employee->getId()]);
        }

        return $this->renderProfileForm('employee/profile_form.html.twig', $form, 'Редактирование награды или звания', $employee);
    }

    #[Route('/awards/{id}', name: 'app_employee_award_delete', methods: ['POST'])]
    public function deleteAward(EmployeeAward $award, Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->deleteProfileItem($award, $award->getEmployee(), $request, $entityManager, 'award');
    }

    #[Route('/{id}/educations/new', name: 'app_employee_education_new', methods: ['GET', 'POST'])]
    public function newEducation(Employee $employee, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(EmployeeVoter::EDIT, $employee);

        $education = (new EmployeeEducation())->setEmployee($employee);
        $form = $this->createForm(EmployeeEducationType::class, $education);
        $form->handleRequest($request);

        if ($this->handleSubmittedForm($form, $entityManager, $education)) {
            return $this->redirectToRoute('app_employee_show', ['id' => $employee->getId()]);
        }

        return $this->renderProfileForm('employee/profile_form.html.twig', $form, 'Новое образование', $employee);
    }

    #[Route('/educations/{id}/edit', name: 'app_employee_education_edit', methods: ['GET', 'POST'])]
    public function editEducation(EmployeeEducation $education, Request $request, EntityManagerInterface $entityManager): Response
    {
        $employee = $education->getEmployee();
        $this->denyAccessUnlessGranted(EmployeeVoter::EDIT, $employee);

        $form = $this->createForm(EmployeeEducationType::class, $education);
        $form->handleRequest($request);

        if ($this->handleSubmittedForm($form, $entityManager)) {
            return $this->redirectToRoute('app_employee_show', ['id' => $employee->getId()]);
        }

        return $this->renderProfileForm('employee/profile_form.html.twig', $form, 'Редактирование образования', $employee);
    }

    #[Route('/educations/{id}', name: 'app_employee_education_delete', methods: ['POST'])]
    public function deleteEducation(EmployeeEducation $education, Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->deleteProfileItem($education, $education->getEmployee(), $request, $entityManager, 'education');
    }

    #[Route('/{id}/trainings/new', name: 'app_employee_training_new', methods: ['GET', 'POST'])]
    public function newTraining(Employee $employee, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(EmployeeVoter::EDIT, $employee);

        $training = (new EmployeeTraining())->setEmployee($employee);
        $form = $this->createForm(EmployeeTrainingType::class, $training);
        $form->handleRequest($request);

        if ($this->handleSubmittedForm($form, $entityManager, $training)) {
            return $this->redirectToRoute('app_employee_show', ['id' => $employee->getId()]);
        }

        return $this->renderProfileForm('employee/profile_form.html.twig', $form, 'Новый курс или профпереподготовка', $employee);
    }

    #[Route('/trainings/{id}/edit', name: 'app_employee_training_edit', methods: ['GET', 'POST'])]
    public function editTraining(EmployeeTraining $training, Request $request, EntityManagerInterface $entityManager): Response
    {
        $employee = $training->getEmployee();
        $this->denyAccessUnlessGranted(EmployeeVoter::EDIT, $employee);

        $form = $this->createForm(EmployeeTrainingType::class, $training);
        $form->handleRequest($request);

        if ($this->handleSubmittedForm($form, $entityManager)) {
            return $this->redirectToRoute('app_employee_show', ['id' => $employee->getId()]);
        }

        return $this->renderProfileForm('employee/profile_form.html.twig', $form, 'Редактирование курса или профпереподготовки', $employee);
    }

    #[Route('/trainings/{id}', name: 'app_employee_training_delete', methods: ['POST'])]
    public function deleteTraining(EmployeeTraining $training, Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->deleteProfileItem($training, $training->getEmployee(), $request, $entityManager, 'training');
    }

    #[Route('/{id}/achievements/new', name: 'app_employee_achievement_new', methods: ['GET', 'POST'])]
    public function newAchievement(Employee $employee, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(EmployeeVoter::EDIT, $employee);

        $achievement = (new EmployeeAchievement())->setEmployee($employee);
        $form = $this->createForm(EmployeeAchievementType::class, $achievement);
        $form->handleRequest($request);

        if ($this->handleSubmittedForm($form, $entityManager, $achievement)) {
            return $this->redirectToRoute('app_employee_show', ['id' => $employee->getId()]);
        }

        return $this->renderProfileForm('employee/profile_form.html.twig', $form, 'Новое достижение', $employee);
    }

    #[Route('/achievements/{id}/edit', name: 'app_employee_achievement_edit', methods: ['GET', 'POST'])]
    public function editAchievement(EmployeeAchievement $achievement, Request $request, EntityManagerInterface $entityManager): Response
    {
        $employee = $achievement->getEmployee();
        $this->denyAccessUnlessGranted(EmployeeVoter::EDIT, $employee);

        $form = $this->createForm(EmployeeAchievementType::class, $achievement);
        $form->handleRequest($request);

        if ($this->handleSubmittedForm($form, $entityManager)) {
            return $this->redirectToRoute('app_employee_show', ['id' => $employee->getId()]);
        }

        return $this->renderProfileForm('employee/profile_form.html.twig', $form, 'Редактирование достижения', $employee);
    }

    #[Route('/achievements/{id}', name: 'app_employee_achievement_delete', methods: ['POST'])]
    public function deleteAchievement(EmployeeAchievement $achievement, Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->deleteProfileItem($achievement, $achievement->getEmployee(), $request, $entityManager, 'achievement');
    }

    private function handleSubmittedForm(FormInterface $form, EntityManagerInterface $entityManager, ?object $entity = null): bool
    {
        if (!$form->isSubmitted() || !$form->isValid()) {
            return false;
        }

        if (null !== $entity) {
            $entityManager->persist($entity);
        }

        $entityManager->flush();

        return true;
    }

    private function renderProfileForm(string $template, FormInterface $form, string $title, Employee $employee): Response
    {
        return $this->render($template, [
            'form' => $form,
            'page_title' => $title,
            'employee' => $employee,
        ]);
    }

    private function deleteProfileItem(object $entity, Employee $employee, Request $request, EntityManagerInterface $entityManager, string $tokenPrefix): Response
    {
        $this->denyAccessUnlessGranted(EmployeeVoter::EDIT, $employee);

        $entityId = method_exists($entity, 'getId') ? $entity->getId() : null;
        $token = $request->getPayload()->getString('_token');
        if (null !== $entityId && $this->isCsrfTokenValid($tokenPrefix.$entityId, $token)) {
            $entityManager->remove($entity);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_employee_show', ['id' => $employee->getId()]);
    }
}
