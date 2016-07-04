<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Mink\Exception\ExpectationException;
use Behat\MinkExtension\Context\RawMinkContext;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid as GridElement;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridFilterDateTimeItem;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridFilters;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridFilterStringItem;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridPaginator;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactoryAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\ElementFactoryDictionary;

class GridContext extends RawMinkContext implements OroElementFactoryAware
{
    use ElementFactoryDictionary;

    /**
     * @var int
     */
    protected $gridRecordsNumber;

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
     * @Given number of records should be :number
     */
    public function numberOfRecordsShouldBe($number)
    {
        \PHPUnit_Framework_Assert::assertEquals((int) $number, $this->getGridPaginator()->getTotalRecordsCount());
    }

    /**
     * @Given number of pages should be :number
     */
    public function numberOfPagesShouldBe($number)
    {
        \PHPUnit_Framework_Assert::assertEquals((int) $number, $this->getGridPaginator()->getTotalPageCount());
    }

    /**
     * @Given /^(?:|I )keep in mind number of records in list$/
     */
    public function iKeepInMindNumberOfRecordsInList()
    {
        $this->gridRecordsNumber = $this->getGridPaginator()->getTotalRecordsCount();
    }

    /**
     * @Then /^(?:|I )check ([\w\s]*) and ([\w\s]*) in grid$/
     */
    public function checkTwoRecordsInGrid($record1, $record2)
    {
        $this->getGrid()->checkRecord($record1);
        $this->getGrid()->checkRecord($record2);
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
        \PHPUnit_Framework_Assert::assertEquals(
            $this->gridRecordsNumber - $number,
            $this->getGridPaginator()->getTotalRecordsCount()
        );
    }

    /**
     * @Then the number of records remained the same
     * @Then no records were deleted
     */
    public function theNumberOfRecordsRemainedTheSame()
    {
        \PHPUnit_Framework_Assert::assertEquals(
            $this->gridRecordsNumber,
            $this->getGridPaginator()->getTotalRecordsCount()
        );
    }

    /**
     * @Given /^(?:|I )select (?P<number>[\d]+) from per page list dropdown$/
     * @Given /^(?:|I )select (?P<number>[\d]+) records per page$/
     */
    public function iSelectFromPerPageListDropdown($number)
    {
        $this->getGrid()->selectPageSize($number);
    }

    /**
     * @When /^(?:|I )press next page button$/
     */
    public function iPressNextPageButton()
    {
        $this->getGridPaginator()->clickLink('Next');
    }

    /**
     * @Then number of page should be :number
     */
    public function numberOfPageShouldBe($number)
    {
        \PHPUnit_Framework_Assert::assertEquals(
            (int) $number,
            (int) $this->getGridPaginator()->find('css', 'input[type="number"]')->getAttribute('value')
        );
    }

    /**
     * @When /^(?:|I )fill (?P<number>[\d]+) in page number input$/
     */
    public function iFillInPageNumberInput($number)
    {
        $this->getGridPaginator()->find('css', 'input[type="number"]')->setValue($number);
    }

    /**
     * @When /^(?:|when )(?:|I )sort grid by (?P<field>([\w\s]*[^again]))(?:| again)$/
     */
    public function sortGridBy($field)
    {
        $this->elementFactory->createElement('GridHeader')->getHeaderLink($field)->click();
    }

    //@codingStandardsIgnoreStart
    /**
     * @Then /^(?P<column>([\w\s]+)) in (?P<rowNumber1>(first|second|[\d]+)) row must be (?P<comparison>(lower|greater|equal)) then in (?P<rowNumber2>(first|second|[\d]+)) row$/
     */
    //@codingStandardsIgnoreEnd
    public function compareRowValues($column, $comparison, $rowNumber1, $rowNumber2)
    {
        $value1 = $this->getGrid()->getRowValue($column, $this->getNumberFromString($rowNumber1));
        $value2 = $this->getGrid()->getRowValue($column, $this->getNumberFromString($rowNumber2));

        switch ($comparison) {
            case 'lower':
                \PHPUnit_Framework_Assert::assertGreaterThan($value1, $value2);
                break;
            case 'greater':
                \PHPUnit_Framework_Assert::assertLessThan($value1, $value2);
                break;
            case 'equal':
                \PHPUnit_Framework_Assert::assertEquals($value1, $value2);
                break;
        }
    }

