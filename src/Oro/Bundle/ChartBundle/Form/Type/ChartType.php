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
        $chartConfigs = $this->configProvider->getChartConfigs();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_chart';
    }
}
