<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FilterBundle\Form\Type\Filter\DateTimeRangeFilterType;

class WidgetDateTimeRangeType extends AbstractType
{
    const NAME = 'oro_type_widget_datetime_range';

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['datetime_range_metadata'] = [
            'name'       => $view->vars['full_name'] . '[type]',
            'label'      => $view->vars['label'],
            'typeValues' => $view->vars['type_values'],
            'dateParts'  => $view->vars['date_parts'],
            'externalWidgetOptions'  => array_merge(
                $view->vars['widget_options'],
                ['dateVars' => $view->vars['date_vars']]
            ),
            'templateSelector'       => '#date-filter-template-wo-actions',
            'criteriaValueSelectors' => [
                'type'      => 'select',
                'date_type' => 'select[name][name!=date_part]',
                'date_part' => 'select[name=date_part]',
                'value'     => [
                    'start' => 'input[name=\"' . $view->vars['full_name'] . '[value][start]\"]',
                    'end'   => 'input[name=\"' . $view->vars['full_name'] . '[value][end]\"]'
                ]
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['datetime_range_metadata'] = array_merge(
            $view->vars['datetime_range_metadata'],
            ['choices' => $view->children['type']->vars['choices']]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'compile_date' => false,
                'field_type'   => WidgetDateRangeValueType::NAME,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return DateTimeRangeFilterType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
