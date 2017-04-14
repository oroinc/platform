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
}
