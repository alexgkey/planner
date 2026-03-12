<?php

namespace App\Form;

use App\Entity\Department;
use App\Entity\Employee;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

class EmployeeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['include_fio']) {
            $builder->add('fio', TextType::class, [
                'label' => 'Ф.И.О.',
            ]);
        }

        $builder->add('phone', TelType::class, [
            'label' => 'Номер телефона',
            'required' => false,
            'constraints' => [
                new Regex(
                    pattern: '/^89\d{9}$/',
                    message: 'Номер телефона должен быть в формате 89XXXXXXXXX (11 цифр)'
                )
            ]
        ]);

        if ($options['include_department']) {
            $departmentFieldOptions = [
                'class' => Department::class,
                'choice_label' => 'title',
                'placeholder' => 'Выберите отдел',
                'label' => 'Отдел',
                'required' => true,
            ];

            if (null !== $options['department_choices']) {
                $departmentFieldOptions['choices'] = $options['department_choices'];
                $departmentFieldOptions['placeholder'] = false;
            }

            $builder->add('department', EntityType::class, $departmentFieldOptions);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Employee::class,
            'include_fio' => true,
            'include_department' => true,
            'department_choices' => null,
        ]);

        $resolver->setAllowedTypes('include_fio', 'bool');
        $resolver->setAllowedTypes('include_department', 'bool');
        $resolver->setAllowedTypes('department_choices', ['null', 'array']);
    }
}