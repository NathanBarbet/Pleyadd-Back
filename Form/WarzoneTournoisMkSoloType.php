<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;

class WarzoneTournoisMkSoloType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('team', null, [
                'attr' => ['autocomplete' => 'chrome-off'],
                'required' => true,
                'mapped' => false,
                'label' => "Nom de la team",
                'constraints' => [new Regex(['pattern' => '/(?!^\d+$)^.+$/'])],
            ])

            ->add('lead', null, [
                'attr' => ['autocomplete' => 'chrome-off'],
                'required' => true,
                'mapped' => false,
                'label' => "Leader",
            ])

            ->add('submitform', SubmitType::class, [
                'label' => "Valider inscription",
                'attr' => ['class' => 'login100-form-btn'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
