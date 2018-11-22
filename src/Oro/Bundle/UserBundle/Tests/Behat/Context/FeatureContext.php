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
     *
     * @param BeforeScenarioScope $scope
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->oroMainContext = $environment->getContext(OroMainContext::class);
    }

    /**
     * Open dashboard login page
     * It's generated from 'oro_user_security_login' route name for current application
     *
     * @Given I am on (Login) page
     */
    public function iAmOnLoginPage()
    {
        $uri = $this->getContainer()->get('router')->generate('oro_user_security_login');
        $this->visitPath($uri);
    }

    /**
     * Logout user
     *
     * @Given I am logged out
     */
    public function iAmLoggedOut()
    {
        $uri = $this->getContainer()->get('router')->generate('oro_user_security_logout');
        $this->visitPath($uri);
    }

    /**
     * Load "user.yml" alice fixture from UserBundle suite
     *
     * @Given (Charlie Sheen) (active) user exists in the system
     */
    public function charlieUserInTheSystem()
    {
        $this->fixtureLoader->loadFixtureFile('OroUserBundle:user.yml');
    }

    /**
     * Assert that user with 'charlie' username has access to dashboard
     *
     * @Then (Charlie Sheen) user could login to the Dashboard
     */
    public function charlieCanLogin()
    {
        self::assertFalse($this->getMink()->isSessionStarted('system_session'));
        $this->getMink()->setDefaultSessionName('system_session');
        $this->oroMainContext->loginAsUserWithPassword('charlie');
        $this->waitForAjax();

        $this->oroMainContext->assertPage('Admin Dashboard');
        $this->getSession('system_session')->stop();
        $this->getMink()->setDefaultSessionName('first_session');
    }

    /**
     * Assert that user with 'charlie' username has NOT access to dashboard
     *
     * @Then (Charlie Sheen) user has no possibility to login to the Dashboard
     */
    public function charlieCantLogin()
    {
        self::assertFalse($this->getMink()->isSessionStarted('system_session'));
        $this->getMink()->setDefaultSessionName('system_session');
        $this->oroMainContext->loginAsUserWithPassword('charlie');

        $error = $this->spin(function (FeatureContext $context) {
            return $context->getPage()->find('css', 'div.alert-error');
        }, 5);

        self::assertNotNull($error, 'Expect to find error on page, but it not found');
        self::assertEquals('Account is locked.', $error->getText());

        $this->oroMainContext->assertPage('Login');
        $this->getSession('system_session')->stop();
        $this->getMink()->setDefaultSessionName('first_session');
    }

    /**
     * Click on button "Reset" in modal window or reset page and skip wait ajax
     *
     * @When /^(?:|I )confirm reset password$/
     */
    public function iConfirmResetPassword()
    {
        $modalWindow = $this->oroMainContext->getPage()->findVisible('css', 'div.modal, div[role="dialog"]');
        if ($modalWindow) {
            $this->oroMainContext->pressButtonInModalWindow('Reset');
        } else {
            $this->oroMainContext->pressButton('Request');
        }

        // need to be skiped ajax wait, because we have redirect to login page and no ajax requests
        $this->oroMainContext->applySkipWait();
    }

    /**
     * @When /^(?:|I )confirm login$/
     */
    public function iConfirmLogin()
    {
        $this->oroMainContext->pressButton('Log in');

        // need to be skiped ajax wait, because we have redirect to login page and no ajax requests
        $this->oroMainContext->applySkipWait();
    }
}
