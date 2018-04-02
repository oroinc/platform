<?php

namespace Oro\Bundle\ReportBundle\Form\Type;

use Oro\Bundle\ChartBundle\Form\Type\ChartType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReportChartType extends AbstractType
{
    const VIEW_MODULE_NAME = 'ororeport/js/app/views/report-chart-view';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('data_schema', ReportChartSchemaCollectionType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr']['data-page-component-view'] = self::VIEW_MODULE_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'chart_filter' => function ($chartConfig) {
                    return !empty($chartConfig['default_settings']['available_in_reports']);
                }
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChartType::class;
    }

    /**
     * {@inheritdoc}
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
        return 'oro_report_chart';
    }
}
