<?php

namespace App\Form;

use App\Entity\EmployeeEducation;
use App\Entity\Enum\EducationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class EmployeeEducationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentYear = (int) date('Y');

        $builder
            ->add('type', EnumType::class, [
                'class' => EducationType::class,
                'label' => 'Тип документа',
                'choice_label' => static fn (EducationType $choice) => $choice->getLabel(),
            ])
            ->add('institution', TextType::class, [
                'label' => 'Учебное заведение',
            ])
            ->add('specialty', TextType::class, [
                'label' => 'Специальность',
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
            'data_class' => EmployeeEducation::class,
        ]);
    }
}