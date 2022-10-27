<?php

namespace Oro\Bundle\FilterBundle\Form\Type;

use Oro\Bundle\FilterBundle\Filter\DateTimeRangeFilter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for a value of {@see \Oro\Bundle\FilterBundle\Filter\DateTimeRangeFilter}.
 */
class DateTimeRangeType extends AbstractType
{
    const NAME = 'oro_type_datetime_range';

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
        return DateRangeType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'field_type'    => DateTimeType::class,
            'field_options' => [
                'format' => DateTimeRangeFilter::DATE_FORMAT,
                'html5'  => false,
            ],
        ]);
    }
}
