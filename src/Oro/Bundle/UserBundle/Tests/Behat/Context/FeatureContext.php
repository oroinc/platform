<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Oro\Bundle\EmailBundle\Tests\Behat\Context\EmailContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderDictionary;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

/** @SuppressWarnings(PHPMD.TooManyPublicMethods) */
class FeatureContext extends OroFeatureContext implements
    FixtureLoaderAwareInterface,
    OroPageObjectAware
{
    use FixtureLoaderDictionary;
    use PageObjectDictionary;

    /**
     * @var OroMainContext
     */
    private $oroMainContext;

    /**
     * Set only in the suites that register EmailContext. Many suites share this context.
     */
    private ?EmailContext $emailContext = null;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->oroMainContext = $environment->getContext(OroMainContext::class);
        if ($environment->hasContextClass(EmailContext::class)) {
            $this->emailContext = $environment->getContext(EmailContext::class);
        }
    }

    /**
     * Waits for the token of the reset password link. UserPasswordResetRequestProcessor calls updateUser() only
     * after UserManager::sendResetPasswordEmail(), and the Email entity write in between can take more than
     * a second. Thus the mailbox can show the link before the database holds the token, and a step that opens
     * the link at once gets the 404 page.
     *
     * Example: And I wait for the reset password link to become valid
     *
     * @Given /^(?:|I )wait for the reset password link to become valid$/
     */
    public function waitForResetPasswordLinkToBecomeValid(): void
    {
        self::assertNotNull(
            $this->emailContext,
            sprintf('This step requires %s to be registered in the suite', EmailContext::class)
        );

        $url = $this->emailContext->getLinkUrlFromEmail('RESET PASSWORD');
        self::assertNotNull($url, '"RESET PASSWORD" link not found in the email');

        self::assertSame(
            1,
            preg_match('#/reset/(?P<token>\w+)#', $url, $matches),
            sprintf('No reset password token in the link from the email: %s', $url)
        );

        $userManager = $this->getAppContainer()->get('oro_user.manager');
        $stored = $this->spin(
            fn () => null !== $userManager->findUserByConfirmationToken($matches['token']),
            30
        );

        self::assertTrue(
            (bool)$stored,
            sprintf('Reset password token "%s" has not been stored within 30 seconds', $matches['token'])
        );
    }

    /**
     * Open dashboard login page
     * It's generated from 'oro_user_security_login' route name for current application
     *
     * @Given I am on (Login) page
     */
    public function iAmOnLoginPage()
    {
        $uri = $this->getAppContainer()->get('router')->generate('oro_user_security_login');
        $this->visitPath($uri);
    }

    /**
     * Logout user
     *
     * @Given I am logged out
     */
    public function iAmLoggedOut()
    {
        $uri = $this->getAppContainer()->get('router')->generate('oro_user_security_logout');
        $this->visitPath($uri);
    }

    /**
     * Load "user.yml" alice fixture from UserBundle suite
     *
     * @Given Charlie Sheen active user exists in the system
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
        self::assertEquals(
            'Your login was unsuccessful. ' .
            'Please check your e-mail address and password before trying again. ' .
            'If you have forgotten your password, follow "Forgot your password?" link.',
            $error->getText()
        );

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
    }

    /**
     * @When /^(?:|I )open User view page with id (?P<id>[\w\s]+)/
     *
     * @param string $id
     */
    public function openUserViewPage($id)
    {
        $url = $this->getAppContainer()
            ->get('router')
            ->generate('oro_user_view', ['id' => $id]);

        $this->visitPath($url);
        $this->waitForAjax();
    }
}
