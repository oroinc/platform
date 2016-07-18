<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\MultipleChoice;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid as GridElement;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridFilterDateTimeItem;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridFilters;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridFilterStringItem;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridHeader;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridPaginator;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactoryAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\ElementFactoryDictionary;

class GridContext extends OroFeatureContext implements OroElementFactoryAware
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
        self::assertEquals((int) $number, $this->getGridPaginator()->getTotalRecordsCount());
    }

    /**
     * @Given number of pages should be :number
     */
    public function numberOfPagesShouldBe($number)
    {
        self::assertEquals((int) $number, $this->getGridPaginator()->getTotalPageCount());
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
        self::assertEquals(
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
        self::assertEquals(
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
        self::assertEquals(
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
                self::assertGreaterThan($value1, $value2);
                break;
            case 'greater':
                self::assertLessThan($value1, $value2);
                break;
            case 'equal':
                self::assertEquals($value1, $value2);
                break;
        }
    }

    /**
     * Assert column values by given row
     * Example: Then I should see Charlie Sheen in grid with following data:
     *            | Email   | charlie@gmail.com   |
     *            | Phone   | +1 415-731-9375     |
     *            | Country | Ukraine             |
     *            | State   | Kharkivs'ka Oblast' |
     *
     * @Then /^(?:|I )should see (?P<content>([\w\s]+)) in grid with following data:$/
     */
    public function assertRowValues($content, TableNode $table)
    {
        /** @var Grid $grid */
        $grid = $this->elementFactory->createElement('Grid');
        /** @var GridHeader $gridHeader */
        $gridHeader = $this->elementFactory->createElement('GridHeader');
        $columns = $grid->getRowByContent($content)->findAll('css', 'td');

        foreach ($table->getRows() as list($header, $value)) {
            $columnNumber = $gridHeader->getColumnNumber($header);
            $actualValue = $columns[$columnNumber]->getText();

            if ($actualValue != $value) {
                throw new ExpectationException(
                    sprintf(
                        'Expect that %s column should be with "%s" value but "%s" found on grid',
                        $header,
                        $value,
                        $actualValue
                    ),
                    $this->getSession()->getDriver()
                );
            }
        }
    }

    /**
     * @Then /^(?P<content>([\w\s]+)) must be (?P<rowNumber>(first|second|[\d]+)) record$/
     */
    public function assertRowContent($content, $rowNumber)
    {
        $row = $this->getGrid()->getRowByNumber($this->getNumberFromString($rowNumber));
        self::assertRegExp(sprintf('/%s/i', $content), $row->getText());
    }

    /**
     * @When /^(?:|I )filter (?P<filterName>([\w\s]+)) as (?P<type>([\w\s]+)) "(?P<value>([\w\s]+))"$/
     */
    public function applyStringFilter($filterName, $type, $value)
    {
        /** @var GridFilterStringItem $filterItem */
        $filterItem = $this->getGridFilters()->getFilterItem('GridFilterStringItem', $filterName);

        $filterItem->open();
        $filterItem->selectType($type);
        $filterItem->setFilterValue($value);
        $filterItem->submit();
    }

    //@codingStandardsIgnoreStart
    /**
     * Filter grid by to dates between or not between
     * Date must be valid format for DateTime php class e.g. 2015-12-24, 2015-12-26 8:30:00, 30 Jun 2015
     * Example: When I filter Date Range as between "2015-12-24" and "2015-12-26"
     * Example: But when I filter Created At as not between "25 Jun 2015" and "30 Jun 2015"
     *
     * @When /^(?:|when )(?:|I )filter (?P<filterName>([\w\s]+)) as (?P<type>(between|not between)) "(?P<start>.+)" and "(?P<end>.+)"$/
     */
    //@codingStandardsIgnoreEnd
    public function appllyDateTimeFilter($filterName, $type, $start, $end)
    {
        /** @var GridFilterDateTimeItem $filterItem */
        $filterItem = $this->getGridFilters()->getFilterItem('GridFilterDateTimeItem', $filterName);

        $filterItem->open();
        $filterItem->selectType($type);
        $filterItem->setStartTime(new \DateTime($start));
        $filterItem->setEndTime(new \DateTime($end));
        $filterItem->submit();
    }

    /**
     * Check checkboxes in multiple select filter
     * Example: When I check "Task, Email" in Activity Type filter
     *
     * @When /^(?:|I )check "(?P<filterItems>.+)" in (?P<filterName>([\w\s]+)) filter$/
     */
    public function iCheckCheckboxesInFilter($filterName, $filterItems)
    {
        /** @var MultipleChoice $filterItem */
        $filterItem = $this->getGridFilters()->getFilterItem('MultipleChoice', $filterName);
        $filterItems = array_map('trim', explode(',', $filterItems));

        $filterItem->checkItems($filterItems);
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
     * Click on row in grid
     * Example: When click on Charlie in grid
     *
     * @Given /^(?:|I )click on (?P<content>(?:[^"]|\\")*) in grid$/
     */
    public function clickOnRow($content)
    {
        $this->getGrid()->getRowByContent($content)->click();
        // Keep this check for sure that ajax is finish
        $this->getSession()->getDriver()->waitForAjax();
    }

    /**
     * @When /^(?:|I )confirm deletion$/
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
        self::assertRegExp($regex, $flashMessage->getText());
    }

    /**
     * Check that mass action link is not available in grid mass actions
     * Example: Then I shouldn't see Delete action
     *
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
