<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderDictionary;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements
    KernelAwareContext,
    FixtureLoaderAwareInterface,
    OroPageObjectAware
{
    use KernelDictionary, FixtureLoaderDictionary, PageObjectDictionary;

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
     * @Given I am on (Login) page
     */
    public function iAmOnLoginPage()
    {
        $uri = $this->getContainer()->get('router')->generate('oro_user_security_login');
        $this->visitPath($uri);
    }

    /**
     * @Given (Charlie Sheen) (active) user exists in the system
     */
    public function charlieUserInTheSystem()
    {
        $this->fixtureLoader->loadFixtureFile('user.yml');
    }

    /**
     * @Then (Charlie Sheen) user could login to the Dashboard
     */
    public function charlieCanLogin()
    {
        $this->getMink()->setDefaultSessionName('second_session');
        $this->oroMainContext->loginAsUserWithPassword('charlie');
        $this->waitForAjax();

        $this->oroMainContext->assertPage('Admin Dashboard');
        $this->getSession('second_session')->stop();
        $this->getMink()->setDefaultSessionName('first_session');
    }

    /**
     * @Then (Charlie Sheen) user has no possibility to login to the Dashboard
     */
    public function charlieCantLogin()
    {
        $this->getMink()->setDefaultSessionName('second_session');
        $this->oroMainContext->loginAsUserWithPassword('charlie');
        $this->waitForAjax();

        $this->oroMainContext->assertPage('Login');
        $this->getSession('second_session')->stop();
        $this->getMink()->setDefaultSessionName('first_session');
    }
}
