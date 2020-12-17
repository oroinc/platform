<?php

namespace Oro\Bundle\EmailBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

/**
 * Enables OAuth 2.0 for Gmail emails sync for tests.
 * This configuration is not full and cannot be used to use for testing connections to Google services.
 */
class GoogleImapEnableContext extends OroFeatureContext implements KernelAwareContext
{
    use KernelDictionary;

    /**
     * @Given /^(?:|I )enable Google IMAP$/
     */
    public function setConfigurationProperty()
    {
        $configManager = $this->getContainer()->get('oro_config.global');
        $configManager->set('oro_imap.enable_google_imap', true);
        $configManager->flush();
    }
}
