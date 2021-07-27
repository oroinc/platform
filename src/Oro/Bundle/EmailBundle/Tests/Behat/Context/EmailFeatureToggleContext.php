<?php

namespace Oro\Bundle\EmailBundle\Tests\Behat\Context;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

/**
 * This context save behat execution time, all detailed steps can be found at
 * - "Manage Email Feature"
 */
class EmailFeatureToggleContext extends OroFeatureContext
{
    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

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
        $this->configManager->set(sprintf('%s.%s', $section, $name), $state ? 1 : 0);
        $this->configManager->flush();
    }
}
