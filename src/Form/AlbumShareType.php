<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AlbumShareType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('emails', TextType::class, [
                'label' => 'Email Addresses',
                'help' => 'Enter one or more email addresses separated by commas',
                'attr' => [
                    'placeholder' => 'email1@example.com, email2@example.com',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Please enter at least one email address'
                    ])
                ]
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Personal Message (optional)',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Add a personal message to your invitation...',
                    'class' => 'form-control'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
