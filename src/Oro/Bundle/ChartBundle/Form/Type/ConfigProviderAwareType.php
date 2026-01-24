<?php

namespace Oro\Bundle\ChartBundle\Form\Type;

use Oro\Bundle\ChartBundle\Model\ConfigProvider;
use Symfony\Component\Form\AbstractType;

/**
 * Provides common functionality for form types that need access to chart configuration.
 *
 * This base class injects the chart {@see ConfigProvider} service, making chart configuration data
 * available to form types. Subclasses can use the config provider to access chart definitions, settings,
 * and metadata when building or configuring chart-related forms.
 */
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

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_chart_aware';
    }
}
