<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return TextFilterType::class;
    }

    /**
     * {@inheritDoc}
     */
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
