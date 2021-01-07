<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\WarzoneUserPoint;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Repository\UserRepository;

class WarzonePointsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        
        $builder
            ->add('pseudo', EntityType::class, [
              'class' => User::class,
              'query_builder' => function (UserRepository $er) {
                return $er->createQueryBuilder('u')
                    ->orderBy('u.pseudo', 'ASC');
              },
              'choice_label' => 'pseudo',
              'mapped' => false,
              'required' => true,
              'label' => "Pseudo"
            ])

            ->add('points', null, [
              'mapped' => false,
              'required' => true,
              'label' => "Points a give"
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => WarzoneUserPoint::class,
        ]);
    }
}
