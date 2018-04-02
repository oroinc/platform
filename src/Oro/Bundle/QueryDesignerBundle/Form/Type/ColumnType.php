<?php

namespace Oro\Bundle\QueryDesignerBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityFieldSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
                'csrf_token_id'        => 'query_designer_column',
                'column_choice_type'   => EntityFieldSelectType::class,
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
