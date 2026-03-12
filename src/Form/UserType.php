<?php

namespace App\Form;

use App\Entity\Employee;
use App\Entity\User;
use App\Security\Permissions\AppPermissions;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $options['data'];
        $isNewUser = $user->getId() === null;

        $builder
            ->add('employee', EntityType::class, [
                'class' => Employee::class,
                'choice_label' => 'fio',
                'placeholder' => 'Выберите сотрудника',
                'label' => 'Сотрудник',
                'required' => true,
                'disabled' => !$isNewUser,
            ])
            ->add('email', null, [
                'label' => 'Email',
            ]);

        if ($this->security->isGranted(AppPermissions::USER_ADMIN)) {
            $builder->add('permissions', ChoiceType::class, [
                'label' => 'Разрешения',
                'choices' => [
                    'Мероприятия' => [
                        'Мероприятия Просмотр (свой отдел)' => AppPermissions::EVENT_VIEW,
                        'Мероприятия Просмотр (все отделы)' => AppPermissions::EVENT_VIEW_ANY,
                        'Мероприятия Управление (свой отдел)' => AppPermissions::EVENT_MANAGE_OWN,
                        'Мероприятия Управление (все отделы)' => AppPermissions::EVENT_MANAGE_ANY,
                        'Мероприятия Администрирование' => AppPermissions::EVENT_ADMIN,
                    ],
                    'Подразделения' => [
                        'Подразделения Просмотр' => AppPermissions::DEPARTMENT_VIEW,
                        'Подразделения Управление (свой отдел)' => AppPermissions::DEPARTMENT_MANAGE_OWN,
                        'Подразделения Управление (все отделы)' => AppPermissions::DEPARTMENT_MANAGE_ANY,
                        'Подразделения Администрирование' => AppPermissions::DEPARTMENT_ADMIN,
                    ],
                    'Пользователи' => [
                        'Пользователи Просмотр (раздел)' => AppPermissions::USER_VIEW,
                        'Пользователи Просмотр (свой отдел)' => AppPermissions::USER_VIEW_ALL,
                        'Пользователи Редактирование (свой профиль)' => AppPermissions::USER_MANAGE_OWN,
                        'Пользователи Редактирование (свой отдел)' => AppPermissions::USER_MANAGE_ALL,
                        'Пользователи Администрирование' => AppPermissions::USER_ADMIN,
                    ],
                    'Сотрудники' => [
                        'Сотрудники Просмотр (только своя карточка)' => AppPermissions::EMPLOYEE_VIEW,
                        'Сотрудники Просмотр (свой отдел)' => AppPermissions::EMPLOYEE_VIEW_DEPARTMENT,
                        'Сотрудники Просмотр (все отделы)' => AppPermissions::EMPLOYEE_VIEW_ANY,
                        'Сотрудники Редактирование (своя карточка)' => AppPermissions::EMPLOYEE_MANAGE_OWN,
                        'Сотрудники Управление (свой отдел)' => AppPermissions::EMPLOYEE_MANAGE_DEPARTMENT,
                        'Сотрудники Управление (все отделы)' => AppPermissions::EMPLOYEE_MANAGE_ANY,
                        'Сотрудники Администрирование' => AppPermissions::EMPLOYEE_ADMIN,
                    ],
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ]);
        }

        $builder->add('password', PasswordType::class, [
            'label' => 'Пароль',
            'mapped' => false,
            'required' => $isNewUser,
            'attr' => [
                'placeholder' => $isNewUser ? 'Задайте пароль' : 'Оставьте пустым, чтобы не менять',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}