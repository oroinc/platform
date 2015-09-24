<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type\Filter;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractChoiceType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Symfony\Component\Form\AbstractType;

class BusinessUnitChoiceFilterType extends AbstractType
{
    const TYPE_CONTAINS     = 1;
    const TYPE_NOT_CONTAINS = 2;
    const NAME              = 'oro_type_business_unit_filter';

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
            )
        )->setRequired(
            array(
                'field_type',
                'field_options',
                'operator_choices',
                'operator_type',
                'operator_options',
                'show_filter'
            )
        );
    }

//    /**
//     * {@inheritDoc}
//     */
//    public function setDefaultOptions(OptionsResolverInterface $resolver)
//    {
//        $choices = array(
//            self::TYPE_CONTAINS     => $this->translator->trans('oro.filter.form.label_type_contains'),
//            self::TYPE_NOT_CONTAINS => $this->translator->trans('oro.filter.form.label_type_not_contains'),
//        );
//
//        $resolver->setDefaults(
//            array(
//                'field_type'       => 'text',
//                'field_options'    => array(),
//                'operator_choices' => $choices,
//                'populate_default' => false,
//                'default_value'    => null,
//                'null_value'       => null,
//                'class'            => null
//            )
//        );
//    }
}
