<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Mink\Exception\ElementNotFoundException;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\FixtureLoader;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\FixtureLoaderAware;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactory;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactoryAware;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use SensioLabs\Behat\PageObjectExtension\PageObject\Factory as PageObjectFactory;

/**
 * Defines application features from the specific context.
 */
class OroMainContext extends MinkContext implements
    SnippetAcceptingContext,
    OroElementFactoryAware,
    KernelAwareContext,
    FixtureLoaderAware
{
    use KernelDictionary;

    /**
     * @var OroElementFactory
     */
    protected $elementFactory;

    /**
     * @var FixtureLoader
     */
    protected $fixtureLoader;

    /** @BeforeStep */
    public function beforeStep(BeforeStepScope $scope)
    {
        $url = $this->getSession()->getCurrentUrl();

        if (1 === preg_match('/^[\S]*\/user\/login\/?$/i', $url)) {
            $this->waitPageToLoad();

            return;
        } elseif ('about:blank' === $url) {
            return;
        }

        $this->waitForAjax();
    }

    /**
     * @BeforeScenario
     */
    public function beforeScenario(BeforeScenarioScope $scope)
    {
        $this->getSession()->resizeWindow(1920, 1080, 'current');
    }

    /**
     * @AfterScenario
     */
    public function afterScenario(AfterScenarioScope $scope)
    {
        if ($scope->getTestResult()->isPassed()) {
            return;
        }

        $screenshot = sprintf(
            '%s/%s-%s-line.png',
            $this->getKernel()->getLogDir(),
            $scope->getFeature()->getTitle(),
            $scope->getScenario()->getLine()
        );
        file_put_contents($screenshot, $this->getSession()->getScreenshot());
    }

    /**
     * {@inheritdoc}
     */
    public function setElementFactory(OroElementFactory $elementFactory)
    {
        $this->elementFactory = $elementFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function setFixtureLoader(FixtureLoader $fixtureLoader)
    {
        $this->fixtureLoader = $fixtureLoader;
    }

    /**
     * @Then I should see :title flash message
     */
    public function iShouldSeeFlashMessage($title)
    {
        $this->assertSession()->elementTextContains('css', '.flash-messages-holder', $title);
    }

    /**
     * @Given /^(?:|I )login as "(?P<login>(?:[^"]|\\")*)" user with "(?P<password>(?:[^"]|\\")*)" password$/
     */
    public function loginAsUserWithPassword($login, $password)
    {
        $this->visit('user/login');
        $this->fillField('_username', $login);
        $this->fillField('_password', $password);
        $this->pressButton('_submit');
    }

    /**
     * {@inheritdoc}
     */
    public function pressButton($button)
    {
        try {
            parent::pressButton($button);
        } catch (ElementNotFoundException $e) {
            if ($this->getSession()->getPage()->hasLink($button)) {
                $this->clickLink($button);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Wait PAGE load
     * @param int $time Time should be in milliseconds
     */
    protected function waitPageToLoad($time = 15000)
    {
        $this->getSession()->wait(
            $time,
            '"complete" == document["readyState"] '.
            '&& (typeof($) != "undefined" '.
            '&& document.title !=="Loading..." '.
            '&& $ !== null '.
            '&& false === $( "div.loader-mask" ).hasClass("shown"))'
        );
    }

    /**
     * Wait AJAX request
     * @param int $time Time should be in milliseconds
     */
    protected function waitForAjax($time = 15000)
    {
        $this->waitPageToLoad($time);

        $jsAppActiveCheck = <<<JS
        (function () {
            var isAppActive = false;
            try {
                if (!window.mediatorCachedForSelenium) {
                    window.mediatorCachedForSelenium = require('oroui/js/mediator');
                }
                isAppActive = window.mediatorCachedForSelenium.execute('isInAction');
            } catch (e) {
                return false;
            }

            return !(jQuery && (jQuery.active || jQuery(document.body).hasClass('loading'))) && !isAppActive;
        })();
JS;
        $this->getSession()->wait($time, $jsAppActiveCheck);
    }

    /**
     * @When /^(?:|I )fill "(?P<formName>(?:[^"]|\\")*)" form with:$/
     */
    public function iFillFormWith($formName, TableNode $table)
    {
        $this->elementFactory->createElement($formName)->fill($table);
    }

    /**
     * @Given /^(?:|I )open the menu "(?P<path>(?:[^"]|\\")*)" (and|then) click "(?P<linkLocator>(?:[^"]|\\")*)"$/
     */
    public function iOpenTheMenuAndClick($path, $linkLocator)
    {
        $this->elementFactory->createElement('MainMenu')->openAndClick($path, $linkLocator);
    }

    /**
     * @Given /^the following ([\w ]+):?$/
     */
    public function theFollowing($name, TableNode $table)
    {
        $this->fixtureLoader->loadTable($name, $table);
    }

    /**
     * @Given /^there (?:is|are) (\d+) ([\w ]+)$/
     */
    public function thereIs($nbr, $name)
    {
        $this->fixtureLoader->loadRandomEntities($name, $nbr);
    }
}
