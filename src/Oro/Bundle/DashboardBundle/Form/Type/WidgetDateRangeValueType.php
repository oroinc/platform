<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for widget date range input with start and end date fields.
 *
 * This form type provides a compound field for selecting date ranges in widget
 * configurations. It creates separate start and end date inputs with customizable
 * field options, allowing widgets to filter data by date ranges. The form handles
 * both field rendering and data transformation for date range values.
 */
class WidgetDateRangeValueType extends AbstractType
{
    public const NAME = 'oro_type_widget_date_range_value';

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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'start',
            TextType::class,
            array_merge(
                ['required' => false],
                $options['field_options'],
                $options['start_field_options']
            )
        );

        $builder->add(
            'end',
            TextType::class,
            array_merge(
                ['required' => false],
                $options['field_options'],
                $options['end_field_options']
            )
        );
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $children                     = $form->all();
        $view->vars['value']['start'] = $children['start']->getViewData();
        $view->vars['value']['end']   = $children['end']->getViewData();
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'field_type'          => TextType::class,
                'field_options'       => [],
                'start_field_options' => [],
                'end_field_options'   => []
            )
        );
    }
}
