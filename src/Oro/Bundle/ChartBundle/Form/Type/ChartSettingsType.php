<?php

namespace Oro\Bundle\ChartBundle\Form\Type;

use Oro\Bundle\ChartBundle\Exception\InvalidArgumentException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChartSettingsType extends ConfigProviderAwareType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $chartConfig = $this->getChartConfig($options);

        foreach ($chartConfig['settings_schema'] as $field) {
            $fieldOptions = !empty($field['options']) ? $field['options'] : array();

            $fieldOptions['label'] = $field['label'];

            $builder->add($field['name'], $field['type'], $fieldOptions);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['chart_name']);
        $resolver->setDefined(['chart_config']);

        $resolver->setAllowedTypes('chart_name', 'string');
        $resolver->setAllowedTypes('chart_config', 'array');
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
        return 'oro_chart_setting';
    }

    /**
     * @param array $options
     * @throws InvalidArgumentException
     * @return array
     */
    protected function getChartConfig(array $options)
    {
        $chartName = $options['chart_name'];

        if (isset($options['chart_config'])) {
            $chartConfig = $options['chart_config'];
        } else {
            $chartConfig = $this->configProvider->getChartConfig($chartName);
        }

        return $chartConfig;
    }
}
