<?php

namespace Oro\Bundle\ApiBundle\Tests\Behat\Context;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class ApiContext extends OroFeatureContext
{
    /**
     * @Given /^(?:|I )enable API$/
     */
    public function setConfigurationProperty(): void
    {
        $configManager = $this->getAppContainer()->get('oro_config.global');
        $configManager->set('oro_api.web_api', true);
        $configManager->flush();
    }
}
