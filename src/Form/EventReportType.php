<?php

namespace App\Form;

use App\Entity\EventReport;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('visitorsCount', IntegerType::class, [
                'label' => 'Посетители/просмотры (чел.)',
                'required' => false,
            ])
            ->add('participantsCount', IntegerType::class, [
                'label' => 'Участники (чел.)',
                'required' => false,
            ])
            ->add('disabledVisitorsCount', IntegerType::class, [
                'label' => 'в т.ч. инвалиды и лица с ОВЗ',
                'required' => false,
            ])
            ->add('seniorsVisitorsCount', IntegerType::class, [
                'label' => 'в т.ч. пенсионеры',
                'required' => false,
            ])
            ->add('adultsVisitorsCount', IntegerType::class, [
                'label' => 'в т.ч. взрослые',
                'required' => false,
            ])
            ->add('youthVisitorsCount', IntegerType::class, [
                'label' => 'в т.ч. молодежь',
                'required' => false,
            ])
            ->add('childrenVisitorsCount', IntegerType::class, [
                'label' => 'в т.ч. дети',
                'required' => false,
            ])
            ->add('childrenAtRiskCount', IntegerType::class, [
                'label' => 'в т.ч. дети на различных видах учета',
                'required' => false,
            ])
            ->add('smoParticipantsCount', IntegerType::class, [
                'label' => 'в т.ч. участники СВО',
                'required' => false,
            ])
            ->add('smoFamiliesCount', IntegerType::class, [
                'label' => 'в т.ч. семьи участников СВО',
                'required' => false,
            ])
            ->add('youngFamiliesCount', IntegerType::class, [
                'label' => 'в т.ч. молодые семьи',
                'required' => false,
            ])
            ->add('volunteersCount', IntegerType::class, [
                'label' => 'Количество волонтеров (чел.)',
                'required' => false,
            ])
            ->add('publicReportText', TextareaType::class, [
                'label' => 'Статья по мероприятию (для Госпабликов)',
                'required' => false,
                'attr' => ['rows' => 6],
            ])
            ->add('photos', HiddenType::class, [
                'mapped' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventReport::class,
        ]);
    }
}
