<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\WarzoneTournois;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Repository\UserRepository;

class WarzoneTournoisTopKillerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('topKillerUser', EntityType::class, [
            'class' => User::class,
            'query_builder' => function (UserRepository $er) {
                return $er->createQueryBuilder('u')
                  ->orderBy('u.pseudo', 'ASC');
            },
            'choice_label' => 'pseudo',
            'mapped' => true,
            'required' => true,
            'label' => "Top Killer ?"
          ])

          ->add('topKillerKills', null, [
            'mapped' => true,
            'required' => true,
            'label' => "Kills ?"
          ])
          ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => WarzoneTournois::class,
        ]);
    }
}
