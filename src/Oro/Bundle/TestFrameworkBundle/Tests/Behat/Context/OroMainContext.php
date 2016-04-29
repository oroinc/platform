<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Mink\Exception\ElementNotFoundException;
use Oro\Bundle\TestFrameworkBundle\Behat\FormFiller\FormFiller;
use Oro\Bundle\TestFrameworkBundle\Behat\FormFiller\FormFillerAware;
use SensioLabs\Behat\PageObjectExtension\PageObject\Factory as PageObjectFactory;
use SensioLabs\Behat\PageObjectExtension\Context\PageObjectAware;
use SensioLabs\Behat\PageObjectExtension\PageObject\Page;

/**
 * Defines application features from the specific context.
 */
class OroMainContext extends MinkContext implements Context, SnippetAcceptingContext, PageObjectAware, FormFillerAware
{
    /** @var  \SensioLabs\Behat\PageObjectExtension\PageObject\Factory */
    protected $pageObjectFactory;

    /** @var  FormFiller */
    protected $formFiller;

    /**
     * {@inheritdoc}
     */
    public function setPageObjectFactory(PageObjectFactory $pageObjectFactory)
    {
        $this->pageObjectFactory = $pageObjectFactory;
    }

    public function setFormFiller(FormFiller $formFiller)
    {
        $this->formFiller = $formFiller;
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
        $this->getSession()->wait($time, "'complete' == document['readyState']");
    }

    /**
     * @When /^(?:|I )fill "(?P<formName>(?:[^"]|\\")*)" form with:$/
     */
    public function iFillFormWith($formName, TableNode $table)
    {
        $this->formFiller->fillForm($formName, $this->getSession()->getPage(), $table);
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
