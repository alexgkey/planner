<?php

namespace App\Form;

use App\Entity\Department;
use App\Entity\Enum\EventAccessibility;
use App\Entity\Enum\EventDirection;
use App\Entity\Enum\EventLevel;
use App\Entity\Enum\OnOffLine;
use App\Entity\Enum\TargetAudience;
use App\Entity\Event;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', null, [
                'label' => 'Дата проведения',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('time', TimeType::class, [
                'label' => 'Время',
                'widget' => 'single_text',
                'input' => 'datetime',
                'required' => false,
                'html5' => true,
                'with_seconds' => false,
            ])
            ->add('title', TextType::class, [
                'label' => 'Название мероприятия',
            ])
            ->add('venue', TextType::class, [
                'label' => 'Место проведения',
                'required' => false,
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

        $builder
            ->add('eventLevel', EnumType::class, [
                'class' => EventLevel::class,
                'label' => 'Уровень',
                'required' => true,
                'placeholder' => 'Выберите уровень',
                'choice_label' => static fn (EventLevel $choice) => $choice->getLabel(),
            ])
            ->add('onOffLine', EnumType::class, [
                'class' => OnOffLine::class,
                'label' => 'Формат',
                'required' => true,
                'placeholder' => 'Выберите формат',
                'choice_label' => static fn (OnOffLine $choice) => $choice->getLabel(),
            ])
            ->add('eventDirection', EnumType::class, [
                'class' => EventDirection::class,
                'label' => 'Направление',
                'required' => true,
                'placeholder' => 'Выберите направление',
                'choice_label' => static fn (EventDirection $choice) => $choice->getLabel(),
            ])
            ->add('eventAccessibility', EnumType::class, [
                'class' => EventAccessibility::class,
                'label' => 'Доступность',
                'required' => true,
                'placeholder' => 'Выберите доступность',
                'choice_label' => static fn (EventAccessibility $choice) => $choice->getLabel(),
            ])
            ->add('targetAudience', EnumType::class, [
                'class' => TargetAudience::class,
                'label' => 'Целевая аудитория',
                'required' => true,
                'placeholder' => 'Выберите аудиторию',
                'choice_label' => static fn (TargetAudience $choice) => $choice->getLabel(),
            ])
            ->add('interaction', TextType::class, [
                'label' => 'Взаимодействие с организацией',
                'required' => false,
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Примечание',
                'required' => false,
            ])
            ->add('responsible', TextType::class, [
                'label' => 'Ответственный',
                'required' => true,
            ])
            ->add('planned_visitors', IntegerType::class, [
                'label' => 'Посетители (план)',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
            'include_department' => false,
            'department_choices' => null,
        ]);

        $resolver->setAllowedTypes('include_department', 'bool');
        $resolver->setAllowedTypes('department_choices', ['null', 'array']);
    }
}
