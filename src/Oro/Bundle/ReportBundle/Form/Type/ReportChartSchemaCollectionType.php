<?php

namespace Oro\Bundle\ReportBundle\Form\Type;

use Oro\Bundle\ChartBundle\Form\Type\ConfigProviderAwareType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * The form type for schemas of all charts available in reports.
 */
class ReportChartSchemaCollectionType extends ConfigProviderAwareType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $chartNames = $this->configProvider->getChartNames();
        foreach ($chartNames as $chartName) {
            $chartConfig = $this->configProvider->getChartConfig($chartName);
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
