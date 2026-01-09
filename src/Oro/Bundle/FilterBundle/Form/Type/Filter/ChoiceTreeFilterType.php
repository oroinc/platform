<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for choice tree filters.
 *
 * This form type provides a specialized filter interface for selecting values from
 * a hierarchical tree structure. It supports two filter operators: `contains` and
 * `not contains`, allowing users to filter records based on whether they contain
 * or exclude specific values from the tree hierarchy. The type extends {@see TextFilterType}
 * to inherit common text filtering behavior.
 */
class ChoiceTreeFilterType extends AbstractType
{
    public const TYPE_CONTAINS     = 1;
    public const TYPE_NOT_CONTAINS = 2;
    public const NAME              = 'oro_type_choice_tree_filter';

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return TextFilterType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = [
            1 => self::TYPE_CONTAINS,
            2 => self::TYPE_NOT_CONTAINS,
        ];

        $resolver->setDefaults(
            array(
                'field_type'       => TextType::class,
                'field_options'    => array(),
                'operator_choices' => $choices,
                'operator_type'    => ChoiceType::class,
                'operator_options' => array(),
                'show_filter'      => false,
                'autocomplete_url' => '',
                'className' => '',
                'data' => array()
            )
        )->setRequired(
            array(
                'field_type',
                'field_options',
                'operator_choices',
                'operator_type',
                'operator_options',
                'show_filter',
                'className'
            )
        );
    }
}
