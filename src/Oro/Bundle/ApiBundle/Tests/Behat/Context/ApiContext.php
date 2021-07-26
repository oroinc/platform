<?php

namespace Oro\Bundle\ApiBundle\Tests\Behat\Context;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class ApiContext extends OroFeatureContext
{
    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @Given /^(?:|I )enable API$/
     */
    public function setConfigurationProperty()
    {
        $this->configManager->set('oro_api.web_api', true);
        $this->configManager->flush();
    }
}
