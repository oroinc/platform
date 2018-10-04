<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Form\Type;

use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractChoiceType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type which can be used as a boolean filter for datagrids with search datasource.
 */
class SearchBooleanFilterType extends AbstractChoiceType
{
    const NAME = 'oro_search_type_boolean_filter';

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setNormalizer('field_options', function (Options $options, $value) {
            if (!$value) {
                $value = [];
            }
            $value['multiple'] = true;

            return $value;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return BooleanFilterType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
