<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WidgetDateRangeValueType extends AbstractType
{
    const NAME = 'oro_type_widget_date_range_value';

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

    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $children                     = $form->all();
        $view->vars['value']['start'] = $children['start']->getViewData();
        $view->vars['value']['end']   = $children['end']->getViewData();
    }

    /**
     * {@inheritDoc}
     */
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
