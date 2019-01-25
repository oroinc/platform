<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\AttachmentBundle\Tests\Behat\Element\AttachmentItem;
use Oro\Bundle\FormBundle\Tests\Behat\Element\OroForm;
use Oro\Bundle\NavigationBundle\Tests\Behat\Element\MainMenu;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\AssertTrait;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\SessionAliasProviderAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\SessionAliasProviderAwareTrait;
use Oro\Bundle\TestFrameworkBundle\Behat\Driver\OroSelenium2Driver;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\CollectionField;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\UIBundle\Tests\Behat\Element\ControlGroup;
use Oro\Bundle\UIBundle\Tests\Behat\Element\EntityStatus;
use Oro\Bundle\UserBundle\Tests\Behat\Element\UserMenu;
use Symfony\Component\Stopwatch\Stopwatch;
use WebDriver\Exception\NoAlertOpenError;
use WebDriver\Exception\NoSuchElement;
use WebDriver\Exception\StaleElementReference;
use WebDriver\Exception\UnknownError;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroMainContext extends MinkContext implements
    SnippetAcceptingContext,
    OroPageObjectAware,
    KernelAwareContext,
    SessionAliasProviderAwareInterface
{
    const SKIP_WAIT_PATTERN = '/'.
        '^(?:|I )should see ".+" flash message$|'.
        '^(?:|I )should see ".+" error message$|'.
        '^(?:|I )should see Schema updated flash message$'.
    '/';

    use AssertTrait, KernelDictionary, PageObjectDictionary, SessionAliasProviderAwareTrait;

    /** @var Stopwatch */
    private $stopwatch;

    /** @var bool */
    private $debug = false;

    /** @var bool */
    private $skipWait = false;

    /**
     * @BeforeScenario
     */
    public function beforeScenario()
    {
        $this->getSession()->resizeWindow(1920, 1080, 'current');
    }

    /**
     * @BeforeScenario @skipWait
     */
    public function applySkipWait()
    {
        $this->skipWait = true;
    }

    /**
     * @AfterScenario @skipWait
     */
    public function cancelSkipWait()
    {
        $this->skipWait = false;
    }

    /** @return Stopwatch */
    private function getStopwatch()
    {
        if (!$this->stopwatch) {
            $this->stopwatch = new Stopwatch();
        }

        return $this->stopwatch;
    }

    /**
     * @BeforeStep
     * @param BeforeStepScope $scope
     */
    public function beforeStepProfile(BeforeStepScope $scope)
    {
        if (!$this->debug) {
            return;
        }

        $this->getStopwatch()->start($scope->getStep()->getText());
    }

    /**
     * @AfterStep
     * @param AfterStepScope $scope
     */
    public function afterStepProfile(AfterStepScope $scope)
    {
        if (!$this->debug) {
            return;
        }

        $eventResult = $this->getStopwatch()->stop($scope->getStep()->getText());
        fwrite(STDOUT, str_pad(sprintf('%s ms', $eventResult->getDuration()), 10));
    }

    /**
     * @BeforeStep
     * @param BeforeStepScope $scope
     */
    public function beforeStep(BeforeStepScope $scope)
    {
        if ($this->skipWait || !$this->getMink()->isSessionStarted()) {
            return;
        }

        $session = $this->getMink()->getSession();

        /** @var OroSelenium2Driver $driver */
        $driver = $session->getDriver();
        try {
            $url = $session->getCurrentUrl();
        } catch (\Exception $e) {
            // there is some age cases when url is not reachable
            return;
        }

        if (1 === preg_match('/[\S]*\/user\/(login|two-factor-auth|reset-request)\/?(\?_rand=[0-9\.]+)?$/i', $url)) {
            return;
        } elseif (0 === preg_match('/^https?:\/\//', $url)) {
            return;
        } elseif (0 !== strpos($url, $this->getMinkParameter('base_url'))) {
            return;
        } elseif (preg_match(self::SKIP_WAIT_PATTERN, $scope->getStep()->getText())) {
            // Don't wait when we need assert the flash message, because it can disappear until ajax in process
            return;
        }

        $driver->waitPageToLoad();
    }

    /**
     * @AfterStep
     * @param AfterStepScope $scope
     */
    public function afterStep(AfterStepScope $scope)
    {
        if ($this->skipWait
            || !$this->getMink()->isSessionStarted()
            || $this->isNextStepNeedSkip($scope->getStep(), $scope->getFeature())
        ) {
            return;
        }

        $session = $this->getMink()->getSession();

        /** @var OroSelenium2Driver $driver */
        $driver = $session->getDriver();
        try {
            $url = $session->getCurrentUrl();
        } catch (\Exception $e) {
            // there is some age cases when url is not reachable
            return;
        }

        if (1 === preg_match('/[\S]*\/user\/(login|two-factor-auth|reset-request)\/?(\?_rand=[0-9\.]+)?$/i', $url)) {
            return;
        } elseif (0 === preg_match('/^https?:\/\//', $url)) {
            return;
        } elseif (0 !== strpos($url, $this->getMinkParameter('base_url'))) {
            return;
        }

        $driver->waitForAjax();

        // Check for unforeseen 500 errors
        $error = $this->elementFactory->findElementContains(
            'Alert Error Message',
            'There was an error performing the requested operation. Please try again or contact us for assistance.'
        );

        if ($error->isIsset()) {
            self::fail(
                sprintf('There is an error message "%s" found on the page, something went wrong', $error->getText())
            );
        }
    }

    /**
     * Returns true if the next step checks for a flash message to bypass wait for ajax on afterStep hook.
     *
     * This helps to overcome a delay introduced by ajax calls after current step execution which prevent flash
     * message check (executed by the next step) to be done before flash message disappears.
     *
     * @param StepNode $currentStep
     * @param FeatureNode $feature
     * @return bool
     */
    private function isNextStepNeedSkip(StepNode $currentStep, FeatureNode $feature): bool
    {
        $isNextStep = false;
        foreach ($feature->getScenarios() as $scenario) {
            foreach ($scenario->getSteps() as $step) {
                if ($isNextStep) {
                    return preg_match(self::SKIP_WAIT_PATTERN, $step->getText());
                }

                $isNextStep = $currentStep->getLine() === $step->getLine();
            }
        }

        return false;
    }

    /**
     * Example: I follow "My Configuration" link within flash message
     *
     * @Then /^(?:|I )follow "(?P<title>[^"]+)" link within flash message "(?P<message>([^"\\]|\\.)*)"$/
     *
     * @param string $title
     * @param string $message
     */
    public function iFollowLinkWithinFlashMessage($title, $message)
    {
        $flashMessage = $this->getFlashMessage($message);

        self::assertNotNull($flashMessage, sprintf(
            'Expected "%s" message didn\'t appear',
            $title
        ));

        if ($flashMessage) {
            $link = $flashMessage->findElementContains('Link', $title);

            self::assertNotNull(
                $link,
                sprintf('Could not find link "%s" within flash message "%s"', $title, $message)
            );

            $link->focus();
            $link->click();
        }
    }

    /**
     * Example: Then I should see "Attachment created successfully" flash message
     * Example: Then I should see "The email was sent" flash message
     *
     * @Then /^(?:|I )should see "(?P<title>[^"]+)" flash message$/
     * @Then /^(?:|I )should see '(?P<title>[^']+)' flash message$/
     *
     * @param string $title
     * @param string $flashMessageElement
     * @param int $timeLimit
     */
    public function iShouldSeeFlashMessage($title, $flashMessageElement = 'Flash Message', $timeLimit = 30)
    {
        $flashMessage = $this->getFlashMessage($title, $flashMessageElement, $timeLimit);

        self::assertNotNull($flashMessage, sprintf(
            'Expected "%s" message didn\'t appear',
            $title
        ));
    }

    /**
     * Example: Then I should not see "Attachment created successfully" flash message
     * Example: Then I should not see "The email was sent" flash message
     *
     * @Then /^(?:|I )should not see "(?P<title>[^"]+)" flash message$/
     * @Then /^(?:|I )should not see '(?P<title>[^']+)' flash message$/
     *
     * @param string $title
     * @param string $flashMessageElement
     * @param int $timeLimit
     */
    public function iShouldNotSeeFlashMessage($title, $flashMessageElement = 'Flash Message', $timeLimit = 30)
    {
        $flashMessage = $this->getFlashMessage($title, $flashMessageElement, $timeLimit);

        self::assertNull($flashMessage, sprintf(
            'Expected that message "%s" won\'t appear',
            $title
        ));
    }

    /**
     * Example: Then I should see "Attachment created successfully" flash message and I close it
     * Example: Then I should see "The email was sent" flash message and I close it
     *
     * @Then /^(?:|I )should see "(?P<title>[^"]+)" flash message and I close it$/
     * @Then /^(?:|I )should see '(?P<title>[^']+)' flash message and I close it$/
     *
     * @param string $title
     * @param string $flashMessageElement
     * @param int $timeLimit
     */
    public function iShouldSeeFlashMessageAndCloseIt($title, $flashMessageElement = 'Flash Message', $timeLimit = 30)
    {
        $flashMessage = $this->getFlashMessage($title, $flashMessageElement, $timeLimit);

        self::assertNotNull($flashMessage, sprintf(
            'Expected "%s" message didn\'t appear',
            $title
        ));

        /** @var NodeElement $closeButton */
        $closeButton = $flashMessage->find('css', '[data-dismiss="alert"]');
        $closeButton->press();
    }

    /**
     * @Then I should not see flash messages
     *
     * @param string $flashMessageElement
     * @param int $timeLimit
     */
    public function shouldNotSeeFlashMessages($flashMessageElement = 'Flash Message', $timeLimit = 30)
    {
        $flashMessages = $this->spin(function (OroMainContext $context) use ($flashMessageElement) {
            return $context->findAllElements($flashMessageElement);
        }, $timeLimit);

        static::assertEmpty($flashMessages);
    }

    /**
     * @param string $title
     * @param string $flashMessageElement
     * @param int $timeLimit
     * @return Element|null
     */
    protected function getFlashMessage($title, $flashMessageElement = 'Flash Message', $timeLimit = 30)
    {
        return $this->spin(
            function (OroMainContext $context) use ($title, $flashMessageElement) {
                $flashMessages = $context->findAllElements($flashMessageElement);

                foreach ($flashMessages as $flashMessage) {
                    if ($flashMessage->isValid() && $flashMessage->isVisible()) {
                        $text = $flashMessage->getText();
                        if (false !== stripos($text, $title) || false !== stripos($text, stripslashes($title))) {
                            return $flashMessage;
                        }
                    }
                }

                return null;
            },
            $timeLimit
        );
    }

    /**
     * @Then /^(?:|I )should see only following flash messages:$/
     *
     * @param TableNode $table
     */
    public function iShouldSeeOnlyFollowingFlashMessages(TableNode $table)
    {
        $this->iShouldSeeFollowingFlashMessages($table, true);
    }

    /**
     * @Then /^(?:|I )should see following flash messages:$/
     *
     * @param TableNode $table
     * @param bool $strict
     */
    public function iShouldSeeFollowingFlashMessages(TableNode $table, $strict = false)
    {
        $expectedMessages = array_map(
            function ($item) {
                return $item[0];
            },
            $table->getRows()
        );

        $actualMessages = [];

        $elements = $this->findAllElements('Flash Message');
        foreach ($elements as $element) {
            if (!$element->isValid() || !$element->isVisible()) {
                continue;
            }

            $actualMessage = $element->getText();

            foreach ($expectedMessages as $message) {
                if (false !== stripos($actualMessage, $message)) {
                    $actualMessage = $message;
                    break;
                }
            }

            $actualMessages[] = $actualMessage;
        }

        $this->assertEquals(
            $expectedMessages,
            array_intersect($expectedMessages, $actualMessages),
            "All messages: \n\t" . implode("\n\t", $actualMessages)
        );

        if ($strict) {
            $this->assertEquals($expectedMessages, $actualMessages);
        }
    }

    /**
     * @Then /^(?:|I )should see (Schema updated) flash message$/
     */
    public function iShouldSeeUpdateSchema()
    {
        $this->iShouldSeeFlashMessage('Schema updated', 'Flash Message', 120);
    }

    /**
     * {@inheritdoc}
     */
    public function assertPageContainsText($text)
    {
        $result = $this->spin(function (OroMainContext $context) use ($text) {
            $context->assertSession()->pageTextContains($this->fixStepArgument($text));

            return true;
        });

        self::assertTrue(
            $result,
            sprintf('The text "%s" was not found anywhere in the text of the current page.', $text)
        );
    }

    /**
     * Checks, that page contains element specified number of times
     * Example: Then I should see 1 element "TextElement"
     * Example: And I should see 3 elements "BlockElement"
     *
     * @Then /^(?:|I )should see (?P<number>\d+) elements? "(?P<elementName>(?:[^"]|\\")*)"$/
     *
     * @param int $number
     * @param string $elementName
     */
    public function assertPageContainsNumElements($number, $elementName)
    {
        $element = $this->createElement($elementName);
        $elements = $this->getSession()->getPage()->findAll('xpath', $element->getXpath());

        static::assertCount(
            (int)$number,
            $elements,
            sprintf('The element "%s" was not found "%d" time(s) in the current page.', $elementName, (int)$number)
        );
    }

    /**
     * Assert form error message
     * Example: Then I should see "At least one of the fields First name, Last name must be defined." error message
     *
     * @Then /^(?:|I should )see "(?P<title>[^"]+)" error message$/
     */
    public function iShouldSeeErrorMessage($title)
    {
        $errorElement = $this->spin(function (MinkContext $context) {
            return $context->getSession()->getPage()->find('css', '.alert-error');
        });

        self::assertNotFalse($errorElement, 'Error message not found on page');
        $message = $errorElement->getText();
        $errorElement->find('css', 'button.close')->press();

        self::assertContains($title, $message, sprintf(
            'Expect that "%s" error message contains "%s" string, but it isn\'t',
            $message,
            $title
        ));
    }

    /**
     * Accepts alert.
     * Example: I accept alert
     *
     * @When /^(?:|I )accept alert$/
     */
    public function iAcceptAlert()
    {
        /** @var Selenium2Driver $driver */
        $driver = $this->getSession()->getDriver();
        $session = $driver->getWebDriverSession();

        for ($tries = 0; $tries < 3; ++$tries) {
            try {
                $session->accept_alert();
            } catch (NoAlertOpenError $exception) {
                usleep(50000);
            } catch (UnknownError $exception) {
                /**
                 * @see https://bugs.chromium.org/p/chromedriver/issues/detail?id=1500
                 */
                usleep(50000);
            }
        }
    }

    /**
     * Assert alert is not present
     * Example: Then I should not see alert
     *
     * @Then I should not see alert
     */
    public function iShouldNotSeeAlert()
    {
        /** @var Selenium2Driver $driver */
        $driver = $this->getSession()->getDriver();
        $session = $driver->getWebDriverSession();

        try {
            $session->accept_alert();
            $alertMessage = $session->getAlert_text();
        } catch (NoAlertOpenError $e) {
            return;
        }

        self::fail('Expect to see no alert but alert with "'.$alertMessage.'" message is present');
    }

    /**
     * Assert alert with text is present
     * Example: Then I should see alert with message "You have unsaved changes"
     *
     * @Then /^(?:|I )should see alert with message "(?P<expectedMessage>[^"]+)"$/
     */
    public function iShouldSeeAlert(string $expectedMessage)
    {
        /** @var Selenium2Driver $driver */
        $driver = $this->getSession()->getDriver();
        $session = $driver->getWebDriverSession();

        try {
            $alertMessage = $session->getAlert_text();

            self::assertEquals(
                $expectedMessage,
                $alertMessage,
                sprintf(
                    'Expected to see alert with message "%s" but alert with "%s" found instead',
                    $expectedMessage,
                    $alertMessage
                )
            );
        } catch (NoAlertOpenError $e) {
            self::fail('Expected to see alert, but it was not found');

            return;
        }
    }

    /**
     * Assert that no malicious scripts present on page
     * Example: Then I should not see malicious scripts
     *
     * @Then I should not see malicious scripts
     */
    public function iShouldNotSeeMaliciousScripts()
    {
        $this->assertPageNotContainsText('<script>');
        $this->assertPageNotContainsText('<Script>');
        $this->assertPageNotContainsText('&lt;script&gt;');
        $this->assertPageNotContainsText('&lt;Script&gt;');
    }

    /**
     * @param \Closure $lambda
     * @param int $timeLimit in seconds
     * @return null|mixed Return null if closure throw error or return not true value.
     *                     Return value that return closure
     */
    public function spin(\Closure $lambda, $timeLimit = 60)
    {
        $time = $timeLimit;

        while ($time > 0) {
            try {
                if ($result = $lambda($this)) {
                    return $result;
                }
            } catch (\Exception $e) {
                // do nothing
            }

            usleep(250000);
            $time -= 0.25;
        }

        return null;
    }

    /**
     * Assert that page hase h1 header
     * Example: And page has "My own custom dashboard" header
     * Example: Then page has "Dashboard" header
     *
     * @Then page has :header header
     */
    public function pageHasHeader($header)
    {
        $this->assertSession()->elementTextContains('css', 'div#container h1', $header);
    }

    /**
     * Close UI dialog popup
     *
     * @Then /^(?:|I )close ui dialog$/
     */
    public function closeUiDialog()
    {
        $this->getSession()->getPage()->find('css', 'button.ui-dialog-titlebar-close')->press();
    }

    /**
     * Open dashboard
     *
     * @Given I am on dashboard
     * @And I am on dashboard
     */
    public function iAmOnDashboard()
    {
        $router = $this->getContainer()->get('router');
        $this->visit($router->generate('oro_default'));
    }

    /**
     * Open dashboard login page and login as existing user
     * Demo user should have password the same as username, e.g. username: charlie, password: charlie
     * Example: Given I login as administrator
     * Example: Given I login as "charlie" user
     *
     * @Given /^(?:|I )login as "(?P<loginAndPassword>(?:[^"]|\\")*)" user$/
     * @Given /^(?:|I )login to dashboard as "(?P<loginAndPassword>(?:[^"]|\\")*)" user$/
     * @Given /^(?:|I )login as administrator$/
     * @Given /^(?:|I )login to dashboard as administrator$/
     *
     * @param string $loginAndPassword
     */
    public function loginAsUserWithPassword($loginAndPassword = 'admin')
    {
        //quick way to logout user (delete all cookies)
        $driver = $this->getSession()->getDriver();
        $driver->reset();

        $this->visit($this->getContainer()->get('router')->generate('oro_default'));
        $this->fillField('_username', $loginAndPassword);
        $this->fillField('_password', $loginAndPassword);
        $this->pressButton('_submit');
    }

    /**
     * Login with credentials under specified session name and session alias
     * Registers an alias switches to specified session and performs the login procedure
     * @see \Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext::switchToActorWindowSession
     *
     * @Given /^(?:|I )login as "(?P<credential>(?:[^"]|\\")*)" and use in (?P<session>\w+) as (?P<alias>\w+)$/
     * @Given /^(?:|I )login as administrator and use in "(?P<session>[^"]*)" as "(?P<alias>[^"]*)"$/
     *
     * @param string $session
     * @param string $alias
     * @param string $credential
     */
    public function loginAsUserWithPasswordInSession($session, $alias, $credential = 'admin')
    {
        $this->sessionAliasProvider->setSessionAlias($this->getMink(), $session, $alias);
        $this->sessionAliasProvider->switchSessionByAlias($this->getMink(), $alias);
        $this->loginAsUserWithPassword($credential);
    }

    /**
     * Example: Given I click My Emails in user menu
     * Example: Given I click My Calendar in user menu
     *
     * @Given /^(?:|I )click (?P<needle>[\w\s]+) in user menu$/
     */
    public function iClickLinkInUserMenu($needle)
    {
        /** @var UserMenu $userMenu */
        $userMenu = $this->createElement('UserMenu');
        self::assertTrue($userMenu->isValid());
        $userMenu->open();
        $userMenu->clickLink($needle);
    }

    /**
     * Example: Given I click "Configure" in "Leads List" widget
     *
     * @Given /^(?:|I )click "(?P<needle>[\w\s]+)" in "(?P<widget>[\w\s]+)" widget$/
     */
    public function iClickLinkInDashboardWidget($needle, $widget)
    {
        $userMenu = $this->createElement($widget);
        self::assertTrue($userMenu->isValid());
        $userMenu->clickLink($needle);
    }

    /**
     * Example: I should see "Leads list" widget on dashboard
     *
     * @Given /^(?:|I )should see "(?P<widget>[\w\s]+)" widget on dashboard$/
     */
    public function iShouldSeeDashboardWidget($widget)
    {
        $widget = $this->createElement($widget);
        self::assertTrue($widget->isValid());
    }

    /**
     * Example: Given I click Websites in sidebar menu
     *
     * @Given /^(?:|I )click (?P<needle>[\w\s]+) in sidebar menu$/
     */
    public function iClickLinkInSidebarMenu($needle)
    {
        $sidebarMenu = $this->createElement('SidebarMenu');
        self::assertTrue($sidebarMenu->isValid());
        $sidebarMenu->clickLink($needle);
    }

    /**
     * Click on element on page
     * Example: When I click on "Help Icon"
     *
     * @When /^(?:|I )click on "(?P<element>[\w\s]+)"$/
     */
    public function iClickOn($element)
    {
        $this->createElement($element)->click();
    }

    /**
     * Example: When I click on "Help Icon" with title "Help"
     *
     * @When /^(?:|I )click on "(?P<selector>[^"]+)" with title "(?P<title>[^"]+)"$/
     *
     * @param string $selector
     * @param string $title
     */
    public function iClickOnElementWithTitle($selector, $title)
    {
        $element = $this->findElementContains($selector, $title);

        self::assertTrue(
            $element->isValid(),
            sprintf('Element "%s" with title "%s" not found on page', $selector, $title)
        );

        $element->click();
    }

    /**
     * Hover on element on page
     * Example: When I hover on "Help Icon"
     *
     * @When /^(?:|I )hover on "(?P<element>[\w\s]+)"$/
     */
    public function iHoverOn($element)
    {
        $this->createElement($element)->mouseOver();
    }

    /**
     * Assert popup with large image on page
     *
     * @Then /^(?:|I )should see large image$/
     */
    public function iShouldSeeLargeImage()
    {
        $largeImage = $this->getSession()->getPage()->find('css', '.lg-image');
        self::assertNotNull($largeImage, 'Large image not visible');
    }

    /**
     * @Then /^(?:|I )close large image preview$/
     */
    public function closeLargeImagePreview()
    {
        $page = $this->getSession()->getPage();
        $page->find('css', '.lg-image')->mouseOver();
        $page->find('css', 'span.lg-close')->click();
    }

    /**
     * Example: When I click on "cat.jpg" attachment thumbnail
     * Example: And I click on "note-attachment.jpg" attachment thumbnail
     *
     * @Then /^(?:|I )click on "(?P<text>[^"]+)" attachment thumbnail$/
     */
    public function commentAttachmentShouldProperlyWork($text)
    {
        /** @var AttachmentItem $attachmentItem */
        $attachmentItem = $this->elementFactory->findElementContains('AttachmentItem', $text);
        self::assertTrue($attachmentItem->isValid(), sprintf('Attachment with "%s" text not found', $text));

        $thumbnail = $this->getPage()->find('css', "div.thumbnail a[title='$text']");
        self::assertTrue($thumbnail->isValid(), sprintf('Thumbnail "%s" not found', $text));

        $thumbnail->click();
    }

    /**
     * Example: Then I should see that "Header" contains "Some Text"
     * @Then /^I should see that "(?P<elementName>[^"]*)" contains "(?P<text>[^"]*)"$/
     *
     * @param string $elementName
     * @param string $text
     */
    public function assertDefinedElementContainsText($elementName, $text)
    {
        $this->waitForAjax();
        $element = $this->elementFactory->createElement($elementName);
        self::assertContains(
            $text,
            $element->getText(),
            sprintf('Element %s does not contains text %s', $elementName, $text)
        );
    }

    /**
     * Example: Then I should see that "Header" does not contain "Some Text"
     * @Then /^I should see that "(?P<elementName>[^"]*)" does not contain "(?P<text>[^"]*)"$/
     *
     * @param string $elementName
     * @param string $text
     */
    public function assertDefinedElementNotContainsText($elementName, $text)
    {
        $this->waitForAjax();
        $element = $this->elementFactory->createElement($elementName);
        self::assertNotContains(
            $text,
            $element->getText(),
            sprintf('Element %s contains text %s', $elementName, $text)
        );
    }

    /**
     * Example: Then I should see that "Header" contains "Some Text" placeholder
     * @Then /^(?:|I )should see that "(?P<elementName>[^"]*)" contains "(?P<text>[^"]*)" placeholder$/
     *
     * @param string $elementName
     * @param string $text
     */
    public function assertDefinedElementContainsPlaceholder($locator, $value)
    {
        $locator = $this->fixStepArgument($locator);
        $value = $this->fixStepArgument($value);
        $field = $this->getPage()->find('named', ['field', $locator]);
        /** @var OroSelenium2Driver $driver */
        $driver = $this->getSession()->getDriver();

        if (null === $field) {
            // try to find field among defined elements
            $field = $this->createElement($locator);
        }
        if (null === $field) {
            throw new ElementNotFoundException(
                $driver,
                'form field',
                'id|name|label|value|element',
                $locator
            );
        }

        self::assertContains(
            $value,
            $field->getAttribute('placeholder'),
            sprintf('Element %s does not contains placeholder %s', $locator, $value)
        );
    }

    /**
     * Example: Then I should see that "Header" does not contain "Some Text" placeholder
     * @Then /^I should see that "(?P<elementName>[^"]*)" does not contain "(?P<text>[^"]*)" placeholder$/
     *
     * @param string $elementName
     * @param string $text
     */
    public function assertDefinedElementNotContainsPlaceholder($elementName, $text)
    {
        $this->waitForAjax();
        $element = $this->elementFactory->createElement($elementName);
        self::assertNotContains(
            $text,
            $element->getAttribute('placeholder'),
            sprintf('Element %s does not contains placeholder %s', $elementName, $text)
        );
    }

    /**
     * Assert that download link in attachment works properly
     * Example: And download link for "cat.jpg" attachment should work
     * Example: And download link for "note-attachment.jpg" attachment should work
     *
     * @Then /^download link for "(?P<text>[^"]+)" attachment should work$/
     */
    public function downloadLinkForAttachmentShouldWork($text)
    {
        /** @var AttachmentItem $attachmentItem */
        $attachmentItem = $this->elementFactory->findElementContains('AttachmentItem', $text);
        self::assertTrue($attachmentItem->isValid(), sprintf('Attachment with "%s" text not found', $text));

        $attachmentItem->checkDownloadLink();
    }

    /**
     * Example: And I should see "UiDialog" with elements:
     *            | Title        | Dialog title   |
     *            | Content      | Dialog content |
     *            | okButton     | Yes, Confirm   |
     *            | cancelButton | Cancel         |
     *
     * @When /^(?:|I )should see "(?P<dialogName>[^"]*)" with elements:$/
     */
    public function iShouldSeeModalWithElements($dialogName, TableNode $table)
    {
        $modals = $this->elementFactory->findAllElements($dialogName);

        $dialog = null;

        foreach ($modals as $modal) {
            if ($modal->isValid() && $modal->isVisible()) {
                $dialog = $modal;
                break;
            }
        }

        self::assertNotNull($dialog, 'There is no modal on page at this moment');

        foreach ($table->getRows() as $row) {
            list($elementName, $expectedTitle) = $row;

            $element = $dialog->findElementContains(sprintf('%s %s', $dialogName, $elementName), $expectedTitle);
            self::assertTrue($element->isValid(), sprintf('Element with "%s" text not found', $expectedTitle));
        }
    }

    /**
     * Example: And I should see "file.jpg" file link with the url matches "#/folder/test.jpg#"
     * Example: And I should see "file.jpg" file link with the url matches "/folder/"
     *
     * @Then /^(?:|I )should see "(?P<text>[^"]+)" link with the url matches (?P<url>"[^"]+")$/
     */
    public function iShouldSeeLinkWithUrl($text, $url)
    {
        $link = $this->elementFactory->findElementContains('Link', $text);
        self::assertRegExp($url, $link->getAttribute('href'));
    }

    /**
     * Example: And I should see "file.jpg" file link with the url matches "/admin/"
     *
     * @Then /^(?:|I )should not see "(?P<text>[^"]+)" link with the url matches (?P<url>"[^"]+")$/
     */
    public function iShouldNotSeeLinkWithUrl($text, $url)
    {
        $link = $this->elementFactory->findElementContains('Link', $text);
        self::assertNotRegExp($url, $link->getAttribute('href'));
    }

    /**
     * Example: And I should see "My Link" button with attributes:
     *            | title | Button title |
     *            | alt   | Button alt   |
     *
     * @When /^(?:|I )should see "(?P<buttonName>[^"]*)" button with attributes:$/
     */
    public function iShouldSeeButtonWithAttributes($buttonName, TableNode $table)
    {
        $button = $this->getSession()->getPage()->findButton($buttonName);
        if (null === $button) {
            $button = $this->getSession()->getPage()->findLink($buttonName);
        }
        if (null === $button) {
            /* @var $driver OroSelenium2Driver */
            $driver = $this->getSession()->getDriver();

            throw new ElementNotFoundException($driver, 'button', 'id|name|title|alt|value', $buttonName);
        }

        foreach ($table->getRows() as $row) {
            list($attributeName, $expectedValue) = $row;
            $attribute = $button->getAttribute($attributeName);

            self::assertNotNull($attribute, sprintf("Attribute with name '%s' not found", $attributeName));
            self::assertContains($expectedValue, $attribute);
        }
    }

    /**
     * Click on button or link
     * Example: Given I click "Edit"
     * Example: When I click "Save and Close"
     *
     * @When /^(?:|I )click "(?P<button>(?:[^"]|\\")*)"$/
     */
    public function pressButton($button)
    {
        for ($i = 0; $i < 2; $i++) {
            try {
                parent::pressButton($button);
                break;
            } catch (ElementNotFoundException $e) {
                $clickLink = $this->spin(function () use ($button) {
                    if ($this->getSession()->getPage()->hasLink($button)) {
                        $this->clickLink($button);
                        return true;
                    }
                    return false;
                }, 1);

                if ($clickLink) {
                    break;
                }

                if ($this->elementFactory->hasElement($button)) {
                    $this->elementFactory->createElement($button)->click();
                    break;
                }

                throw $e;
            } catch (StaleElementReference $e) {
            }
        }
    }

    /**
     * Example: Given I wait 1 second
     * Example: Given I wait 2 seconds
     *
     * @When /^(?:|I )wait (?P<timeout>\d) second(s){0,1}.*$/
     *
     * @param int $timeout
     */
    public function iWait($timeout = 1)
    {
        $this->getSession()->wait($timeout * 1000);
    }

    /**
     * {@inheritdoc}
     */
    public function assertElementContainsText($element, $text)
    {
        $elementObject = $this->createElement($element);
        self::assertTrue($elementObject->isIsset(), sprintf('Element "%s" not found', $element));

        $actual = $elementObject->getText();
        $regex = '/'.preg_quote($text, '/').'/ui';

        $message = sprintf('Failed asserting that "%s" contains "%s"', $text, $actual);

        self::assertTrue((bool) preg_match($regex, $actual), $message, $element);
    }

    /**
     * {@inheritdoc}
     */
    public function assertElementNotContainsText($element, $text)
    {
        $elementObject = $this->createElement($element);
        self::assertTrue($elementObject->isIsset(), sprintf('Element "%s" not found', $element));

        $actual = $elementObject->getText();
        $regex = '/'.preg_quote($text, '/').'/ui';

        $message = sprintf('Failed asserting that "%s" does not contain "%s"', $text, $actual);

        self::assertTrue(!preg_match($regex, $actual), $message, $element);
    }

    /**
     * Example: When I scroll to top
     * @When /^I scroll to top$/
     */
    public function scrollTop()
    {
        $this->getSession()->executeScript('window.scrollTo(0,0);');
    }

    /**
     * Example: When I scroll to bottom
     * @When /^I scroll to bottom/
     */
    public function scrollBottom()
    {
        $this->getSession()->executeScript('window.scrollTo(0,document.body.scrollHeight);');
    }

    /**
     * Click on button in modal window
     * Example: Given I click "Edit" in modal window
     * Example: When I click "Save and Close" in modal window
     * @When /^(?:|I )click "(?P<button>(?:[^"]|\\")*)" in modal window$/
     */
    public function pressButtonInModalWindow($button)
    {
        $modalWindow = $this->getPage()->findVisible('css', 'div.modal, div[role="dialog"]');
        self::assertNotNull($modalWindow, 'There is no visible modal window on page at this moment');
        try {
            $button = $this->fixStepArgument($button);
            $modalWindow->pressButton($button);
        } catch (ElementNotFoundException $e) {
            if ($modalWindow->hasLink($button)) {
                $modalWindow->clickLink($button);
            } else {
                throw $e;
            }
        }
    }

    /**
     * When I scroll modal window to bottom
     *
     * @When /I scroll modal window to bottom/
     */
    public function scrollModalWindowToBottom()
    {
        $modalWindow = $this->getPage()->findVisible('css', 'div.modal, div[role="dialog"]');
        self::assertNotNull($modalWindow, 'There is no visible modal window on page at this moment');
        $function = <<<JS
(function(){
    var scrollableElement = jQuery('section.widget-content').parent();
    scrollableElement.scrollTop(scrollableElement.height());
})()
JS;

        $this->getSession()->executeScript($function);
    }

    /**
     * Navigate through menu navigation
     * Every menu link must be separated by slash symbol "/"
     * Example: Given I go to System/ Channels
     * Example: And go to System/ User Management/ Users
     *
     * @Given /^(?:|I )go to (?P<path>(?:(?!([nN]ewer|[oO]lder) activities)(?!.*page)([^"]*)))$/
     */
    public function iOpenTheMenuAndClick($path)
    {
        /** @var MainMenu $mainMenu */
        $mainMenu = $this->createElement('MainMenu');
        $mainMenu->openAndClick($path);
    }

    /**
     * Select top submenu item in the side menu
     * Example: Given I select "System" in the side menu
     * Example: And select "System" in the side menu
     *
     * @Given /^(?:|I )select "(?P<submenu>[^\"]+)" in the side menu$/
     */
    public function iSelectSideSubmenu(string $submenu)
    {
        /** @var MainMenu $mainMenu */
        $mainMenu = $this->createElement('MainMenu');
        $mainMenu->selectSideSubmenu($submenu);
    }

    /**
     * Assert current page
     * Example: Then I should be on Search Result page
     * Example: Then I should be on Default Calendar View page
     *
     * @Given /^(?:|I )should be on (?P<page>[\w\s\/]+) page$/
     */
    public function assertPage($page)
    {
        $urlPath = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_PATH);
        $urlPath = preg_replace('/^.*\.php/', '', $urlPath);
        $route = $this->getContainer()->get('router')->match($urlPath);

        self::assertEquals($this->getPage($page)->getRoute(), $route['_route']);
    }

    /**
     * Assert current page with its title
     *
     * @Given /^(?:|I )should be on "(?P<entityTitle>[\w\s\/]+)" (?P<page>[\w\s\/]+) ((v|V)iew) page$/
     */
    public function assertViewPage($page, $entityTitle)
    {
        $urlPath = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_PATH);
        $route = $this->getContainer()->get('router')->match($urlPath);

        self::assertEquals($this->getPage($page . ' View')->getRoute(), $route['_route']);

        $actualEntityTitle = $this->getSession()->getPage()->find('css', 'h1.page-title__entity-title');
        self::assertNotNull($actualEntityTitle, sprintf('Entity title not found on "%s" view page', $page));
        self::assertEquals($entityTitle, $actualEntityTitle->getText());
    }

    /**
     * Example: Given I open Opportunity Create page
     * Example: Given I open Account Index page
     *
     * @Given /^(?:|I )open (?P<pageName>[\w\s\/]+) page$/
     */
    public function openPage($pageName)
    {
        $this->getPage($pageName)->open();
    }

    /**
     * Example: Given I open "Charlie" Account edit page
     * Example: When I open "Supper sale" opportunity edit page
     *
     * @Given /^(?:|I )open "(?P<title>[\w\s]+)" (?P<entity>[\w\s]+) edit page$/
     */
    public function openEntityEditPage($title, $entity)
    {
        $pageName = preg_replace('/\s+/', ' ', ucwords($entity)) . ' Edit';
        $this->getPage($pageName)->open(['title' => $title]);
    }

    /**
     * Example: Given I open "Charlie" Account view page
     * Example: When I open "Supper sale" opportunity view page
     *
     * @Given /^(?:|I )open "(?P<title>[\w\s]+)" (?P<entity>[\w\s]+) view page$/
     */
    public function openEntityViewPage($title, $entity)
    {
        $pageName = preg_replace('/\s+/', ' ', ucwords($entity)) . ' View';
        $this->getPage($pageName)->open(['title' => $title]);
    }

    /**
     * @When updated date must be grater then created date
     */
    public function updatedDateMustBeGraterThenCreatedDate()
    {
        /** @var NodeElement[] $records */
        $records = $this->getSession()->getPage()->findAll('css', 'div.navigation div.page-title__path ul li');
        $createdDate = new \DateTime(
            str_replace('Created At: ', '', $records[0]->getText())
        );
        $updatedDate = new \DateTime(
            str_replace('Updated At: ', '', $records[1]->getText())
        );

        self::assertGreaterThan($updatedDate, $createdDate);
    }

    /**
     * Assert entity owner
     * Example: And Harry Freeman should be an owner
     * Example: And Todd Greene should be an owner
     *
     * @When /^([\w\s]*) should be an owner$/
     */
    public function userShouldBeAnOwner($owner)
    {
        self::assertEquals(
            $owner,
            $this->getSession()->getPage()->find('css', '.page-title__entity-info-state li a')->getText()
        );
    }

    /**
     * Click on empty space
     * Example: When I click on empty space
     *
     * @When /^(?:|I )click on empty space$/
     */
    public function clickOnEmptySpace()
    {
        $this->getPage()->find('css', '#container')->click();
    }

    /**
     * Assert text by label in page.
     * Example: Then I should see call with:
     *            | Subject             | Proposed Charlie to star in new film |
     *            | Additional comments | Charlie was in a good mood           |
     *            | Call date & time    | Aug 24, 2017, 11:00 AM               |
     *            | Phone number        | (310) 475-0859                       |
     *            | Direction           | Outgoing                             |
     *            | Duration            | 5:30                                 |
     *
     * @Then /^(?:|I )should see (?P<entity>[\w\s]+) with:$/
     */
    public function assertValuesByLabels($entity, TableNode $table)
    {
        $page = $this->getSession()->getPage();

        foreach ($table->getRows() as $row) {
            list($label, $value) = $row;
            $labelElement = $this->findElementContains('Label', $label);
            $labels = $page->findAll('xpath', $labelElement->getXpath());

            self::assertNotCount(0, $labels, sprintf('Can\'t find "%s" label', $label));

            /** @var NodeElement $labelElement */
            foreach ($labels as $labelElement) {
                $controlLabel = $labelElement->getParent()
                    ->find('css', 'div.attribute-item__description div.control-label');

                if ($controlLabel === null) {
                    continue;
                }

                /** @var ControlGroup $controlLabel */
                $controlLabel = $this->elementFactory->wrapElement(
                    'ControlGroup',
                    $controlLabel
                );

                if (true === $controlLabel->compareValues(Form::normalizeValue($value, $label))) {
                    continue 2;
                }
            }

            self::fail(
                sprintf('Found %s "%s" labels, but no one has "%s" value', count($labels), $label, $value)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function selectOption($select, $option)
    {
        $select = $this->fixStepArgument($select);
        $option = $this->fixStepArgument($option);
        $this->createOroForm()->selectFieldOption($select, $option);
    }

    /**
     * Example: Then I should see "Address Select" with options:
     *            | Value     |
     *            | Address 1 |
     * Example: Then I should see "User Address Select" with options:
     *            | Value                | Type   |
     *            | Organization Address | Group  |
     *            | Address 1            | Option |
     *            | Address 2            | Option |
     *            | User Address         | Group  |
     *            | User Address 1       | Option |
     *
     * @When /^(?:|I )should see "(?P<select>[^"]+)" with options:$/
     */
    public function shouldSeeSelectWithOptions($select, TableNode $options)
    {
        $field = $this->createElement($select);
        $this->assertTrue($field->isValid(), sprintf('Select "%s" not found on page', $select));

        foreach ($options as $option) {
            if (isset($option['Type']) && $option['Type'] === 'Group') {
                $opt = $field->find('named', ['optgroup', $option['Value']]);
                $this->assertNotNull($opt, sprintf('Optgroup with value|text "%s" not found', $option['Value']));
            } else {
                $opt = $field->find('named', ['option', $option['Value']]);
                $this->assertNotNull($opt, sprintf('Options with value|text "%s" not found', $option['Value']));
            }
        }
    }

    /**
     * Assert that select field has no options
     * Example: Then User Address Select has no options
     *
     * @When /^(?P<fieldName>[\w\s]*) has no options$/
     */
    public function selectHasNoOptions($fieldName)
    {
        $field = $this->createElement($fieldName);
        $this->assertTrue($field->isValid(), sprintf('Select "%s" not found on page', $fieldName));

        $options = $field->findAll('css', 'option');
        if (count($options) > 0) {
            $options = array_filter($options, function (NodeElement $option) {
                $value = $option->getValue();

                return !empty($value);
            });
        }
        $this->assertCount(0, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function fillField($field, $value)
    {
        $field = $this->fixStepArgument($field);
        $value = $this->fixStepArgument($value);
        $value = $this->createOroForm()->normalizeValue($value);

        if ($this->elementFactory->hasElement($field)) {
            $this->elementFactory->createElement($field)->setValue($value);

            return;
        }

        $this->createOroForm()->fillField($field, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function assertElementOnPage($element)
    {
        $isVisible = $this->spin(function (OroMainContext $context) use ($element) {
            return $context->createElement($element)->isVisible();
        }, 3);

        self::assertTrue(
            $isVisible,
            sprintf('Element "%s" is not visible, or not present on the page', $element)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function assertElementNotOnPage($element)
    {
        $elementOnPage = $this->createElement($element);
        $result = $this->spin(function (OroMainContext $context) use ($elementOnPage, $element) {
            try {
                return !$elementOnPage->isVisible();
            } catch (NoSuchElement $e) {
                return true;
            }
        }, 3);

        self::assertTrue(
            $result,
            sprintf('Element "%s" is present when it should not', $element)
        );
    }

    /**
     * Scrolls page to first element with given text
     *
     * @When /^(?:|I )scroll to text "(?P<text>(?:[^"]|\\")*)"$/
     */
    public function iScrollToText($text)
    {
        $this->assertPageContainsText($text);
        $element = $this->getPage()->find('named', ['content', $text]);
        if ($element) {
            $element->focus();
        }
    }

    /**
     * Presses button with specified id|name|title|alt|value in some named section
     * Example: When I press "Add" in "General Information" section
     * Example: And I press "Add" in "General Information" section
     *
     * @When /^(?:|I )press "(?P<button>(?:[^"]|\\")*)" in "(?P<section>[^"]+)" section$/
     */
    public function pressButtonInSection($button, $section)
    {
        $button = $this->fixStepArgument($button);
        $section = $this->fixStepArgument($section);
        $page = $this->getSession()->getPage();

        $sectionContainer = $page->find('xpath', '//h4[text()="' . $section . '"]')->getParent();

        if ($sectionContainer->hasButton($button)) {
            $sectionContainer->pressButton($button);
        } else {
            $sectionContainer->clickLink($button);
        }
    }

    /**
     * Remove collection's element in a specified row
     * Example: I remove element in row #2 from Product Price collection
     *
     * @When /^(?:|I )remove element in row #(?P<rowNumber>\d+) from (?P<collectionFieldName>[^"]+) collection$/
     *
     * @param string $collectionFieldName
     * @param int $rowNumber
     */
    public function removeCollectionElement($collectionFieldName, $rowNumber)
    {
        /** @var CollectionField $collection */
        $collection = $this->createOroForm()->findField($collectionFieldName);

        $collection->removeRow($rowNumber);
    }

    /**.
     * @return OroForm
     */
    protected function createOroForm()
    {
        return $this->createElement('OroForm');
    }

    /**
     * @Then /^"([^"]*)" button is disabled$/
     */
    public function buttonIsDisabled($button)
    {
        $element = $this->getSession()->getPage()->findButton($button);
        //Try to find link element with such name if no button found
        if ($element === null) {
            $element = $this->getSession()->getPage()->findLink($button);
        }
        self::assertTrue($element->hasClass('disabled'));
    }

    /**
     * @Then /^"([^"]*)" button is not disabled$/
     */
    public function buttonIsNotDisabled($button)
    {
        $element = $this->getSession()->getPage()->findButton($button);
        //Try to find link element with such name if no button found
        if ($element === null) {
            $element = $this->getSession()->getPage()->findLink($button);
        }
        self::assertFalse($element->hasClass('disabled'));
    }

    /**
     * @Given /^I should see "(?P<string>[^"]*)" in "(?P<elementName>[^"]*)" under "(?P<parentElementName>[^"]*)"$/
     */
    public function iShouldSeeStringInElementUnderElements($string, $elementName, $parentElementName)
    {
        static::assertTrue($this->stringFoundInElements($string, $elementName, $parentElementName), sprintf(
            '`%s` has not been found in any of `%s` elements',
            $string,
            $elementName
        ));
    }

    /**
     * @Given /^I should not see "(?P<string>[^"]*)" in "(?P<elementName>[^"]*)" under "(?P<parentElementName>[^"]*)"$/
     */
    public function iShouldNotSeeStringInElementUnderElements($string, $elementName, $parentElementName)
    {
        static::assertFalse($this->stringFoundInElements($string, $elementName, $parentElementName), sprintf(
            '`%s` has been found in one of `%s` elements',
            $string,
            $elementName
        ));
    }

    /**
     * @param string $string
     * @param string $elementName
     * @param string $parentElementName
     * @return bool
     */
    private function stringFoundInElements($string, $elementName, $parentElementName)
    {
        $allElements = $this->findAllElements($parentElementName);

        $found = false;
        foreach ($allElements as $elementRow) {
            $element = $elementRow->findElementContains($elementName, $string);
            if ($element->isIsset() && strpos(trim($element->getText()), trim($string)) !== false) {
                $found = true;
            }
        }

        return $found;
    }

    /**
     * @param int|string $count
     * @return int
     */
    protected function getCount($count)
    {
        switch (trim($count)) {
            case '':
                return 1;
            case 'one':
                return 1;
            case 'two':
                return 2;
            default:
                return (int)$count;
        }
    }

    /**
     * Sets aliases to the correspond sessions
     * Example: Given sessions:
     * | First User  | first_session  |
     * | Second User | second_session |
     *
     * @Given sessions active:
     * @Given sessions:
     * @Given sessions has aliases:
     * @param TableNode $table
     */
    public function sessionsInit(TableNode $table)
    {
        $mink = $this->getMink();
        foreach ($table->getRows() as list($alias, $name)) {
            $this->sessionAliasProvider->setSessionAlias($mink, $name, $alias);
        }
    }

    /**
     * @Given /^(I |)operate as "(?P<actor>[^"]*)" under "(?P<session>[^"]*)"$/
     * @Given /^here is the "(?P<actor>[^"]*)" under "(?P<session>[^"]*)"$/
     *
     * @param string $actor
     * @param string $session
     */
    public function iOperateAsActorUnderSession($actor, $session)
    {
        $mink = $this->getMink();
        $this->sessionAliasProvider->setSessionAlias($mink, $session, $actor);
        $this->sessionAliasProvider->switchSessionByAlias($mink, $actor);
    }

    /**
     * Switch to named session window (aliases must be initialized earlier)
     * @see \Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext::iOperateAsActorUnderSession
     * @see \Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext::sessionsInit
     * @see \Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext::loginAsUserWithPasswordInSession
     * To define session aliases
     *
     * Example1: I operate as the Manager
     * Example2: I act like a boss
     * Example3: I continue as the Accountant
     * Example4: I proceed as the User
     * Example5: I switch to the "Beginning" session
     *
     * @Then /^I operate as the (\w+)$/
     * @Then /^I act like a (\w+)$/
     * @Then /^I continue as the (\w+)$/
     * @Then /^I proceed as the ([^"]*)$/
     * @Then /^I switch to the "([^"]*)" session$/
     *
     * @param string $sessionAlias
     */
    public function switchToActorWindowSession($sessionAlias)
    {
        $this->sessionAliasProvider->switchSessionByAlias($this->getMink(), $sessionAlias);
        $this->getSession()->resizeWindow(1920, 1080, 'current');
    }

    /**
     * @param int $time
     */
    protected function waitForAjax($time = 60000)
    {
        return $this->getSession()->getDriver()->waitForAjax($time);
    }

    /**
     * Unselect a value in the multiselect box
     * Example: And unselect "Color" option from "Attributes"
     * Example: When I unselect "Color" option from "Attributes"
     *
     * @When /^(?:|I )unselect "(?P<option>(?:[^"]|\\")*)" option from "(?P<select>(?:[^"]|\\")*)"$/
     */
    public function unselectOption($select, $option)
    {
        /** @var OroSelenium2Driver $driver */
        $driver = $this->getSession()->getDriver();

        $field = $this->elementFactory->createElement($select);

        $selectOptionXpath = '//option[contains(.,"%s")]';
        $selectOption = $field->find(
            'xpath',
            sprintf($selectOptionXpath, $option)
        );

        if (null === $selectOption) {
            throw new ElementNotFoundException(
                $driver,
                'select option',
                'id|name|label|value|placeholder',
                sprintf($selectOptionXpath, $option)
            );
        }

        $optionValue = $selectOption->getValue();

        $values = $field->getValue();
        if ($values !== null && is_array($values)) {
            foreach ($values as $key => $value) {
                if ($value === $optionValue) {
                    unset($values[$key]);
                    break;
                }
            }

            $field->setValue(array_values($values));
        }
    }

    /**
     * Confirm schema update and wait for success message
     * Example: And I confirm schema update
     *
     * @Then /^(?:|I )confirm schema update$/
     */
    public function iConfirmSchemaUpdate()
    {
        try {
            $this->pressButton('Update schema');
            $this->assertPageContainsText('Schema update confirmation');
            $this->pressButton('Yes, Proceed');
            $this->iShouldSeeFlashMessage('Schema updated', 'Flash Message', 120);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Checks that element on page exists and visible
     *
     * @param string $selector
     * @return bool
     */
    public function isNodeVisible($selector)
    {
        $element = $this->getPage()->findVisible('css', $selector);

        return !is_null($element);
    }

    /**
     * Drag and Drop one element before another
     * Example: When I drag and drop "Products" before "Clearance"
     *
     * @When /^(?:|I )drag and drop "(?P<elementName>[\w\s]+)" before "(?P<dropZoneName>[\w\s]+)"$/
     * @param string $elementName
     * @param string $dropZoneName
     */
    public function iDragAndDropElementBeforeAnotherOne($elementName, $dropZoneName)
    {
        $this->dragAndDropElementToAnotherOne($elementName, $dropZoneName, 1, 1);
    }

    /**
     * Drag and Drop one element on another
     * Example: When I drag and drop "Field Condition" on "Drop condition here"
     *
     * @When /^(?:|I )drag and drop "(?P<elementName>[\w\s]+)" on "(?P<dropZoneName>[\w\s]+)"$/
     * @param string $elementName
     * @param string $dropZoneName
     */
    public function iDragAndDropElementOnAnotherOne($elementName, $dropZoneName)
    {
        $this->dragAndDropElementToAnotherOne($elementName, $dropZoneName);
    }

    /**
     * @param string $elementName
     * @param string $dropZoneName
     * @param int $xOffset
     * @param int $yOffset
     */
    public function dragAndDropElementToAnotherOne($elementName, $dropZoneName, $xOffset = null, $yOffset = null)
    {
        /** @var Selenium2Driver $driver */
        $driver = $this->getSession()->getDriver();
        $webDriverSession = $driver->getWebDriverSession();

        $element = $this->createElement($elementName);
        $source = $webDriverSession->element('xpath', $element->getXpath());

        $webDriverSession->moveto([
            'element' => $source->getID()
        ]);
        $webDriverSession->buttondown();

        $moveToOptions = ['element' => null];

        if ($dropZoneName) {
            $dropZone = $this->createElement($dropZoneName);
            $destination = $webDriverSession->element('xpath', $dropZone->getXpath());

            $moveToOptions['element'] = $destination->getID();
        }

        if (!is_null($xOffset)) {
            $moveToOptions['xoffset'] = $xOffset;
        }
        if (!is_null($yOffset)) {
            $moveToOptions['yoffset'] = $yOffset;
        }
        $this->waitForAjax();
        $webDriverSession->moveto($moveToOptions);
        $webDriverSession->buttonup();
    }

    /**
     * @Given /^I check element "(?P<elementName>[\w\s]+)" has width "(?P<width>[\w\s]+)"$/
     */
    public function checkElementWidth($elementName, $width = 0)
    {
        /** @var Selenium2Driver $driver */
        $driver = $this->getSession()->getDriver();
        $xpath = $this->createElement($elementName)->getXpath();
        $javascipt = <<<JS
return jQuery(
    document.evaluate("{$xpath}", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue
).outerWidth();
JS;
        self::assertEquals($width, $driver->evaluateScript($javascipt));
    }

    /**
     * Expand node of JS Tree detected by given title
     * Example: When I expand "Retail Supplies" in tree
     *
     * @When /^(?:|I )expand "(?P<nodeTitle>[\w\s]+)" in tree$/
     * @param string $nodeTitle
     */
    public function iExpandNodeInTree($nodeTitle)
    {
        $page = $this->getSession()->getPage();
        $nodeStateControl = $page->find(
            'xpath',
            '//a[contains(., "' . $nodeTitle . '")]/parent::li[contains(@class, "jstree-closed")]'
            . '/i[contains(@class, "jstree-ocl")]'
        );
        if (null !== $nodeStateControl) {
            $nodeStateControl->click();
        }
    }

    /**
     * Check that some JS Tree node located right after another one node
     * Example: Then I see "By Brand" after "New Arrivals" in tree
     *
     * @Then /^(?:|I )should see "(?P<nodeTitle>[\w\s]+)" after "(?P<anotherNodeTitle>[\w\s]+)" in tree$/
     * @param string $nodeTitle
     * @param string $anotherNodeTitle
     */
    public function iSeeNodeAfterAnotherOneInTree($nodeTitle, $anotherNodeTitle)
    {
        $page = $this->getSession()->getPage();
        $resultElement = $page->find(
            'xpath',
            '//a[contains(., "' . $anotherNodeTitle . '")]/parent::li[contains(@class, "jstree-node")]'
            . '/following-sibling::li[contains(@class, "jstree-node")]/a[contains(., "' . $nodeTitle . '")]'
        );

        self::assertNotNull($resultElement, sprintf(
            'Node "%s" not found after "%s" in tree.',
            $nodeTitle,
            $anotherNodeTitle
        ));
    }

    /**
     * @Then /^Page title equals to "(?P<pageTitle>[\w\s-]+)"$/
     *
     * @param string $pageTitle
     */
    public function assertPageTitle($pageTitle)
    {
        $title = $this->getSession()->getPage()->find('css', 'title');

        static::assertNotNull($title, 'Cannot find title element for the page');

        static::assertEquals($pageTitle, $title->getHtml());
    }

    /**
     * Presses enter key for specified id|name|title|alt|value
     * Example: When I focus on "Some" field and press Enter key
     * Example: And I focus on "Some" field and press Enter key
     *
     * @When /^(?:|I )focus on "(?P<fieldName>[^"]*)" field and press Enter key$/
     * @param string $fieldName
     */
    public function focusOnFieldAndPressEnterKey($fieldName)
    {
        $field = $this->createOroForm()->findField($fieldName);
        $field->focus();
        $field->keyDown(13);
        $field->keyUp(13);
        $this->waitForAjax();
    }

    /**
     * @Then /^(?:|I )should see "(?P<title>[\w\s]*)" button$/
     */
    public function iShouldSeeButton($title)
    {
        $button = $this->getPage()->findButton($title);

        if ($button) {
            return;
        }

        $link = $this->getPage()->findLink($title);

        if ($link && $link->hasClass('btn')) {
            return;
        }

        self::fail(sprintf('Could not find button with "%s" title', $title));
    }

    /**
     * Use this action only for debugging
     *
     * This method should be used only for debug
     * @When /^I wait for action$/
     */
    public function iWaitForAction()
    {
        fwrite(STDOUT, "Press [RETURN] to continue...");
        fgets(STDIN, 1024);
    }

    /**
     * Example: Given I set window size to 320x640
     *
     * @Given /^(?:|I )set window size to (?P<width>\d+)x(?P<height>\d+)$/
     * @param int $width
     * @param int $height
     */
    public function iSetWindowSize(int $width = 1920, int $height = 1080)
    {
        $this->getSession()->resizeWindow($width, $height, 'current');
    }

    /**
     * Ensures that given iframe is fully loaded and ready.
     *
     * @Given /^(?:|I )wait for iframe "(?P<iframeElement>[\w\s]*)" to load$/
     *
     * @param string $iframeElement
     *
     * @throws ElementNotFoundException
     */
    public function waitForIframeToLoad($iframeElement)
    {
        $iframeId = $this->elementFactory->createElement($iframeElement)->getAttribute('id');
        $driver = $this->getSession()->getDriver();

        $driver->switchToIFrame($iframeId);
        $driver->waitPageToLoad();
        $driver->switchToWindow();
    }

    /**
     * Example: Then I should see "Map container" element inside "Default Addresses" element
     *
     * @Then I should see :childElementName element inside :parentElementName element
     * @param string $parentElementName
     * @param string $childElementName
     */
    public function iShouldSeeElementInsideElement($childElementName, $parentElementName)
    {
        $parentElement = $this->createElement($parentElementName);
        self::assertTrue($parentElement->isIsset() && $parentElement->isVisible(), sprintf(
            'Parent element "%s" not found on page',
            $parentElementName
        ));

        $childElement = $parentElement->getElement($childElementName);
        self::assertTrue($childElement->isIsset(), sprintf(
            'Element "%s" not found inside element "%s"',
            $childElementName,
            $parentElementName
        ));
        self::assertTrue($childElement->isVisible(), sprintf(
            'Element "%s" found inside element "%s", but it\'s not visible',
            $childElementName,
            $parentElementName
        ));
    }

    /**
     * Example: Then I should not see "Map container" element inside "Default Addresses" element
     *
     * @Then I should not see :childElementName element inside :parentElementName element
     * @param string $parentElementName
     * @param string $childElementName
     */
    public function iShouldNotSeeElementInsideElement($childElementName, $parentElementName)
    {
        $parentElement = $this->createElement($parentElementName);
        self::assertTrue($parentElement->isIsset() && $parentElement->isVisible(), sprintf(
            'Parent element "%s" not found on page',
            $parentElementName
        ));

        $childElement = $parentElement->getElement($childElementName);
        self::assertTrue(!$childElement->isIsset() || !$childElement->isVisible(), sprintf(
            'Element "%s" exists inside element "%s" when it should not',
            $childElementName,
            $parentElementName
        ));
    }

    /**
     * Example: Then I should see "Map container" element inside "Default Addresses" iframe
     *
     * @Then I should see :childElementName element inside :iframeName iframe
     * @param string $iframeName
     * @param string $childElementName
     */
    public function iShouldSeeElementInsideIframe($childElementName, $iframeName)
    {
        $iframeElement = $this->createElement($iframeName);
        self::assertTrue($iframeElement->isIsset() && $iframeElement->isVisible(), sprintf(
            'Iframe element "%s" not found on page',
            $iframeName
        ));

        /** @var OroSelenium2Driver $driver */
        $driver = $this->getSession()->getDriver();
        $driver->switchToIFrameByElement($iframeElement);

        $iframeBody = $this->getSession()->getPage()->find('css', 'body');
        $childElement = $this->elementFactory->createElement($childElementName, $iframeBody);
        self::assertTrue($childElement->isIsset(), sprintf(
            'Element "%s" not found inside iframe',
            $childElementName,
            $iframeName
        ));
        self::assertTrue($childElement->isVisible(), sprintf(
            'Element "%s" found inside iframe, but it\'s not visible',
            $childElementName,
            $iframeName
        ));

        $driver->switchToWindow();
    }

    /**
     * Example: Then I should see "Map container" element with text "Address" inside "Default Addresses" element
     *
     * @Then I should see :childElementName element with text :text inside :parentElementName element
     * @param string $parentElementName
     * @param string $childElementName
     * @param string $text
     */
    public function iShouldSeeElementWithTextInsideElement($childElementName, $parentElementName, $text)
    {
        $parentElement = $this->createElement($parentElementName);
        self::assertTrue($parentElement->isIsset() && $parentElement->isVisible(), sprintf(
            'Parent element "%s" not found on page',
            $parentElementName
        ));

        $childElement = $parentElement->findElementContains($childElementName, $text);
        self::assertTrue($childElement->isIsset(), sprintf(
            'Element "%s" with text "%s" not found inside element "%s"',
            $childElementName,
            $text,
            $parentElementName
        ));
        self::assertTrue($childElement->isVisible(), sprintf(
            'Element "%s" with text "%s" found inside element "%s", but it\'s not visible',
            $childElementName,
            $text,
            $parentElementName
        ));
    }

    /**
     * Example: Then I should not see "Map container" element with text "Address" inside "Default Addresses" element
     *
     * @Then I should not see :childElementName element with text :text inside :parentElementName element
     * @param string $parentElementName
     * @param string $childElementName
     * @param string $text
     */
    public function iShouldNotSeeElementWithTextInsideElement($childElementName, $parentElementName, $text)
    {
        $parentElement = $this->createElement($parentElementName);
        self::assertTrue($parentElement->isIsset() && $parentElement->isVisible(), sprintf(
            'Parent element "%s" not found on page',
            $parentElementName
        ));

        $childElement = $parentElement->findElementContains($childElementName, $text);
        self::assertTrue(!$childElement->isIsset() || !$childElement->isVisible(), sprintf(
            'Element "%s" with text "%s" exists inside element "%s" when it should not',
            $childElementName,
            $text,
            $parentElementName
        ));
    }

    /**
     * Assert link existing on current page
     *
     * @Then /^I should see following buttons:$/
     */
    public function iShouldSeeFollowingButtons(TableNode $table)
    {
        foreach ($table->getRows() as $item) {
            $item = reset($item);
            self::assertNotNull(
                $this->getPage()->findLink($item),
                "Button with name $item not found (link selector, actually)"
            );
        }
    }

    /**
     * Assert that links are not present on current page
     *
     * @Then /^I should not see following buttons:$/
     */
    public function iShouldNotSeeFollowingButtons(TableNode $table)
    {
        foreach ($table->getRows() as $item) {
            $item = reset($item);
            self::assertNull(
                $this->getPage()->findLink($item),
                "Button with name $item still present on page (link selector, actually)"
            );
        }
    }

    /**
     * Scroll element info viewport
     *
     * @When /^(?:|I )scroll to "(?P<elementName>(?:[^"]|\\")*)"$/
     * @When /^(?:|I )focus on "(?P<elementName>(?:[^"]|\\")*)"$/
     */
    public function iScrollToElement($elementName)
    {
        $element = $this->elementFactory->createElement($elementName);
        $element->focus();
    }

    /**
     * Asserts status badge on entity view page
     *
     * Examples: Then I should see "Closed Lost" gray status
     *           Then I should see "Open" green status
     *
     * @Then /^I should see "(?P<status>[^"]+)" (?P<color>[\w\s]+) status$/
     */
    public function iShouldSeeColoredStatus($status, $color)
    {
        /** @var EntityStatus $element */
        $element = $this->createElement('Entity Status');

        self::assertEquals($status, $element->getText());
        self::assertEquals($color, $element->getColor());
    }

    /**
     * {@inheritdoc}
     */
    public function assertPageAddress($page)
    {
        $this->spin(function () use ($page) {
            parent::assertPageAddress($page);
        }, 10);

        parent::assertPageAddress($page);
    }

    /**
     * Returns the name of the current and the last browser tab as an array.
     *
     * @return array [$currentTab, $lastTab]
     */
    public function getCurrentAndLastTabNames()
    {
        $currentTab = $this->getSession()->getWindowName();
        $windowNames = $this->getSession()->getWindowNames();
        $lastTab = end($windowNames);
        return [$currentTab, $lastTab];
    }

    /**
     * Check if the browser opened a new tab.
     *
     * It is based on the assumption that the current window was the last tab before a new tab was opened.
     *
     * Example: Then a new browser tab is opened
     * @Then /^a new browser tab is opened$/
     */
    public function newBrowserTabIsOpened()
    {
        list($currentTab, $lastTab) = $this->getCurrentAndLastTabNames();
        if ($lastTab === $currentTab) {
            self::fail('No new browser tabs detected after the current one');
        }
    }

    /**
     * Check if the browser opened a new tab, and switch to this tab if it is.
     *
     * It is based on the assumption that the current window was the last tab before a new tab was opened.
     *
     * Example: Then a new browser tab is opened and I switch to it
     * @Then /^a new browser tab is opened and I switch to it$/
     */
    public function newBrowserTabIsOpenedAndISwitchToIt()
    {
        list($currentTab, $lastTab) = $this->getCurrentAndLastTabNames();
        if ($lastTab === $currentTab) {
            self::fail('No new browser tabs detected after the current one');
        }
        $this->getSession()->switchToWindow($lastTab);
    }

    /**
     * Asserts that checkbox is checked
     *
     * @Then /^The "(?P<elementName>(?:[^"]|\\")*)" checkbox should be checked$/
     * @param string $elementName
     */
    public function checkboxShouldBeChecked($elementName)
    {
        $element = $this->createElement($elementName);
        self::assertTrue($element->isChecked());
    }

    /**
     * Asserts that checkbox is checked
     *
     * @Then /^The "(?P<elementName>(?:[^"]|\\")*)" checkbox should be unchecked$/
     * @param string $elementName
     */
    public function checkboxShouldBeUnchecked($elementName)
    {
        $element = $this->createElement($elementName);
        self::assertFalse($element->isChecked());
    }

    /**
     * Checks, that form field specified by the element has specified value
     * Example: Then the "username" field element should contain "bwayne"
     * Example: And the "username" field element should contain "bwayne"
     *
     * @Then /^the "(?P<fieldName>(?:[^"]|\\")*)" field element should contain "(?P<value>(?:[^"]|\\")*)"$/
     * @Then /^the "(?P<fieldName>(?:[^"]|\\")*)" field element should contain:$/
     */
    public function assertFieldElementContains($fieldName, $value)
    {
        $fieldName = $this->fixStepArgument($fieldName);
        $value = $this->fixStepArgument($value);

        $field = $this->createElement($fieldName);
        self::assertTrue($field->isIsset(), sprintf(
            'Element "%s" not found on page',
            $fieldName
        ));

        $actual = $field->getValue();
        $regex = '/^'.preg_quote($value, '/').'$/ui';

        $message = sprintf('The field "%s" value is "%s", but "%s" expected.', $fieldName, $actual, $value);

        self::assertTrue((bool) preg_match($regex, $actual), $message);
    }
}
