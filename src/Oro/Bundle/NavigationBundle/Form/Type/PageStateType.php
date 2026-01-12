<?php

namespace Oro\Bundle\NavigationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for page state data.
 *
 * This form type defines the structure and configuration for page state forms. It includes fields
 * for the page identifier and the serialized state data. Page state forms are used to capture and
 * persist user interface preferences and layout configurations for specific pages.
 */
class PageStateType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'pageId',
                TextType::class,
                array(
                    'required' => true,
                )
            )
            ->add(
                'data',
                TextareaType::class,
                array(
                    'required' => true,
                )
            );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'      => 'Oro\Bundle\NavigationBundle\Entity\AbstractPageState',
                'csrf_token_id'   => 'pagestate',
                'csrf_protection' => false,
            )
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'pagestate';
    }
}
