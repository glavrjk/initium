<?php

namespace App\Form;

use App\Entity\Content;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ContentType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('description')
            ->add('mediaFiles', CollectionType::class, [
                'entry_type' => FileType::class,
                'entry_options' => [
                    'required' => false,
                    'constraints' => [
                        new File([
                            'maxSize' => '5M',
                            'mimeTypes' => ['image/*', 'video/*','application/pdf'],
                        ])
                    ]
                ],
                'mapped' => false,
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
            ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Content::class,
        ]);
    }
}
