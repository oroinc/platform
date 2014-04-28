<?php

namespace Oro\Bundle\ReportBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\ChartBundle\Form\Type\ConfigProviderAwareType;

class ReportChartSchemaCollectionType extends ConfigProviderAwareType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $chartConfigs = $this->configProvider->getChartConfigs();

        foreach ($chartConfigs as $chartName => $chartConfig) {
            if (isset($chartConfig['data_schema'])) {
                $builder->add(
                    $chartName,
                    'oro_report_chart_schema',
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
        return 'oro_report_chart_schema_collection';
    }
}
