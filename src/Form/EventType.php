<?php

namespace App\Form;

use App\Entity\Department;
use App\Entity\Enum\EventAccessibility;
use App\Entity\Enum\EventDirection;
use App\Entity\Enum\EventLevel;
use App\Entity\Enum\OnOffLine;
use App\Entity\Enum\TargetAudience;
use App\Entity\Event;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
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
            ->add('title', null, [
                'label' => 'Название мероприятия'
            ])
            ->add('venue', null, [
                'label' => 'Место проведения'
            ])
            ->add('eventLevel', EnumType::class, [
                'class' => EventLevel::class,
                'label' => 'Уровень',
                'required' => true,
                'placeholder' => 'Выберите уровень',
                'choice_label' => fn(EventLevel $choice) => $choice->getLabel(),
            ])
            ->add('onOffLine', EnumType::class, [
                'class' => OnOffLine::class,
                'label' => 'Формат',
                'required' => true,
                'placeholder' => 'Выберите формат',
                'choice_label' => fn(OnOffLine $choice) => $choice->getLabel(),
            ])
            ->add('eventDirection', EnumType::class, [
                'class' => EventDirection::class,
                'label' => 'Направление',
                'required' => true,
                'placeholder' => 'Выберите направление',
                'choice_label' => fn(EventDirection $choice) => $choice->getLabel(),
            ])
            ->add('eventAccessibility', EnumType::class, [
                'class' => EventAccessibility::class,
                'label' => 'Доступность',
                'required' => true,
                'placeholder' => 'Выберите доступность',
                'choice_label' => fn(EventAccessibility $choice) => $choice->getLabel(),
            ])
            ->add('targetAudience', EnumType::class, [
                'class' => TargetAudience::class,
                'label' => 'Целевая аудитория',
                'required' => true,
                'placeholder' => 'Выберите аудиторию',
                'choice_label' => fn(TargetAudience $choice) => $choice->getLabel(),
            ])
            ->add('interaction', null, [
                'label' => 'Взаимодействие с организацией',
                'required' => false,
            ])
            ->add('note', null, [
                'label' => 'Примечание',
                'required' => false,
            ])
            ->add('responsible', null, [
                'label' => 'Ответственный',
                'required' => true,
            ])
            ->add('planned_visitors', null, [
                'label' => 'Посетители (план)',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
