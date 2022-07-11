<?php

namespace Oro\Bundle\ConfigBundle\Provider;

/**
 * Provides configuration of a system configuration form on the application level.
 */
class SystemConfigurationFormProvider extends AbstractProvider
{
    /**
     * {@inheritdoc}
     */
    protected function getTreeName(): string
    {
        return 'system_configuration';
    }

    /**
     * {@inheritdoc}
     */
    protected function getParentCheckboxLabel(): string
    {
        return 'oro.config.system_configuration.use_default';
    }
}
