<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\DateTimeRangeType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * DateTimeRangeFilter form type.
 */
class DateTimeRangeFilterType extends AbstractDateFilterType
{
    const NAME = 'oro_type_datetime_range_filter';

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
        return DateRangeFilterType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'field_type' => DateTimeRangeType::class,
                // Allows to specify which time zone to use when creating filter data.
                // If option is null, the time zone specified in the system configuration (localization) is used.
                'time_zone' => 'UTC',
                'widget_options' => [
                    'showDatevariables' => true,
                    'showTime' => true,
                    'showTimepicker' => true,
                ]
            ]
        );
    }
}
