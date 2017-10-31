<?php

namespace Oro\Bundle\ConfigBundle\Tests\Functional\Controller;

class ConfigurationControllerTest extends AbstractConfigurationControllerTest
{
    /**
     * {@inheritdoc}
     */
    protected function getRequestUrl(array $parameters)
    {
        return $this->getUrl('oro_config_configuration_system', $parameters);
    }
}
