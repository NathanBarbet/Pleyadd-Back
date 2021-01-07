<?php

namespace App\Form;

use App\Entity\WarzoneTournoisEquipeResultats;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Repository\UserRepository;

class WarzoneTournoisResultatsTrioTypePart3 extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('userKills1', NumberType::class, [
                'attr' => ['autocomplete' => 'chrome-off'],
                'required' => true,
                'mapped' => true,
                'constraints' => [new Regex(['pattern' => '#^[0-9]#'])]
            ])

            ->add('userKills2', NumberType::class, [
                'attr' => ['autocomplete' => 'chrome-off'],
                'required' => true,
                'mapped' => true,
                'constraints' => [new Regex(['pattern' => '#^[0-9]#'])]
            ])

            ->add('userKills3', NumberType::class, [
                'attr' => ['autocomplete' => 'chrome-off'],
                'required' => true,
                'mapped' => true,
                'constraints' => [new Regex(['pattern' => '#^[0-9]#'])]
            ])

            ->add('position', NumberType::class, [
                'attr' => ['autocomplete' => 'chrome-off'],
                'required' => true,
                'mapped' => true,
                'label' => "Position",
                'constraints' => [new Regex(['pattern' => '#^[0-9]#'])]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => WarzoneTournoisEquipeResultats::class,
        ]);
    }
}
