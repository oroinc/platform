<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\MinkExtension\Context\RawMinkContext;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid as GridElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactory;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactoryAware;

class Grid extends RawMinkContext implements OroElementFactoryAware
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
    {}

    /**
     * @When I click ":title" link from mass action dropdown
     */
    public function clickLinkFromMassActionDropdown($title)
    {
        $grid = $this->getGrid();
        $grid->clickMassActionLink($title);
    }

    /**
     * @Given I keep in mind number of records in list
     */
    public function iKeepInMindNumberOfRecordsInList()
    {
        $this->gridRecordsNumber = $this->getGrid()->getRecordsNumber();
    }

    /**
     * @When /^(?:|I )check first (?P<number>(?:[^"]|\\")*) records in grid$/
     */
    public function iCheckFirstRecordsInGrid($number)
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
     * @When I check all records in grid
     */
    public function iCheckAllRecordsInGrid()
    {
        $this->getGrid()->massCheck('All');
    }
    /**
     * @Then there is no records in grid
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
     * @return GridElement
     */
    private function getGrid()
    {
        return $this->elementFactory->createElement('Grid');
    }
}
