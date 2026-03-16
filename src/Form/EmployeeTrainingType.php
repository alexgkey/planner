<?php

namespace App\Form;

use App\Entity\EmployeeTraining;
use App\Entity\Enum\TrainingType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class EmployeeTrainingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentYear = (int) date('Y');

        $builder
            ->add('type', EnumType::class, [
                'class' => TrainingType::class,
                'label' => 'Тип',
                'choice_label' => static fn (TrainingType $choice) => $choice->getLabel(),
            ])
            ->add('institution', TextType::class, [
                'label' => 'Учреждение',
            ])
            ->add('year', IntegerType::class, [
                'label' => 'Год прохождения',
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
            ])
            ->add('hours', IntegerType::class, [
                'label' => 'Количество часов',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmployeeTraining::class,
        ]);
    }
}