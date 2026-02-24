<?php

namespace App\Form;

use App\Entity\EventReport;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class EventReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('audienceType', ChoiceType::class, [
                'label' => 'Тип аудитории',
                'choices' => [
                    'По возрастным категориям' => 'by_age',
                    'Смешанная аудитория' => 'mixed',
                ],
                'expanded' => true,
                'multiple' => false,
                'mapped' => false,
                'data' => 'by_age',
                'label_attr' => ['class' => 'd-block'],
                'attr' => ['class' => 'd-none'],
            ])
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
            ->add('mixedAudienceCount', IntegerType::class, [
                'label' => 'Посетители (смешанная аудитория)',
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
                'label' => 'Отчет по мероприятию (для Госпабликов)',
                'required' => false,
                'attr' => ['rows' => 6],
            ])
            ->add('scenarioFile', VichFileType::class, [
                'label' => 'Сценарий (файл)',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
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
