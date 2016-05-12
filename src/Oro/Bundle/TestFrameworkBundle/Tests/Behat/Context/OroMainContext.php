<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use SensioLabs\Behat\PageObjectExtension\PageObject\Factory as PageObjectFactory;
use SensioLabs\Behat\PageObjectExtension\Context\PageObjectAware;

/**
 * Defines application features from the specific context.
 */
class OroMainContext extends MinkContext implements
    Context,
    SnippetAcceptingContext,
    PageObjectAware,
    KernelAwareContext
{
    use KernelDictionary;

    /** @var  \SensioLabs\Behat\PageObjectExtension\PageObject\Factory */
    protected $pageObjectFactory;

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
            '%s/%s.png',
            str_replace('/', DIRECTORY_SEPARATOR, __DIR__.'/../../../../../../../../../app/logs/'),
            $scope->getScenario()->getTitle()
        );
        file_put_contents($screenshot, $this->getSession()->getScreenshot());
    }

    /**
     * {@inheritdoc}
     */
    public function setPageObjectFactory(PageObjectFactory $pageObjectFactory)
    {
        $this->pageObjectFactory = $pageObjectFactory;
    }

    /**
     * @Then I should see :title flash message
     */
    public function iShouldSeeFlashMessage($title)
    {
        $this->assertSession()->elementTextContains('css', '.flash-messages-holder', $title);
    }

    /**
     * @Given Login as an existing :login user and :password password
     */
    public function loginAsAnExistingUserAndPassword($login, $password)
    {
        $this->visit('user/login');
        $this->fillField('_username', $login);
        $this->fillField('_password', $password);
        $this->pressButton('_submit');
        $errorBlock = $this->getSession()->getPage()->find('css', '.alert-error');
    }

    /**
     * {@inheritdoc}
     */
    public function pressButton($button)
    {
        try {
            parent::pressButton($button);
            $this->iWaitingForAjaxResponce();
        } catch (ElementNotFoundException $e) {
            if ($this->getSession()->getPage()->hasLink($button)) {
                $this->clickLink($button);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Wait for AJAX to finish.
     *
     * @Given /^(?:|I )waiting for AJAX responce$/
     * @param int $time Time should be in milliseconds
     */
    public function iWaitingForAjaxResponce($time = 15000)
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
     * @Given /^(?:|I )fill "(?P<element>(?:[^"]|\\")*)" with:$/
     */
    public function iFillWith($element, TableNode $table)
    {
        $this->pageObjectFactory->createElement($element)->fill($table);
    }

    /*********************************************/
    /**** Wait for ajax finish for mink steps ****/
    /*********************************************/

    /**
     * {@inheritdoc}
     */
    public function visit($page)
    {
        parent::visit($page);
        $this->iWaitingForAjaxResponce();
    }

    /**
     * {@inheritdoc}
     */
    public function clickLink($link)
    {
        parent::clickLink($link);
        $this->iWaitingForAjaxResponce();
    }
}
