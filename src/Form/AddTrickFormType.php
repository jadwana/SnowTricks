<?php

namespace App\Form;

use App\Entity\Tricks;
use App\Entity\Categories;
use App\Form\VideosFormType;
use App\Repository\CategoriesRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class AddTrickFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'name', options: [
                'label' => 'Nom de la figure'
                ]
            )
            ->add(
                'description', options: [
                'label' => 'Description de la figure'
                ]
            )
            ->add(
                'category', EntityType::class,[
                'class' => Categories::class,
                'choice_label' => 'name',
                'label' => 'Choisissez le groupe de cette figure',
                'query_builder' => function (CategoriesRepository $cr) {
                    return $cr->createQueryBuilder('c')
                        ->orderBy('c.name', 'ASC');
                }
                ]
            )
            ->add(
                'images', FileType::class, [
                'label' => 'Ajouter une ou plusieurs images',
                'multiple' => true,
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new All(
                        new Image(
                            [
                            // 'maxWidth' => 1280,
                            // 'maxWidthMessage' => 'l\'image doit faire {{ max_width }} px de large max',
                            'mimeTypesMessage' => 'Ce fichier n\'est pas une image'
                            ]
                        )
                    )
                ]
                ]
            )


            ->add(
                'videos', CollectionType::class, [
                'entry_type' => VideosFormType::class,
                'entry_options' => ['label' => false],
                'label' => false,
                'allow_add' => true,
                'allow_delete' => true


                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
            'data_class' => Tricks::class,
            ]
        );
    }
}
