<?php

namespace App\Form;

use App\Entity\EmployeeAchievement;
use App\Entity\Enum\AchievementStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class EmployeeAchievementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentYear = (int) date('Y');

        $builder
            ->add('title', TextType::class, [
                'label' => 'Название',
            ])
            ->add('status', EnumType::class, [
                'class' => AchievementStatus::class,
                'label' => 'Статус',
                'choice_label' => static fn (AchievementStatus $choice) => $choice->getLabel(),
            ])
            ->add('year', IntegerType::class, [
                'label' => 'Год',
                'attr' => [
                    'min' => 1960,
                    'max' => $currentYear,
                    'inputmode' => 'numeric',
                ],
                'constraints' => [
                    new Range(
                        min: 1960,
                        max: $currentYear,
                        notInRangeMessage: sprintf('Год должен быть в диапазоне от 1960 до %d.', $currentYear)
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmployeeAchievement::class,
        ]);
    }
}