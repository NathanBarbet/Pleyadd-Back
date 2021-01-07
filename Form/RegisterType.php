<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

class RegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, [
              'required' => true,
              'label' => "Prénom *"
            ])

            ->add('firstname', null, [
              'required' => true,
              'label' => "Nom *"
            ])

            ->add('pseudo', null, [
              'required' => true,
              'label' => "Pseudo *",
              'constraints' => [new Length(['max' => '30','min' => '3']), new Regex(['pattern' => '/^[^#]*$/'])],
            ])

            ->add('username', EmailType::class, [
              'required' => true,
              'label' => "Email *",
              'constraints' => [new Email()],
            ])

            ->add('password', RepeatedType::class, [
              'type' => PasswordType::class,
              'invalid_message' => 'The password fields must match.',
              'required' => true,
              'first_options'  => ['label' => 'Mot de passe *'],
              'second_options' => ['label' => 'Répéter mot de passe *'],
              'constraints' => [new Regex(['pattern' => '#^(?=.*[a-z])(?=.*[A-Z])(?=.*[a-zA-Z]).{6,}$#'])]
            ])

            ->add('dob', Birthdaytype::class, [
              'format' => 'yyyy-MM-dd',
              'widget' => 'single_text',
              'required' => true,
              'label' => "Date de naissance *",
            ])

            ->add('trn', null, [
              'attr' => ['autocomplete' => 'off'],
              'required' => true,
              'label' => "ID Activision *",
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'constraints' => array(
                new UniqueEntity(array('fields' => array('pseudo'), 'message' => 'Ce pseudo est déjà utiliser'))),
        ]);
    }
}
