<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Mink\Exception\ElementNotFoundException;
use SensioLabs\Behat\PageObjectExtension\PageObject\Factory as PageObjectFactory;
use SensioLabs\Behat\PageObjectExtension\Context\PageObjectAware;
use SensioLabs\Behat\PageObjectExtension\PageObject\Page;

/**
 * Defines application features from the specific context.
 */
class OroMainContext extends MinkContext implements Context, SnippetAcceptingContext, PageObjectAware
{
    /** @var  \SensioLabs\Behat\PageObjectExtension\PageObject\Factory */
    protected $pageObjectFactory;

    /** @var  \SensioLabs\Behat\PageObjectExtension\PageObject\Page */
    protected $currentPage;

    /**
     * {@inheritdoc}
     */
    public function setPageObjectFactory(PageObjectFactory $pageObjectFactory)
    {
        $this->pageObjectFactory = $pageObjectFactory;
    }

    /**
     * @BeforeScenario
     */
    public function beforeScenario(BeforeScenarioScope $scope)
    {
        $this->getSession()->resizeWindow(1920, 1080, 'current');
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
        if ($errorBlock) {
            $errorBlock->find('named', ['content', '×'])->click();
        }
    }

    /**
     * @Given /^(?:|I )open (?:|the )"(?P<pageName>.*?)" page$/
     * @Given /^(?:|I )visited (?:|the )"(?P<pageName>.*?)"$/
     */
    public function iOpenPage($pageName)
    {
        $this->currentPage = $this->pageObjectFactory->createPage($pageName);
        $this->currentPage->open();
        $this->iWaitingForAjaxResponce();
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
        $this->currentPage->getElement($element)->fill($table);
    }

    /**
     * @Given /^(?:|I )should be on "(?P<pageName>(?:[^"]|\\")*)" page?$/
     */
    public function iShouldBeOnPage($pageName)
    {
        $page = $this->pageObjectFactory->createPage($pageName);
        $pattern = $this->getPagePattern($page);
        $this->assertSession()->addressMatches($pattern);
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

    /**
     * @param Page $page
     * @return string
     */
    private function getPagePattern(Page $page)
    {
        $pageReflection = new \ReflectionClass($page);
        $pathReflection = $pageReflection->getProperty('path');
        $pathReflection->setAccessible(true);
        $path = $pathReflection->getValue($page);

        // Replace placeholders like {id} to pattern
        $pattern = preg_replace("/\{[\d\D]*\}/", "[\d\D]*", $path);
        $pattern = sprintf('`^%s$`', $pattern);

        return $pattern;
    }
}
