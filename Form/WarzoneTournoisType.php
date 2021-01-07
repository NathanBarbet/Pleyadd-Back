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

class WarzoneTournoisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', null, [
              'required' => true,
              'label' => "Nom"
            ])

            ->add('description', null, [
                'required' => false,
                'label' => "Description"
            ])

            ->add('type', ChoiceType::class, [
                'choices'  => [
                    'MK Auto' => 'mk',
                    'MK Manuel' => 'mkmanuel',
                    'Duo Duo' => 'dd',
                    'PlayOff' => 'playoff',
                    'Event' => 'event',
                ],
                'required' => true,
                'label' => "Type"
            ])

            ->add('nombre', ChoiceType::class, [
                'choices'  => [
                    'Solo' => '1',
                    'Duo' => '2',
                    'Trio' => '3',
                    'Quad' => '4'
                ],
                'required' => true,
                'label' => "Nombre"
            ])

            ->add('dateDebut', DateTimeType::class, [
                'required' => true,
                'widget' => 'choice',
            ])

            ->add('dateFin', DateTimeType::class, [
                'required' => true,
                'widget' => 'choice',
            ])

            ->add('dateFinInscription', DateTimeType::class, [
                'required' => true,
                'widget' => 'choice',
            ])

            ->add('plateforme', null, [
                'required' => true,
                'label' => "Plateforme"
            ])

            ->add('recompenses', null, [
                'required' => false,
                'label' => "Récompenses"
            ])
            
            ->add('kdcap', null, [
                'required' => true,
                'label' => "KDCAP"
            ])

            ->add('reglements', null, [
                'required' => false,
                'label' => "Reglements"
            ])

            ->add('image', FileType::class, [
                'label' => 'Image jpg/pdf/gif',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image',
                    ])
                ],
            ])

            ->add('imageMobile', FileType::class, [
                'label' => 'Image Mobile jpg/pdf/gif',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image',
                    ])
                ],
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
