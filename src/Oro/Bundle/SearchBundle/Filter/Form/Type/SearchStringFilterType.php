<?php

namespace Oro\Bundle\SearchBundle\Filter\Form\Type;

use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SearchStringFilterType extends TextFilterType
{
    const TYPE_CONTAINS     = 1;
    const TYPE_NOT_CONTAINS = 2;
    const TYPE_EQUAL        = 3;
    const NAME              = 'oro_type_search_string_filter';

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $choices = array(
            self::TYPE_CONTAINS           => $this->translator->trans('oro.filter.form.label_type_contains'),
            self::TYPE_NOT_CONTAINS       => $this->translator->trans('oro.filter.form.label_type_not_contains'),
            self::TYPE_EQUAL              => $this->translator->trans('oro.filter.form.label_type_equals')
        );

        $resolver->setDefaults(
            array(
                'field_type'       => 'text',
                'operator_choices' => $choices,
            )
        );
    }
}
