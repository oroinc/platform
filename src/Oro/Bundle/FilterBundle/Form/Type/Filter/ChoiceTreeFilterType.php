<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;

use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;

class ChoiceTreeFilterType extends AbstractType
{
    const TYPE_CONTAINS     = 1;
    const TYPE_NOT_CONTAINS = 2;
    const NAME              = 'oro_type_choice_tree_filter';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return TextFilterType::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $choices = array(
            self::TYPE_CONTAINS           => 1,
            self::TYPE_NOT_CONTAINS       => 2,
        );

        $resolver->setDefaults(
            array(
                'field_type'       => 'text',
                'field_options'    => array(),
                'operator_choices' => $choices,
                'operator_type'    => 'choice',
                'operator_options' => array(),
                'show_filter'      => false,
                'autocomplete_url' => '',
                'className' => '',
                'data'=> array()
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
