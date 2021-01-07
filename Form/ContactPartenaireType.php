<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;

class ContactPartenaireType extends AbstractType
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

            ->add('raisonSociale', null, [
              'required' => true,
              'label' => "Raison sociale *",
            ])

            ->add('username', EmailType::class, [
              'required' => true,
              'label' => "Email *",
              'constraints' => [new Email()],
            ])

            ->add('telephone', null, [
                'required' => true,
                'label' => "Téléphone *",
            ])

            ->add('sujet', TextareaType::class, [
              'invalid_message' => 'Votre titre est trop long. (3000 caract)',
              'required' => true,
              'label' => "Sujet *",
              'constraints' => [new Length(['max' => '3000'])]
            ])

            ->add('texte', TextareaType::class, [
                'invalid_message' => 'Votre message est trop long. (1000000 caract)',
                'required' => true,
                'label' => "Message *",
                'constraints' => [new Length(['max' => '1000000'])]
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        ]);
    }
}
