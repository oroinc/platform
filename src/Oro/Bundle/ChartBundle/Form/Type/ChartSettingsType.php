<?php

namespace Oro\Bundle\ChartBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\ChartBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ChartBundle\Model\ConfigProvider;

class ChartSettingsType extends AbstractType
{
    const NAME            = 'name';
    const SETTINGS_SCHEMA = 'settings_schema';
    const CHART_OPTIONS   = 'chart_options';

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $chartConfig = $this->getChartConfig($options);

        foreach ($chartConfig[self::SETTINGS_SCHEMA] as $field) {
            $options = !empty($field['options']) ? $field['options'] : array();

            $options['label'] = $field['label'];

            $builder->add($field['name'], $field['type'], $options);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(
            [
                self::NAME,
                self::CHART_OPTIONS
            ]
        );

        $resolver->setAllowedTypes(
            [
                self::NAME          => 'string',
                self::CHART_OPTIONS => 'array'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
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
        $chartConfig = [];
        $chartName   = $options[self::NAME];

        if (isset($options[self::NAME])) {
            $chartConfig = $this->configProvider->getChartConfig($chartName);
        }

        if (isset($options[self::CHART_OPTIONS])) {
            $chartConfig = $options[self::CHART_OPTIONS];
        }

        if (!$chartConfig) {
            throw new InvalidArgumentException(
                sprintf('Missing options for "%s" chart', $chartName)
            );
        }

        return $chartConfig;
    }
}
