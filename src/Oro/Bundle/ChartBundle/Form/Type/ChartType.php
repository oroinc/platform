<?php

namespace Oro\Bundle\ChartBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\ChartBundle\Model\ConfigProvider;

class ChartType extends AbstractType
{
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
        $chartConfig = $this->configProvider->getChartConfig($options['chart_name']);

        foreach ($chartConfig['settings_schema'] as $field) {
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
        $resolver->setRequired(array('chart_name'));
        $resolver->setAllowedTypes(array('chart_name' => 'string'));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_chart';
    }
}
