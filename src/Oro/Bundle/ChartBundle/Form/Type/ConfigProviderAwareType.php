<?php

namespace Oro\Bundle\ChartBundle\Form\Type;

use Oro\Bundle\ChartBundle\Model\ConfigProvider;
use Symfony\Component\Form\AbstractType;

abstract class ConfigProviderAwareType extends AbstractType
{
    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
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
        return 'oro_chart_aware';
    }
}
