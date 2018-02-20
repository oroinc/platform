<?php

namespace Oro\Bundle\QueryDesignerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ColumnType extends AbstractType
{
    const NAME = 'oro_query_designer_column';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                'oro_field_choice',
                [
                    'required'            => true,
                    'page_component_name' => 'column-field-choice',
                ] + $options['field_choice_options']
            )
            ->add('label', 'text', array('required' => true))
            ->add(
                'func',
                'oro_function_choice',
                [
                    'required' => false,
                    'page_component_name' => 'column-function-choice',
                    'query_type' =>  $options['query_type'],
                ]
            )
            ->add('sorting', 'oro_sorting_choice', array('required' => false));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['query_type']);

        $resolver->setDefaults(
            array(
                'entity'               => null,
                'data_class'           => 'Oro\Bundle\QueryDesignerBundle\Model\Column',
                'intention'            => 'query_designer_column',
                'column_choice_type'   => 'oro_entity_field_select',
                'field_choice_options' => [],
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
