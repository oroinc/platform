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

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('data_schema', ReportChartSchemaCollectionType::class);
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr']['data-page-component-view'] = self::VIEW_MODULE_NAME;
    }

    #[\Override]
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

    #[\Override]
    public function getParent(): ?string
    {
        return ChartType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_report_chart';
    }
}
