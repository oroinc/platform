<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\DateRangeType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * For filter type for Date range filter.
 * Time Zone set to UTC, to prevent dates modification and align filter with DB.
 */
class DateRangeFilterType extends AbstractDateFilterType
{
    const NAME = 'oro_type_date_range_filter';

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
        return FilterType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'field_type'       => DateRangeType::class,
                'time_zone'        => 'UTC',
                'widget_options'   => [
                    'showDatevariables' => true,
                    'showTime'          => false,
                    'showTimepicker'    => false,
                ],
                'operator_choices' => $this->getOperatorChoices(),
                'type_values'      => $this->getTypeValues(),
            ]
        );
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['type_values'] = $options['type_values'];

        parent::buildView($view, $form, $options);
    }
}
