<?php

namespace App\Form;


use App\Entity\Medias;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class MediasFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('path', options:[
                'label' => 'Copier le lien de la video',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('type', HiddenType::class, [
                'data' => 'video',
            ])

            ->add('main', CheckboxType::class, [
                
            ])
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Medias::class,
        ]);
    }
}
