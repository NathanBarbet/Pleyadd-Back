<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use App\Repository\UserRepository;

class WarzoneTournoisMkDuoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('team', null, [
                'attr' => ['autocomplete' => 'chrome-off'],
                'required' => true,
                'mapped' => false,
                'label' => "NOM D'EQUIPE",
                'constraints' => [new Regex(['pattern' => '/(?!^\d+$)^.+$/'])],
            ])

            ->add('lead', null, [
                'attr' => ['autocomplete' => 'chrome-off'],
                'required' => true,
                'mapped' => false,
                'label' => "CAPITAINE",
            ])

            ->add('pseudo', EntityType::class, [
                'class' => User::class,
                'query_builder' => function (UserRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.pseudo', 'ASC');
                },
                'choice_label' => 'pseudo',
                'mapped' => false,
                'required' => true,
                'label' => "2E MEMBRE"
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
