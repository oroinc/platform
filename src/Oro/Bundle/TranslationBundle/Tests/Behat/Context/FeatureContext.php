<?php

namespace Oro\Bundle\TranslationBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;

class FeatureContext extends OroFeatureContext
{
    /**
     * @var OroMainContext
     */
    private $oroMainContext;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->oroMainContext = $environment->getContext(OroMainContext::class);
    }

    /**
     * @Given I'm waiting for the translations to be reset
     */
    public function iWaitingForTranslationsReset()
    {
        $this->oroMainContext->iShouldSeeFlashMessage(
            'Selected translations were reset to their original values.',
            'Flash Message',
            600
        );
    }

    /**
     * @Given /^(?:|I )should see that the page does not contain untranslated labels$/
     */
    public function iShouldSeeThatThePageDoesNotContainUntranslatedLabels()
    {
        // Using 'substring' + 'string-length' function because 'matches' or 'ends-with' functions requires xpath2.0+
        $xpath = <<<EOF
//*[starts-with(text(), 'oro.') and substring(text(), string-length(text()) - string-length('.label') +1) = '.label']
EOF;
        $hasUntranslated = $this->getSession()->getPage()->has('xpath', $xpath);
        static::assertFalse($hasUntranslated);
    }
}
