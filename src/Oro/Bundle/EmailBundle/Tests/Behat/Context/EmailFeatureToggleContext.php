<?php

namespace Oro\Bundle\EmailBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

/**
 * This context save behat execution time, all detailed steps can be found at
 * - "Manage Email Feature"
 */
class EmailFeatureToggleContext extends OroFeatureContext implements KernelAwareContext
{
    use KernelDictionary;

    /**
     * @When /^(?:|I )enable Email feature$/
     */
    public function enableEmailFeature()
    {
        $this->setFeatureState(1, 'oro_email', 'feature_enabled');
    }

    /**
     * @When /^(?:|I )disable Email feature$/
     */
    public function disableEmailFeature()
    {
        $this->setFeatureState(0, 'oro_email', 'feature_enabled');
    }

    /**
     * @param mixed $state
     * @param string $section
     * @param string $name
     */
    protected function setFeatureState($state, $section, $name)
    {
        $configManager = $this->getContainer()->get('oro_config.global');
        $configManager->set(sprintf('%s.%s', $section, $name), $state ? 1 : 0);
        $configManager->flush();
    }
}
