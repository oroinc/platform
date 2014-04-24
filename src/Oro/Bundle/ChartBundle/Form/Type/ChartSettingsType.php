<?php

namespace Oro\Bundle\ChartBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\ChartBundle\Model\ConfigProvider;

class ChartSettingsType extends AbstractType
{
    const NODE_NAME = 'chart_name';
    const NODE_SETTINGS = 'settings_schema';

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
        $chartConfig = $this->configProvider->getChartConfig($options[self::NODE_NAME]);

        foreach ($chartConfig[self::NODE_SETTINGS] as $field) {
            $options          = !empty($field['options']) ? $field['options'] : array();
            $options['label'] = $field['label'];

            $builder->add($field['name'], $field['type'], $options);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array(self::NODE_NAME));
        $resolver->setAllowedTypes(array(self::NODE_NAME => 'string'));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_chart';
    }
}
