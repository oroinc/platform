<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Mink\Exception\ExpectationException;
use Behat\MinkExtension\Context\RawMinkContext;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid as GridElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactory;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactoryAware;

class GridContext extends RawMinkContext implements OroElementFactoryAware
{
    /**
     * @var int
     */
    protected $gridRecordsNumber;

    /**
     * @var OroElementFactory
     */
    protected $elementFactory;

    /**
     * @param OroElementFactory $elementFactory
     *
     * @return null
     */
    public function setElementFactory(OroElementFactory $elementFactory)
    {
        $this->elementFactory = $elementFactory;
    }

    /**
     * @When I don't select any record from Grid
     */
    public function iDonTSelectAnyRecordFromGrid()
    {
    }

    /**
     * @When /^(?:|I )click "(?P<title>(?:[^"]|\\")*)" link from mass action dropdown$/
     * @When /^(?:|I )click (?P<title>(?:[^"]|\\")*) mass action$/
     */
    public function clickLinkFromMassActionDropdown($title)
    {
        $grid = $this->getGrid();
        $grid->clickMassActionLink($title);
    }

    /**
     * @Given /^(?:|I )keep in mind number of records in list$/
     */
    public function iKeepInMindNumberOfRecordsInList()
    {
        $this->gridRecordsNumber = $this->getGrid()->getRecordsNumber();
    }

    /**
     * @When /^(?:|I )check first (?P<number>(?:[^"]|\\")*) records in grid$/
     * @When select few records
     */
    public function iCheckFirstRecordsInGrid($number = 2)
    {
        $this->getGrid()->checkFirstRecords($number);
    }

    /**
     * @When /^(?:|I )uncheck first (?P<number>(?:[^"]|\\")*) records in grid$/
     */
    public function iUncheckFirstRecordsInGrid($number)
    {
        $this->getGrid()->checkFirstRecords($number);
    }

    /**
     * @Then the number of records decreased by :number
     */
    public function theNumberOfRecordsDecreasedBy($number)
    {
        expect($this->gridRecordsNumber - $number)
            ->toBeEqualTo($this->getGrid()->getRecordsNumber());
    }

    /**
     * @Then the number of records remained the same
     * @Then no records were deleted
     */
    public function theNumberOfRecordsRemainedTheSame()
    {
        expect($this->gridRecordsNumber)
            ->toBeEqualTo($this->getGrid()->getRecordsNumber());
    }

    /**
     * @Given I select :number from per page list dropdown
     */
    public function iSelectFromPerPageListDropdown($number)
    {
        $this->getGrid()->selectPageSize($number);
    }

    /**
     * @When I check All Visible records in grid
     */
    public function iCheckAllVisibleRecordsInGrid()
    {
        $this->getGrid()->massCheck('All visible');
    }

    /**
     * @When /^(?:|I )check all records in grid$/
     */
    public function iCheckAllRecordsInGrid()
    {
        $this->getGrid()->massCheck('All');
    }
    /**
     * @Then there is no records in grid
     * @Then all records should be deleted
     */
    public function thereIsNoRecordsInGrid()
    {
        $this->getGrid()->assertNoRecords();
    }

    /**
     * @Then I shouldn't see :arg1 link from mass action dropdown
     */
    public function iShouldnTSeeLinkFromMassActionDropdown($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Given I should see :arg1 records in grid
     */
    public function iShouldSeeRecordsInGrid($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Given see :arg3 record in grid with :arg1 as :arg2 column
     */
    public function seeRecordInGridWithAsColumn($arg1, $arg2, $arg3)
    {
        throw new PendingException();
    }

    /**
     * @Given /^(?:|I )click (?P<action>(?:[^"]|\\")*) (?P<content>(?:[^"]|\\")*) in grid$/
     */
    public function clickActionInRow($content, $action)
    {
        $this->getGrid()->clickActionLink($content, $action);
    }

    /**
     * @When confirm deletion
     */
    public function confirmDeletion()
    {
        $this->elementFactory->createElement('Modal')->clickLink('Yes, Delete');
    }

    /**
     * @When cancel deletion
     */
    public function cancelDeletion()
    {
        $this->elementFactory->createElement('Modal')->clickLink('Cancel');
    }

    /**
     * @Then I should see success message with number of records were deleted
     */
    public function iShouldSeeSuccessMessageWithNumberOfRecordsWereDeleted()
    {
        $flashMessage = $this->getSession()->getPage()->find('css', '.flash-messages-holder');

        if (!$flashMessage) {
            throw new ExpectationException('Can\'t find flash message', $this->getSession()->getDriver());
        }


        $regex = '/\d+ entities were deleted/';
        expect($flashMessage->getText())->toMatch($regex);
    }

    /**
     * @Then I shouldn't see :action action
     */
    public function iShouldNotSeeDeleteAction($action)
    {
        $grid = $this->getGrid();
        if ($grid->getMassActionLink($action)) {
            throw new ExpectationException(
                sprintf('%s mass action should not be accassable', $action),
                $this->getSession()->getDriver()
            );
        }
    }

    /**
     * @return GridElement
     */
    private function getGrid()
    {
        return $this->elementFactory->createElement('Grid');
    }
}
