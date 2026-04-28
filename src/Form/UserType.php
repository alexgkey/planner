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
                        'Мероприятия: просмотр своего отдела' => AppPermissions::EVENT_VIEW,
                        'Мероприятия: просмотр всех отделов' => AppPermissions::EVENT_VIEW_ANY,
                        'Мероприятия: управление своим отделом' => AppPermissions::EVENT_MANAGE_OWN,
                        'Мероприятия: управление всеми отделами' => AppPermissions::EVENT_MANAGE_ANY,
                        'Мероприятия: администрирование' => AppPermissions::EVENT_ADMIN,
                    ],
                    'Подразделения' => [
                        'Подразделения: просмотр' => AppPermissions::DEPARTMENT_VIEW,
                        'Подразделения: управление своим отделом' => AppPermissions::DEPARTMENT_MANAGE_OWN,
                        'Подразделения: управление всеми отделами' => AppPermissions::DEPARTMENT_MANAGE_ANY,
                        'Подразделения: администрирование' => AppPermissions::DEPARTMENT_ADMIN,
                    ],
                    'Пользователи' => [
                        'Пользователи: просмотр раздела' => AppPermissions::USER_VIEW,
                        'Пользователи: просмотр своего отдела' => AppPermissions::USER_VIEW_ALL,
                        'Пользователи: редактирование своего профиля' => AppPermissions::USER_MANAGE_OWN,
                        'Пользователи: управление своим отделом' => AppPermissions::USER_MANAGE_ALL,
                        'Пользователи: администрирование' => AppPermissions::USER_ADMIN,
                    ],
                    'Сотрудники' => [
                        'Сотрудники: просмотр своей карточки' => AppPermissions::EMPLOYEE_VIEW,
                        'Сотрудники: просмотр своего отдела' => AppPermissions::EMPLOYEE_VIEW_DEPARTMENT,
                        'Сотрудники: просмотр всех отделов' => AppPermissions::EMPLOYEE_VIEW_ANY,
                        'Сотрудники: редактирование своей карточки' => AppPermissions::EMPLOYEE_MANAGE_OWN,
                        'Сотрудники: управление своим отделом' => AppPermissions::EMPLOYEE_MANAGE_DEPARTMENT,
                        'Сотрудники: управление всеми отделами' => AppPermissions::EMPLOYEE_MANAGE_ANY,
                        'Сотрудники: администрирование' => AppPermissions::EMPLOYEE_ADMIN,
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
