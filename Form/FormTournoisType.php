<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;

class FormTournoisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder

            ->add('nomEquipe', null, [
              'required' => true,
              'label' => "Nom d'équipe *",
            ])

            ->add('email', EmailType::class, [
              'required' => true,
              'label' => "Votre email (Gmail) *",
              'constraints' => [new Email()],
            ])

            ->add('pseudo', null, [
              'required' => true,
              'label' => "Votre pseudo Discord *",
            ])

            ->add('ID1', null, [
              'required' => true,
              'label' => "Votre identifiant joueur * (Activision, PSN, Gamertag ou Battlenet)",
            ])
            ->add('plateformeID1', ChoiceType::class, [
              'choices'  => [
                'Activision ID' => 'Activision',
                'BattleNet' => 'BattleNet',
                'PSN' => 'PSN',
                'Xbox' => 'XBOX'
              ],
              'attr' => ['autocomplete' => 'off'],
              'required' => true,
              'label' => "Plateforme",
            ])

            ->add('ID2', null, [
              'required' => true,
              'label' => "Identifiant joueur du 2ème participant * (Activision, PSN, Gamertag ou Battlenet)",
            ])
            ->add('plateformeID2', ChoiceType::class, [
              'choices'  => [
                'Activision ID' => 'Activision',
                'BattleNet' => 'BattleNet',
                'PSN' => 'PSN',
                'Xbox' => 'XBOX'
              ],
              'attr' => ['autocomplete' => 'off'],
              'required' => true,
              'label' => "Plateforme",
            ])

            ->add('ID3', null, [
              'required' => true,
              'label' => "Identifiant joueur du 3ème participant * (Activision, PSN, Gamertag ou Battlenet)",
            ])

            ->add('plateformeID3', ChoiceType::class, [
              'choices'  => [
                'Activision ID' => 'Activision',
                'BattleNet' => 'BattleNet',
                'PSN' => 'PSN',
                'Xbox' => 'XBOX'
              ],
              'attr' => ['autocomplete' => 'off'],
              'required' => true,
              'label' => "Plateforme",
            ])

            ->add('ID4', null, [
              'required' => true,
              'label' => "Identifiant joueur du 4ème participant * (Activision, PSN, Gamertag ou Battlenet)",
            ])
            ->add('plateformeID4', ChoiceType::class, [
              'choices'  => [
                'Activision ID' => 'Activision',
                'BattleNet' => 'BattleNet',
                'PSN' => 'PSN',
                'Xbox' => 'XBOX'
              ],
              'attr' => ['autocomplete' => 'off'],
              'required' => true,
              'label' => "Plateforme",
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        ]);
    }
}
