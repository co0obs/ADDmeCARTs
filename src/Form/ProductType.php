<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('price')
            ->add('stockQuantity')
            
            // Category Dropdown
            ->add('category', ChoiceType::class, [
                'choices'  => [
                    'Electronics & Gadgets' => 'Electronics',
                    'Clothing & Apparel' => 'Clothing',
                    'Home & Living' => 'Home',
                    'Sports & Outdoors' => 'Sports',
                    'Health & Beauty' => 'Beauty',
                    'Toys & Hobbies' => 'Toys',
                    'Other' => 'Other',
                ],
                'placeholder' => 'Select a Category...',
                'attr' => [
                    'style' => 'width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ccc; margin-bottom: 15px;'
                ]
            ])
            
            ->add('thumbnailImage')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}