    /**
     * @Then /^(?P<content>([\w\s]+)) must be (?P<rowNumber>(first|second|[\d]+)) record$/
     */
    public function assertRowContent($content, $rowNumber)
    {
        $row = $this->getGrid()->getRowByNumber($this->getNumberFromString($rowNumber));
        \PHPUnit_Framework_Assert::assertRegExp(sprintf('/%s/i', $content), $row->getText());
    }

    /**
     * @When /^(?:|I )filter (?P<filterName>([\w\s]+)) as (?P<type>([\w\s]+)) "(?P<value>([\w\s]+))"$/
     */
    public function applyStringFilter($filterName, $type, $value)
    {
        /** @var GridFilterStringItem $filterItem */
        $filterItem = $this->getGridFilters()->getFilterItem('GridFilterStringItem', $filterName);

        $filterItem->activate();
        $filterItem->selectType($type);
        $filterItem->setFilterValue($value);
        $filterItem->submit();
    }

    //@codingStandardsIgnoreStart
    /**
     * @When /^(?:|when )(?:|I )filter (?P<filterName>([\w\s]+)) as (?P<type>(between|not between)) "(?P<start>([\w\s]+))" and "(?P<end>([\w\s]+))"/
     */
    //@codingStandardsIgnoreEnd
    public function appllyDateTimeFilter($filterName, $type, $start, $end)
    {
        /** @var GridFilterDateTimeItem $filterItem */
        $filterItem = $this->getGridFilters()->getFilterItem('GridFilterDateTimeItem', $filterName);

        $filterItem->activate();
        $filterItem->selectType($type);
        $filterItem->setStartTime(new \DateTime($start));
        $filterItem->setEndTime(new \DateTime($end));
        $filterItem->submit();
    }

    /**
     * @When /^(?:|I )check All Visible records in grid$/
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
     * @Given /^(?:|I )click (?P<action>((?!on)\w)*) (?P<content>(?:[^"]|\\")*) in grid$/
     */
    public function clickActionInRow($content, $action)
    {
        $this->getGrid()->clickActionLink($content, $action);
    }

    /**
     * @Given /^(?:|I )click on (?P<content>(?:[^"]|\\")*) in grid$/
     */
    public function clickOnRow($content)
    {
        $this->getGrid()->getRowByContent($content)->click();
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
     * @Then /^(?:|I )should see success message with number of records were deleted$/
     */
    public function iShouldSeeSuccessMessageWithNumberOfRecordsWereDeleted()
    {
        $flashMessage = $this->getSession()->getPage()->find('css', '.flash-messages-holder');

        if (!$flashMessage) {
            throw new ExpectationException('Can\'t find flash message', $this->getSession()->getDriver());
        }


        $regex = '/\d+ entities were deleted/';
        \PHPUnit_Framework_Assert::assertRegExp($regex, $flashMessage->getText());
    }

    /**
     * @Then /^(?:|I )shouldn't see (?P<action>(?:[^"]|\\")*) action$/
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
     * @param string $stringNumber
     * @return int
     */
    private function getNumberFromString($stringNumber)
    {
        switch (trim($stringNumber)) {
            case 'first':
                return 1;
            case 'second':
                return 2;
            default:
                return (int) $stringNumber;
        }
    }

    /**
     * @return GridElement
     */
    private function getGrid()
    {
        return $this->elementFactory->createElement('Grid');
    }

    /**
     * @return GridPaginator
     */
    private function getGridPaginator()
    {
        return $this->elementFactory->createElement('GridPaginator');
    }

    /**
     * @return GridFilters
     */
    private function getGridFilters()
    {
        return $this->elementFactory->createElement('GridFilters');
    }
}
