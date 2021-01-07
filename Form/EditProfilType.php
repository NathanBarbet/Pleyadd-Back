<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Type;

class EditProfilType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, [
              'attr' => ['autocomplete' => 'off'],
              'required' => false,
              'label' => "Prénom"
            ])

            ->add('firstname', null, [
              'attr' => ['autocomplete' => 'off'],
              'required' => false,
              'label' => "Nom"
            ])

            ->add('clan', null, [
              'attr' => ['autocomplete' => 'off'],
              'required' => false,
              'label' => "Tag de clan"
            ])

            ->add('timezone', TimezoneType::class, [
              'attr' => ['autocomplete' => 'off'],
              'required' => false,
              'placeholder' => 'Fuseau horaire',
              'label' => "Timezone"
            ])

            ->add('dob', Birthdaytype::class, [
              'attr' => ['autocomplete' => 'off'],
              'format' => 'yyyy-MM-dd',
              'widget' => 'single_text',
              'required' => false,
              'label' => "Date de naissance",
            ])

            ->add('bio', TextType::class, [
              'attr' => ['autocomplete' => 'off'],
              'required' => false,
              'label' => "Bio",
              'constraints' => [new Length(['max' => '200'])]
            ])

            ->add('twitch', null, [
              'attr' => ['autocomplete' => 'off'],
              'required' => false,
              'label' => "ID Twitch",
            ])

            ->add('discord', null, [
              'attr' => ['autocomplete' => 'off'],
              'required' => false,
              'label' => "ID Discord",
              'constraints' => [new Regex(['pattern' => '@[a-zA-Z0-9_]{1,}#[0-9]{1,}@'])]
            ])

            ->add('steam', null, [
              'attr' => ['autocomplete' => 'off'],
              'required' => false,
              'label' => "ID Steam",
            ])

            ->add('twitter', null, [
              'attr' => ['autocomplete' => 'off'],
              'required' => false,
              'label' => "ID Twitter",
            ])

            ->add('youtube', null, [
              'attr' => ['autocomplete' => 'off'],
              'required' => false,
              'label' => "ID Youtube",
            ])

            ->add('email2', null, [
              'attr' => ['autocomplete' => 'off'],
              'required' => false,
              'label' => "Email",
            ])

            ->add('battlenet', null, [
              'attr' => ['autocomplete' => 'off'],
              'required' => false,
              'label' => "ID Battlenet",
            ])

            ->add('psn', null, [
              'attr' => ['autocomplete' => 'off'],
              'required' => false,
              'label' => "ID PSN",
            ])

            ->add('xbox', null, [
              'attr' => ['autocomplete' => 'off'],
              'required' => false,
              'label' => "ID Xbox",
            ])

            ->add('trn', null, [
              'mapped' => false,
              'attr' => ['autocomplete' => 'off'],
              'required' => false,
              'label' => "ID Activision",
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
