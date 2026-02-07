<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Department;
use App\Form\DataTransformer\RolesTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraints\Regex;

class UserType extends AbstractType
{
    private array $roles;
    private RolesTransformer $rolesTransformer;
    private Security $security;

    public function __construct(array $userRoles, RolesTransformer $rolesTransformer, Security $security)
    {
        $this->roles = $userRoles;
        $this->rolesTransformer = $rolesTransformer;
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $options['data'];
        $isNewUser = $user->getId() === null;

        $availableRoles = [];
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $availableRoles = $this->roles;
        } elseif ($this->security->isGranted('ROLE_MANAGER')) {
            $availableRoles = ['Директор' => 'ROLE_DIR'];
        }

        $builder
            ->add('fio', TextType::class, [
                'label' => 'Ф.И.О.'
            ])
            ->add('email', null, [
                'label' => 'Email'
            ])
            ->add('phone', TelType::class, [
                'label' => 'Номер телефона',
                'constraints' => [
                    new Regex(
                        pattern: '/^89\d{9}$/',
                        message: 'Номер телефона должен быть в формате 89XXXXXXXXX (11 цифр)'
                    )
                ]
            ])
            ->add('department', EntityType::class, [
                'class' => Department::class,
                'choice_label' => 'title',
                'placeholder' => 'Выберите отдел',
                'label' => 'Отдел',
                'required' => false,
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Роль',
                'choices' => $availableRoles,
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'placeholder' => 'Без особой роли',
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Пароль',
                'mapped' => false,
                'required' => $isNewUser,
                'attr' => [
                    'placeholder' => $isNewUser ? 'Задайте пароль' : 'Оставьте пустым, чтобы не менять'
                ],
            ])
        ;

        $builder->get('roles')->addModelTransformer($this->rolesTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
