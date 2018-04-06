<?php

namespace Oro\Bundle\ReportBundle\Form\Type;

use Oro\Bundle\ChartBundle\Form\Type\ConfigProviderAwareType;
use Symfony\Component\Form\FormBuilderInterface;

class ReportChartSchemaCollectionType extends ConfigProviderAwareType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $chartConfigs = $this->configProvider->getChartConfigs();

        foreach ($chartConfigs as $chartName => $chartConfig) {
            $isAvailable = !empty($chartConfig['default_settings']['available_in_reports']);
            if ($isAvailable && isset($chartConfig['data_schema'])) {
                $builder->add(
                    $chartName,
                    ReportChartSchemaType::class,
                    [
                        'data_schema' => $chartConfig['data_schema']
                    ]
                );
            }
        }
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
        return 'oro_report_chart_data_schema_collection';
    }
}
