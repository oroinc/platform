<?php

namespace Oro\Bundle\ApiBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class ApiContext extends OroFeatureContext implements KernelAwareContext
{
    use KernelDictionary;

    /**
     * @Given /^(?:|I )enable API$/
     */
    public function setConfigurationProperty()
    {
        $configManager = $this->getContainer()->get('oro_config.global');
        $configManager->set('oro_api.web_api', true);
        $configManager->flush();
    }
}
