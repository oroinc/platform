<?php

namespace Oro\Bundle\QueryDesignerBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityFieldSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for configuring query designer columns.
 *
 * This form type provides a user interface for defining columns in a query designer query.
 * It allows users to select a field, provide a display label, optionally apply an aggregation
 * function, and configure sorting direction. The form integrates with the entity field selection
 * system to provide context-aware field choices based on the query type.
 */
class ColumnType extends AbstractType
{
    public const NAME = 'oro_query_designer_column';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                FieldChoiceType::class,
                [
                    'required'            => true,
                    'page_component_name' => 'column-field-choice',
                ] + $options['field_choice_options']
            )
            ->add('label', TextType::class, array('required' => true))
            ->add(
                'func',
                FunctionChoiceType::class,
                [
                    'required' => false,
                    'page_component_name' => 'column-function-choice',
                    'query_type' =>  $options['query_type'],
                ]
            )
            ->add('sorting', SortingChoiceType::class, array('required' => false));
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['query_type']);

        $resolver->setDefaults(
            array(
                'entity'               => null,
                'data_class'           => 'Oro\Bundle\QueryDesignerBundle\Model\Column',
                'csrf_token_id'        => 'query_designer_column',
                'column_choice_type'   => EntityFieldSelectType::class,
                'field_choice_options' => [],
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
        return self::NAME;
    }
}
