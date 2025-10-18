<?php

namespace App\Form;

use App\Entity\Album;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;

class BulkPhotoUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('album', EntityType::class, [
                'class' => Album::class,
                'choice_label' => 'name',
                'placeholder' => 'Select an album',
                'required' => true,
                'constraints' => [
                    new NotNull(['message' => 'Please select an album']),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('photos', FileType::class, [
                'label' => 'Select Photos',
                'multiple' => true,
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new All([
                        new File([
                            'maxSize' => '100M',
                            'mimeTypes' => [
                                'image/jpeg',
                                'image/jpg',
                                'image/png',
                                'image/gif',
                                'image/webp',
                            ],
                            'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, GIF, WebP)',
                        ]),
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control file-upload-input',
                    'accept' => 'image/*',
                    'data-dropzone' => 'true',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
