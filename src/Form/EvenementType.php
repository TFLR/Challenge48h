<?php

namespace App\Form;

use App\Entity\Evenement;
use App\Entity\Categorie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('duree')
            ->add('image')
            ->add('date')
            ->add('description')
            ->add('categorie', EntityType::class, [  // j'indique que le champ category est une entity
                'class' => Categorie::class, // je précise quelle entity
                'choice_label' => 'titre'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'novalidate' => 'novalidate'
            ],
            'data_class' => Evenement::class,
        ]);
    }
}
