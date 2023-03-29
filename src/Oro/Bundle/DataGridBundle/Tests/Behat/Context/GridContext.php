<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\DateTimePicker;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridColumnManager;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridFilterChoiceTree;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridFilterDateTimeItem;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridFilterManager;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridFilterPriceItem;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridFilters;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridFilterStringItem;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridInterface;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridPaginator;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridRow;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridToolBarTools;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\MultipleChoice;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableHeader;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableRow;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class GridContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @var OroMainContext
     */
    private $oroMainContext;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->oroMainContext = $environment->getContext(OroMainContext::class);
    }

    /**
     * @var int[]
     */
    protected $gridRecordsNumber = [];

    /**
     * @param string|null $gridName
     * @param string|null $content
     * @return GridInterface|Table|Element
     */
    private function getGrid($gridName = null, $content = null)
    {
        if ($gridName === null) {
            $gridName = 'Grid';
        }

        if ($content !== null) {
            $grid = $this->elementFactory->findElementContains($gridName, $content);
        } else {
            $grid = $this->elementFactory->createElement($gridName);
        }

        self::assertTrue($grid->isIsset(), sprintf('Element "%s" not found on the page', $gridName));

        return $grid;
    }

    /**
     * @When I don't select any record from Grid
     * @When /^I don't select any record from "(?P<gridName>[^"]+)"$/
     */
    public function iDonTSelectAnyRecordFromGrid($gridName = null)
    {
        // No need to do anything
    }

    /**
     * Mass inline grid field edit
     * Accept table and pass it to inlineEditField
     * Example: When I edit first record from grid:
     *            | name      | editedName       |
     *            | status    | Qualified        |
     *
     * @Then I edit first record from grid:
     */
    public function iEditFirstRecordFromGrid(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            [$field, $value] = $row;
            $this->inlineEditField($field, $value);
            $this->waitForAjax();
        }
    }

    /**
     * Inline grid field edit with click on empty space
     * Example: When I edit Status as "Open"
     * Example: Given I edit Probability as "30"
     *
     * @When /^(?:|I )edit (?P<field>[^"]+) as "(?P<value>.*)" with click on empty space$/
     * @When /^(?:|I )edit "(?P<entityTitle>[^"]+)" (?P<field>.+) as "(?P<value>.*)" with click on empty space$/
     */
    public function inlineEditRecordInGridWithClickOnEmptySpace($field, $value, $entityTitle = null)
    {
        $row = $this->getGridRow($entityTitle);

        $row->setCellValue($field, $value);
        // click any where on the page
        $this->getPage()->find('css', '#container')->click();
        $this->oroMainContext->iShouldSeeFlashMessage('Record has been successfully updated');
    }

    /**
     * Inline grid field edit by double click
     * Example: When I edit Status as "Open"
     * Example: Given I edit Probability as "30"
     *
     * @When /^(?:|I )edit (?P<field>[^"]+) as "(?P<value>.*)" by double click$/
     * @When /^(?:|I )edit "(?P<entityTitle>[^"]+)" (?P<field>.+) as "(?P<value>.*)" by double click$/
     */
    public function inlineEditRecordInGridByDoubleclick($field, $value, $entityTitle = null)
    {
        $row = $this->getGridRow($entityTitle);

        $row->setCellValueByDoubleClick($field, $value);
    }

    /**
     * Start inline editing in grid without changing the value and assert inline editor value
     *
     * Example: When I start inline editing on "Test Warehouse" Quantity field I should see "10000000" value
     *
     * @param string|null $field
     * @param string|null $value
     * @param string|null $entityTitle
     *
     * @When /^(?:|I )start inline editing on "(?P<field>[^"]+)" field I should see "(?P<value>.*)" value$/
     * @codingStandardsIgnoreStart
     * @When /^(?:|I )start inline editing on "(?P<entityTitle>[^"]+)" "(?P<field>.+)" field I should see "(?P<value>.*)" value$/
     * @codingStandardsIgnoreEnd
     */
    public function startInlineEditingAndAssertEditorValue($field, $value, $entityTitle = null)
    {
        $row = $this->getGridRow($entityTitle);
        $cell = $row->startInlineEditing($field);
        $inlineEditor = $cell->findField('value');

        self::assertEquals($value, $inlineEditor->getValue());
        $cell->find('css', 'button[title="Cancel"]')->click();
    }

    /**
     * @param string|null $entityTitle
     * @param string|null $gridName
     * @return GridRow
     */
    protected function getGridRow($entityTitle = null, $gridName = null)
    {
        $grid = $this->getGrid($gridName);

        if (null !== $entityTitle) {
            $row = $grid->getRowByContent($entityTitle);
        } else {
            $rows = $grid->getRows();
            self::assertCount(1, $rows, sprintf('Expect one row in grid but got %s.' .
                PHP_EOL . 'You can specify row content for edit field in specific row.', count($rows)));

            $row = array_shift($rows);
        }

        return $row;
    }

    /**
     * Inline edit field
     * Example: When I edit Status as "Open"
     * Example: Given I edit Probability as "30"
     *
     * @When /^(?:|I )edit (?P<field>[^"]+) as "(?P<value>.*)"$/
     * @When /^(?:|I )edit "(?P<entityTitle>[^"]+)" (?P<field>.+) as "(?P<value>.*)"$/
     */
    public function inlineEditField($field, $value, $entityTitle = null)
    {
        $row = $this->getGridRow($entityTitle);

        $row->setCellValueAndSave($field, $value);
        $this->oroMainContext->iShouldSeeFlashMessage('Record has been successfully updated');
    }

    /**
     * Inline edit field and don't save
     * Example: When I edit Status as "Open" without saving
     * Example: Given I edit Probability as "30" without saving
     *
     * @When /^(?:|I )edit (?P<field>[^"]+) as "(?P<value>.*)" without saving$/
     * @When /^(?:|I )edit "(?P<entityTitle>[^"]+)" (?P<field>.+) as "(?P<value>.*)" without saving$/
     */
    public function inlineEditFieldWithoutSaving($field, $value, $entityTitle = null)
    {
        $row = $this->getGridRow($entityTitle);

        $row->setCellValue($field, $value);
    }

    /**
     * Inline edit field and cancel
     * Example: When I edit Status as "Open" and cancel
     * Example: Given I edit Probability as "30" and cancel
     *
     * @When /^(?:|I )edit (?P<field>[^"]+) as "(?P<value>.*)" and cancel$/
     * @When /^(?:|I )edit "(?P<entityTitle>[^"]+)" (?P<field>.+) as "(?P<value>.*)" and cancel$/
     */
    public function inlineEditFieldAndCancel($field, $value, $entityTitle = null)
    {
        $row = $this->getGridRow($entityTitle);

        $row->setCellValueAndCancel($field, $value);
    }

    /**
     * Example: And I should see following grid:
     *   | First name | Last name | Primary Email     | Enabled | Status {{ "type": "array", "separator": ";" }} |
     *   | John       | Doe       | admin@example.com | Enabled | Active; Reviewed                               |
     *
     * @Then /^(?:|I )should see following grid:$/
     * @Then /^(?:|I )should see following "(?P<gridName>[^"]+)" grid:$/
     */
    public function iShouldSeeFollowingGrid(TableNode $table, $gridName = null)
    {
        $this->waitForAjax();
        $grid = $this->getGrid($gridName);
        $hiddenRowsCount = 0;

        foreach ($table as $index => $row) {
            $rowNumber = $index + $hiddenRowsCount + 1;
            foreach ($row as $columnTitle => $value) {
                [$value, $cellValue, $columnTitle] = $this->normalizeValueByMetadata(
                    $value,
                    $grid,
                    $rowNumber,
                    $columnTitle
                );

                if (!$grid->getRowByNumber($rowNumber)->isVisible()) {
                    $hiddenRowsCount = $hiddenRowsCount + 1;
                    $rowNumber = $rowNumber + 1;
                    continue;
                }

                self::assertEquals(
                    $value,
                    $cellValue,
                    sprintf('Unexpected value at %d row "%s" column in grid', $rowNumber, $columnTitle)
                );
            }
        }
    }

    /**
     * Example: And I should see following grid containing rows:
     *   | First name | Last name | Primary Email     | Enabled |
     *   | John       | Doe       | admin@example.com | Enabled |
     *
     * @Then /^(?:|I )should see following grid containing rows:$/
     * @Then /^(?:|I )should see following "(?P<gridName>[^"]+)" grid containing rows:$/
     */
    public function iShouldSeeFollowingGridWithRowsInAnyOrder(TableNode $table, $gridName = null)
    {
        $this->waitForAjax();
        $grid = $this->getGrid($gridName);

        $expected = [];
        foreach ($table as $row) {
            $expected[] = array_map(static function ($value) {
                return TableRow::normalizeValueByGuessingType($value);
            }, $row);
        }

        $headers = [];
        if ($expected) {
            $headers = array_keys(reset($expected));
        }
        $actualRows = array_map(function (GridRow $row) use ($headers) {
            return array_combine($headers, $row->getCellValues($headers));
        }, $grid->getRows());

        foreach ($expected as $expectedRow) {
            self::assertContainsEquals($expectedRow, $actualRows);
        }
    }

    /**
     * Example: And I should see following grid with exact columns order:
     *            | First name | Last name | Primary Email     | Enabled | Status |
     *            | John       | Doe       | admin@example.com | Enabled | Active |
     *
     * @Then /^(?:|I )should see following grid with exact columns order:$/
     * @Then /^(?:|I )should see following "(?P<gridName>[^"]+)" grid with exact columns order:$/
     */
    public function iShouldSeeFollowingGridWithOrder(TableNode $table, $gridName = null)
    {
        $this->waitForAjax();
        $grid = $this->getGrid($gridName);
        $tableHeaders = $table->getRow(0);
        $gridHeader = $grid->getHeader();
        $indexOffset = $gridHeader->hasMassActionColumn() ? 1 : 0;

        foreach ($tableHeaders as $tableHeaderIndex => $tableHeaderValue) {
            self::assertEquals(
                $tableHeaderIndex + $indexOffset,
                $gridHeader->getColumnNumber($tableHeaderValue),
                'Wrong order of columns in grid'
            );
        }

        $this->iShouldSeeFollowingGrid($table, $gridName);
    }

    /**
     * Example: And It should be exactly 3 columns in grid
     *
     * @Then /^It should be (?P<count>.+) columns in grid$/
     * @Then /^It should be (?P<count>.+) columns in "(?P<gridName>[^"]+)" grid$/
     * And It should be exactly 3 columns in grid
     */
    public function itShouldBeColumnsInGrid(int $count, ?string $gridName = null)
    {
        $gridHeader = $this->getGrid($gridName)->getHeader();
        self::assertSame($count, $gridHeader->getColumnsCount());
    }

    /**
     * Example: When I click "Delete" link from mass action dropdown
     * Example: And click Delete mass action
     *
     * @When /^(?:|I )click "(?P<title>(?:[^"]|\\")*)" link from mass action dropdown$/
     * @When /^(?:|I )click "(?P<title>(?:[^"]|\\")*)" link from mass action dropdown in "(?P<gridName>[^"]+)"$/
     * @When /^(?:|I )click (?P<title>(?:[^"]|\\")*) mass action$/
     * @When /^(?:|I )click (?P<title>(?:[^"]|\\")*) mass action in "(?P<grid>[\w\s]+)" grid$/
     *
     * @param string $title
     * @param string|null $gridName
     */
    public function clickLinkFromMassActionDropdown($title, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $grid->clickMassActionLink($title);
    }

    //@codingStandardsIgnoreStart
    /**
     * Example: When I click "Delete" link from mass action dropdown
     * Example: And click Delete mass action
     *
     * @When /^(?:|I )click "(?P<title>(?:[^"]|\\")*)" link from select all mass action dropdown$/
     * @When /^(?:|I )click "(?P<title>(?:[^"]|\\")*)" link from select all mass action dropdown in "(?P<gridName>[^"]+)"$/
     */
    //@codingStandardsIgnoreEnd
    public function clickSelectAllLinkFromMassActionDropdown($title, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $grid->clickSelectAllMassActionLink($title);
    }

    /**
     * Example: Then there is one record in grid
     * Example: And there are two records in grid
     * Example: And there are 7 records in grid
     * Example: And number of records should be 34
     *
     * @Given number of records should be :number
     * @Given /^number of records in "(?P<gridName>[^"]+)"( grid)? should be (?P<number>(?:|zero|one|two|\d+))$/
     * @Given /^there (?:|are|is) (?P<number>(?:|zero|one|two|\d+)) record(?:|s) in grid$/
     */
    public function numberOfRecordsShouldBe($number, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        self::assertEquals($this->getCount($number), $this->getGridPaginator($grid)->getTotalRecordsCount());
    }

    /**
     * Example: I should see not empty grid
     *
     * @Given /^I should see not empty grid "(?P<gridName>[^"]+)"$/
     * @Given /^I should see not empty grid$/
     */
    public function gridShouldNotBeEmpty($number, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        self::assertGreaterThan(0, $this->getGridPaginator($grid)->getTotalRecordsCount());
    }


    /**
     * Example: Then number of pages should be 3
     * Example: Then number of pages should be 15
     *
     * @Given number of pages should be :number
     * @Given number of pages in "(?P<gridName>[^"]+)" should be :number
     */
    public function numberOfPagesShouldBe($number, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        self::assertEquals((int)$number, $this->getGridPaginator($grid)->getTotalPageCount());
    }

    /**
     * This step used for compare number of records after some actions
     *
     * @Given /^(?:|I )keep in mind number of records in list$/
     * @Given /^(?:|I )keep in mind number of records in list in "(?P<gridName>[^"]+)"$/
     */
    public function iKeepInMindNumberOfRecordsInList($gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $this->gridRecordsNumber[$gridName] = $this->getGridPaginator($grid)->getTotalRecordsCount();
    }

    /**
     * @Then /^(?:|I )check (?P<content>\S+) record in grid$/
     * @Then /^(?:|I )check (?P<content>\S+) record in "(?P<gridName>[^"]+)" grid$/
     * @Then /^(?:|I )check (?P<content>\S+) record in "(?P<gridName>[^"]+)"$/
     *
     * @param string $content
     * @param string|null $gridName
     */
    public function checkRecordInGrid($content, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $grid->checkRecord($content);
    }

    /**
     * @When /^(?:|I )check records in grid:$/
     * @When /^(?:|I )check records in "(?P<gridName>[^"]+)":$/
     * @When /^(?:|I )check records in "(?P<gridName>[^"]+)" grid:$/
     *
     * @param TableNode $table
     * @param string $gridName
     */
    public function iCheckRecordsInGrid(TableNode $table, ?string $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        if (!count($grid->getRows())) {
            self::fail('Grid has no records to check');
        }
        foreach ($table->getRows() as $row) {
            $first = reset($row);
            $grid->checkRecord($first);
        }
    }

    /**
     * @Then /^(?:|I )uncheck (?P<content>\S+) record in grid$/
     * @Then /^(?:|I )uncheck (?P<content>\S+) record in "(?P<gridName>[^"]+)" grid$/
     * @Then /^(?:|I )uncheck (?P<content>\S+) record in "(?P<gridName>[^"]+)"$/
     *
     * @param string $content
     * @param string|null $gridName
     */
    public function uncheckRecordInGrid($content, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $grid->uncheckRecord($content);
    }

    /**
     * Check two records in grid by one step
     * E.g. to check check accounts with "Columbia Pictures" and "Warner Brothers" content in it
     * Example: And check Warner Brothers and Columbia Pictures in grid
     *
     * @Then /^(?:|I )check ([\w\s]*) and ([\w\s]*) in grid$/
     * @Then /^(?:|I )check ([\w\s]*) and ([\w\s]*) in "(?P<gridName>[^"]+)"$/
     */
    public function checkTwoRecordsInGrid($record1, $record2, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $grid->checkRecord($record1);
        $grid->checkRecord($record2);
    }

    /**
     * I select few records == I check first 2 records in grid
     * Example: When I check first 2 records in grid
     * Example: I select few records
     *
     * @When /^(?:|I )check first (?P<number>(?:[^"]|\\")*) records in grid$/
     * @When /^(?:|I )check first (?P<number>(?:[^"]|\\")*) records in "(?P<gridName>[^"]+)"$/
     * @When select few records
     * @When /^select few records in "(?P<gridName>[^"]+)"$/
     */
    public function iCheckFirstRecordsInGrid($number = 2, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $grid->checkFirstRecords($number);
    }

    //@codingStandardsIgnoreStart
    /**
     * Checks first records in provided column number
     * Example: And I check first 5 records in 1 column
     *
     * @When /^(?:|I )check first (?P<number>(?:|one|two|\d+)) record(?:|s|) in (?P<column>(?:|one|two|\d+)) column$/
     * @When /^(?:|I )check first (?P<number>(?:|one|two|\d+)) record(?:|s|) in (?P<column>(?:|one|two|\d+)) column in "(?P<gridName>[^"]+)"$/
     */
    //@codingStandardsIgnoreEnd
    public function iCheckRecordsInColumn($number, $column, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $grid->checkFirstRecords(
            $this->getCount($number),
            $this->getCount($column)
        );
    }

    //@codingStandardsIgnoreStart
    /**
     * Unchecks first records in provided column number
     * Example: And I uncheck first 2 records in 1 column
     *
     * @When /^(?:|I )uncheck first (?P<number>(?:[^"]|\\")*) records in (?P<column>(?:[^"]|\\")*) column$/
     * @When /^(?:|I )uncheck first (?P<number>(?:[^"]|\\")*) records in (?P<column>(?:[^"]|\\")*) column in "(?P<gridName>[^"]+)"$/
     */
    //@codingStandardsIgnoreEnd
    public function iUncheckFirstRecordsInColumn($number, $column, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $grid->uncheckFirstRecords($number, $column);
    }

    /**
     * Example: And I uncheck first 2 records in grid
     *
     * @When /^(?:|I )uncheck first (?P<number>(?:[^"]|\\")*) records in grid$/
     * @When /^(?:|I )uncheck first (?P<number>(?:[^"]|\\")*) records in "(?P<gridName>[^"]+)"$/
     */
    public function iUncheckFirstRecordsInGrid($number, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $grid->uncheckFirstRecords($number);
    }

    /**
     * Check how much records was deleted after some actions
     * Example: Given go to Customers/ Accounts
     *          And I keep in mind number of records in list
     *          When I check first 2 records in grid
     *          And I click "Delete" link from mass action dropdown
     *          Then the number of records decreased by 2
     *
     * @Then the number of records decreased by :number
     * @Then /^the number of records in "(?P<gridName>[^"]+)" decreased by (?P<number>\d+)$/
     */
    public function theNumberOfRecordsDecreasedBy($number, $gridName = null)
    {
        $this->getSession()->getDriver()->waitForAjax();
        $grid = $this->getGrid($gridName);
        self::assertEquals(
            $this->gridRecordsNumber[$gridName] - $number,
            $this->getGridPaginator($grid)->getTotalRecordsCount()
        );
    }

    /**
     * @Then the number of records greater than or equal to :number
     * @Then /^the number of records in "(?P<gridName>[^"]+)" greater than or equal to (?P<number>\d+)/
     */
    public function theNumberOfRecordsGreaterThanOrEqual($number, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        self::assertGreaterThanOrEqual(
            $number,
            $this->getGridPaginator($grid)->getTotalRecordsCount()
        );
    }

    /**
     * @Then the number of records remained the same
     * @Then /^the number of records in "(?P<gridName>[^"]+)" remained the same$/
     * @Then no records were deleted
     * @Then /^no records were deleted from "(?P<gridName>[^"]+)"$/
     */
    public function theNumberOfRecordsRemainedTheSame($gridName = null)
    {
        $grid = $this->getGrid($gridName);
        self::assertEquals(
            $this->gridRecordsNumber[$gridName],
            $this->getGridPaginator($grid)->getTotalRecordsCount()
        );
    }

    /**
     * Example: And I select 10 from per page list dropdown
     *
     * @Given /^(?:|I )select (?P<number>[\d]+) from per page list dropdown$/
     * @Given /^(?:|I )select (?P<number>[\d]+) from per page list dropdown in "(?P<gridName>[^"]+)"$/
     * @Given /^(?:|I )select (?P<number>[\d]+) records per page$/
     * @Given /^(?:|I )select (?P<number>[\d]+) records per page in "(?P<gridName>[^"]+)"$/
     */
    public function iSelectFromPerPageListDropdown($number, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $grid->selectPageSize($number);
    }

    /**
     * Proceed forward oro grid pagination
     *
     * @When /^(?:|I )press next page button$/
     * @When /^(?:|I )press next page button in "(?P<gridName>[^"]+)"$/
     */
    public function iPressNextPageButton($gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $this->getGridPaginator($grid)->pressButton('Next');
    }

    /**
     * Proceed backward oro grid pagination for specific grid.
     *
     * @When /^(?:|I )press previous page button in grid "(?P<gridName>([\w\s]+))"$/
     */
    public function iPressPreviousPageButtonInGrid($gridName = null)
    {
        $this->pressPaginationControlButton('Prev', $gridName);
    }

    /**
     * Proceed forward oro grid pagination for specific grid.
     *
     * @When /^(?:|I )press next page button in grid "(?P<gridName>[^"]+)"$/
     */
    public function iPressNextPageButtonInGrid($gridName = null)
    {
        $this->pressPaginationControlButton('Next', $gridName);
    }

    /**
     * Assert number of pages in oro grid
     * It depends on per page and row count values
     * Example: Then number of page should be 3
     *
     * @Then number of page should be :number
     * @Then number of page in "(?P<gridName>[^"]+)" should be :number
     */
    public function numberOfPageShouldBe($number, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        self::assertEquals(
            (int)$number,
            (int)$this->getGridPaginator($grid)->find('css', 'input[type="number"]')->getAttribute('value')
        );
    }

    /**
     * Example: When I fill 4 in page number input
     *
     * @When /^(?:|I )fill (?P<number>[\d]+) in page number input$/
     * @When /^(?:|I )fill (?P<number>[\d]+) in page number input of "(?P<gridName>[^"]+)"$/
     */
    public function iFillInPageNumberInput($number, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $this->getGridPaginator($grid)->find('css', 'input[type="number"]')->setValue($number);
    }

    /**
     * Sort grid by column
     * Example: When sort grid by Created at
     * Example: But when I sort grid by First Name again
     * Example: When I sort "Quotes Grid" by Updated At
     *
     * @When /^(?:|when )(?:|I )sort grid by (?P<field>(?:|[\w\s]*(?<!again)))(?:| again)$/
     * @When /^(?:|when )(?:|I )sort "(?P<gridName>[^"]+)" by (?P<field>(?:|[\w\s]*(?<!again)))(?:| again)$/
     * @When /^(?:|I )sort "(?P<gridName>[^"]+)" by "(?P<field>.*)"(?:| again)$/
     * @When /^(?:|I )sort grid by "(?P<field>.*)"(?:| again)$/
     */
    public function sortGridBy($field, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $grid->getElement($grid->getMappedChildElementName('GridHeader'))
            ->findElementContains($grid->getMappedChildElementName('GridHeaderLink'), $field)
            ->click();
    }

    //@codingStandardsIgnoreStart
    /**
     * @Then /^(?P<column>[\w\s]+) in (?P<rowNumber1>(?:|first|second|[\d]+)) row must be (?P<comparison>(?:|lower|greater|equal)) then in (?P<rowNumber2>(?:|first|second|[\d]+)) row$/
     * @Then /^(?P<column>[\w\s]+) in (?P<rowNumber1>(?:|first|second|[\d]+)) row in "(?P<gridName>[^"]+)" must be (?P<comparison>(?:|lower|greater|equal)) then in (?P<rowNumber2>(?:|first|second|[\d]+)) row$/
     */
    //@codingStandardsIgnoreEnd
    public function compareRowValues($column, $comparison, $rowNumber1, $rowNumber2, $gridName = null)
    {
        $grid = $this->getGrid($gridName);

        $rowNumber1 = $this->getNumberFromString($rowNumber1);
        $rowNumber2 = $this->getNumberFromString($rowNumber2);

        $value1 = $grid->getRowByNumber($rowNumber1)->getCellValue($column);
        $value2 = $grid->getRowByNumber($rowNumber2)->getCellValue($column);

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

    //@codingStandardsIgnoreStart
    /**
     * Assert that column value of specified row is equal to given value
     * Example: I should see that Translated Value in 1 row is equal to "some value"
     * @Then /^(?:|I )should see that (?P<column>[\w\s]+) in (?P<rowNumber>[\d]+) row is equal to "(?P<value>.*)"$/
     * @Then /^(?:|I )should see that (?P<column>[\w\s]+) in (?P<rowNumber>[\d]+) row in "(?P<gridName>[^"]+)" is equal to "(?P<value>.*)"$/
     */
    //@codingStandardsIgnoreEnd
    public function assertColumnValueEquals($column, $rowNumber, $value, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $rowValue = $grid->getRowByNumber($rowNumber)->getCellValue($column);
        self::assertEquals($value, $rowValue);
    }

    //@codingStandardsIgnoreStart
    /**
     * Assert that column value of specified row is empty (or not empty)
     * Example: I should see that Translated Value in 1 row is empty
     * Example: I should see that Translated Value in 1 row is not empty
     * @Then /^(?:|I )should see that (?P<column>[\w\s]+) in (?P<rowNumber>[\d]+) row is (?P<type>(?:|empty|not empty))$/
     * @Then /^(?:|I )should see that (?P<column>[\w\s]+) in (?P<rowNumber>[\d]+) row in "(?P<gridName>[^"]+)" is (?P<type>(?:|empty|not empty))$/
     */
    //@codingStandardsIgnoreEnd
    public function assertColumnValueIsEmpty($column, $rowNumber, $type, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $rowValue = $grid->getRowByNumber($rowNumber)->getCellValue($column);
        $type === 'empty' ? self::assertEmpty($rowValue) : self::assertNotEmpty($rowValue);
    }

    //@codingStandardsIgnoreStart
    /**
     * Assert column values by given row
     * Example: Then I should see Charlie Sheen in grid with following data:
     *            | Email   | charlie@gmail.com   |
     *            | Phone   | +1 415-731-9375     |
     *            | Country | Ukraine             |
     *            | State   | Kharkivs'ka Oblast' |
     * Example: Then I should see Charlie Sheen in Frontend Grid with following data:
     *            | Email   | charlie@gmail.com   |
     *            | Phone   | +1 415-731-9375     |
     *
     * @Then /^(?:|I )should see (?P<content>[\w\s\.\_\-\@\:]+) in (?:|grid|(?P<gridName>[\s\w]+)) with following data:$/
     * @Then /^(?:|I )should see "(?P<content>[\w\s\.\_\-\@\:\(\)]+)" in (?:|grid|(?P<gridName>[\s\w]+)) with following data:$/
     * @Then /^(?:|I )should see "(?P<content>[\w\s\.\_\-\@\:\(\)]+)" in "(?:|grid|(?P<gridName>[\s\w]+))" with following data:$/
     */
    //@codingStandardsIgnoreEnd
    public function assertRowValues($content, TableNode $table, $gridName = null)
    {
        $grid = $this->getGrid($gridName, $content);

        /** @var TableHeader $gridHeader */
        $gridHeader = $grid->getElement($grid->getMappedChildElementName($grid::TABLE_HEADER_ELEMENT));
        $row = $grid->getRowByContent($content);

        $crawler = new Crawler($row->getHtml());
        /** @var Crawler[] $columns */
        $columns = $crawler->filter('td')->each(function (Crawler $td) {
            return $td;
        });

        foreach ($table->getRows() as [$header, $value]) {
            $columnNumber = $gridHeader->getColumnNumber($header);
            $actualValue = trim($columns[$columnNumber]->text());
            // removing multiple spaces, newlines, tabs
            $actualValue = trim(preg_replace('/[\s\t\n\r\x{00a0}]+/iu', " ", $actualValue));

            $html = trim($columns[$columnNumber]->html());
            // remove "Edit" suffix from value, if it comes from editable cell
            if (preg_match('/<i[^>]+>Edit<\/i>$/iu', $html) === 1) {
                $actualValue = substr($actualValue, 0, -4);
            }

            self::assertEquals(
                $value,
                $actualValue,
                sprintf(
                    'Expect that %s column should be with "%s" value but "%s" found on grid',
                    $header,
                    $value,
                    $actualValue
                )
            );
        }
    }

    //@codingStandardsIgnoreStart
    /**
     * Assert record position in grid
     * It is find record by text and assert its position
     * Example: Then Zyta Zywiec must be first record
     * Example: And John Doe must be first record
     *
     * @Then /^(?P<content>[\w\d\s\-\.,%]+) must be (?P<rowNumber>(?:|first|second|[\d]+)) record$/
     * @Then /^(?P<content>[\w\d\s\-\.,%]+) must be (?P<rowNumber>(?:|first|second|[\d]+)) record in "(?P<gridName>[^"]+)"$/
     */
    //@codingStandardsIgnoreEnd
    public function assertRowContent($content, $rowNumber, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $row = $grid->getRowByNumber($this->getNumberFromString($rowNumber));
        self::assertMatchesRegularExpression(sprintf('/%s/i', $content), $row->getText());
    }

    /**
     * @Then /^I should see that "(?P<content>([^"]+))" is in (?P<rowNum>([\d]+)) row$/
     */
    public function assertRowContentInTable($content, $rowNum)
    {
        /** @var Table $table */
        $table = $this->findElementContains('Table', $content);
        $row = $table->getRowByNumber($rowNum);
        self::assertTrue(
            $row->has('named', ['content', $content]),
            "There is no content '$content' in $rowNum row"
        );
    }

    /**
     * Assert that text is in table with some content
     * Example: Then I should see "Priority" in "Warehouse" table
     * Example: Then I should not see "Apple" in "Basket" table
     *
     * @Then /^I should (?P<type>(?:|see|not see)) "(?P<content>.+)" in "(?P<tableContent>[\w\s]+)" table$/
     */
    public function assertContentInTable($type, $content, $tableContent)
    {
        /** @var Table $table */
        $table = $this->findElementContains('Table', $tableContent);
        $result = $table->has('named', ['content', $content]);
        if ($type === 'see') {
            self::assertTrue($result, "There is no text '$content' in table '$tableContent'");
        } else {
            self::assertFalse($result, "There is a text '$content' in table '$tableContent'");
        }
    }

    /**
     * Assert that mass action checkbox is unchecked for a record.
     * Example: Then I should see "Priority" record unchecked
     *
     * @Then /^I should see (?P<content>[\w\s]+) unchecked record in grid$/
     * @Then /^I should see (?P<content>[\w\s]+) unchecked record in "(?P<gridName>[^"]+)"$/
     *
     * @param string $content
     * @param string|null $gridName
     */
    public function assertRecordIsUnchecked($content, $gridName = null)
    {
        /** @var Grid $grid */
        $grid = $this->getGrid($gridName);

        static::assertTrue(
            $grid->isRecordUnchecked($content),
            sprintf('Record with "%s" content is checked', $content)
        );
    }

    //@codingStandardsIgnoreStart

    /**
     * Set range price value in grid filter
     * Example: When I set range filter "Price" as min value "12.45" and max value "25.66" use "item" unit
     * Example: And set range filter "Price" as min value "12.45" and max value "25.66" use "item" unit
     *
     * @When /^(?:|I )set range filter "(?P<filterName>.+)" as min value "(?P<minValue>[\w\s\,\.\_\%]+)"$/
     * @When /^(?:|I )set range filter "(?P<filterName>.+)" as min value "(?P<minValue>[\w\s\,\.\_\%]+)" use "(?P<unitType>[\w\s\=\<\>]+)" unit$/
     * @When /^(?:|I )set range filter "(?P<filterName>.+)" as max value "(?P<maxValue>[\w\s\,\.\_\%]+)"$/
     * @When /^(?:|I )set range filter "(?P<filterName>.+)" as max value "(?P<maxValue>[\w\s\,\.\_\%]+)" use "(?P<unitType>[\w\s\=\<\>]+)" unit$/
     * @When /^(?:|I )set range filter "(?P<filterName>.+)" as min value "(?P<minValue>[\w\s\,\.\_\%]+)" and max value "(?P<maxValue>[\w\s\,\.\_\%]+)" use "(?P<unitType>[\w\s\=\<\>]+)" unit$/
     * @When /^(?:|I )set range filter "(?P<filterName>.+)" as min value "(?P<minValue>[\w\s\,\.\_\%]+)" and max value "(?P<maxValue>[\w\s\,\.\_\%]+)"$/
     * @When /^(?:|I )set range filter "(?P<filterName>.+)" as min value "(?P<minValue>[\w\s\,\.\_\%]+)" and max value "(?P<maxValue>[\w\s\,\.\_\%]+)" use "(?P<unitType>[\w\s\=\<\>]+)" unit in "(?P<filterGridName>[\w\s]+)" grid$/
     *
     * @param string $filterName
     * @param string $minValue
     * @param string $maxValue
     * @param string $unitType
     * @param string $filterGridName
     * @param string $strictly
     */
    public function setPriceRangeFilter(
        $filterName,
        string $minValue = '',
        string $maxValue = '',
        string $unitType = '',
        string $filterGridName = 'Grid',
        string $strictly = ''
    ) {
        /** @var GridFilterPriceItem $filterItem */
        $filterItem = $this
            ->getGridFilters($filterGridName)
            ->getFilterItem('GridFilterPriceItem', $filterName, $strictly === 'strictly');

        $filterItem->open();
        if (!empty($unitType)) {
            $filterItem->selectRadioUnitType($unitType);
        }

        $filterItem->setFilterValue($minValue);
        $filterItem->setSecondFilterValue($maxValue);

        $filterItem->submit();
    }

    /**
     * Open filter dropdown
     * Example: And I open "Price" filter
     * Example: And I open "Price" filter in "Product" grid
     *
     * @When /^(?:|I )open "(?P<filterName>.+)" filter$/
     * @When /^(?:|I )open "(?P<filterName>.+)" filter in "(?P<filterGridName>[\w\s]+)" grid$/
     */
    public function openFilter(
        $filterName,
        string $filterGridName = 'Grid',
        string $strictly = ''
    ) {
        /** @var GridFilterPriceItem $filterItem */
        $filterItem = $this
            ->getGridFilters($filterGridName)
            ->getFilterItem('GridFilterPriceItem', $filterName, $strictly === 'strictly');

        $filterItem->open();
    }

    //@codingStandardsIgnoreStart
    /**
     * Set string value in grid filter
     * Example: When I set filter First Name as contains "Adi"
     * Example: And set filter Name as is equal to "User"
     *
     * @When /^(?:|I )set filter (?P<filterName>[\w\s]+) as (?P<type>(?:|is empty|is not empty))$/
     * @When /^(?:|I )set filter (?P<filterName>[\w\s]+) as (?P<type>[\w\s\=\<\>]+) "(?P<value>(?:[^"]|\\")*)"$/
     * @When /^(?:|I )set filter "(?P<filterName>.+)" as (?P<type>[\w\s\=\<\>]+) "(?P<value>(?:[^"]|\\")*)"$/
     * @When /^(?:|I )set filter (?P<filterName>[\w\s]+) as (?P<type>[\w\s\=\<\>]+) "(?P<value>(?:[^"]|\\")*)" in "(?P<filterGridName>[\w\s]+)"$/
     * @When /^(?:|I )set filter (?P<filterName>[\w\s]+) as (?P<type>[\w\s\=\<\>]+) "(?P<value>(?:[^"]|\\")*)" in "(?P<filterGridName>[\w\s]+)" grid$/
     * @When /^(?:|I )set filter (?P<filterName>.+) as (?P<type>[\w\s\=\<\>]+) "(?P<value>(?:[^"]|\\")*)" in "(?P<filterGridName>[\w\s]+)" grid ?(?P<strictly>strictly)$/
     *
     * @param string $filterName
     * @param string $type
     * @param string $value
     * @param string $filterGridName
     * @param string $strictly
     */
    //@codingStandardsIgnoreEnd
    public function setValueInStringFilter(
        $filterName,
        $type,
        $value = '',
        $filterGridName = 'Grid',
        string $strictly = ''
    ) {
        $value = $this->fixStepArgument($value);

        /** @var GridFilterStringItem $filterItem */
        $filterItem = $this
            ->getGridFilters($filterGridName)
            ->getFilterItem('GridFilterStringItem', $filterName, $strictly === 'strictly');

        $filterItem->open();
        $filterItem->selectType($type);
        // does not need set value if use filter 'is empty' or 'is not empty'
        if (!in_array($type, ['is empty', 'is not empty'])) {
            $filterItem->setFilterValue($value);
        }
    }

    //@codingStandardsIgnoreStart
    /**
     * Set string value in grid filter and press Enter key
     * Example: When I set filter First Name as contains "Adi" and press Enter key
     * Example: And set filter Name as is equal to "User" and press Enter key
     *
     * @When /^(?:|I )set filter (?P<filterName>[\w\s]+) as (?P<type>(?:|is empty|is not empty)) and press Enter key$/
     * @When /^(?:|I )set filter (?P<filterName>[\w\s]+) as (?P<type>[\w\s\=\<\>]+) "(?P<value>(?:[^"]|\\")*)" and press Enter key$/
     * @When /^(?:|I )set filter "(?P<filterName>.+)" as (?P<type>[\w\s\=\<\>]+) "(?P<value>(?:[^"]|\\")*)" and press Enter key$/
     * @When /^(?:|I )set filter (?P<filterName>[\w\s]+) as (?P<type>[\w\s\=\<\>]+) "(?P<value>(?:[^"]|\\")*)" in "(?P<filterGridName>[\w\s]+)" and press Enter key$/
     * @When /^(?:|I )set filter (?P<filterName>[\w\s]+) as (?P<type>[\w\s\=\<\>]+) "(?P<value>(?:[^"]|\\")*)" in "(?P<filterGridName>[\w\s]+)" grid and press Enter key$/
     * @When /^(?:|I )set filter (?P<filterName>.+) as (?P<type>[\w\s\=\<\>]+) "(?P<value>(?:[^"]|\\")*)" in "(?P<filterGridName>[\w\s]+)" grid ?(?P<strictly>strictly) and press Enter key$/
     *
     * @param string $filterName
     * @param string $type
     * @param string $value
     * @param string $filterGridName
     * @param string $strictly
     */
    //@codingStandardsIgnoreEnd
    public function applyStringFilterByEnterKey(
        $filterName,
        $type,
        $value = '',
        $filterGridName = 'Grid',
        string $strictly = ''
    ) {
        self::setValueInStringFilter($filterName, $type, $value, $filterGridName, $strictly);

        /** @var GridFilterStringItem $filterItem */
        $filterItem = $this
            ->getGridFilters($filterGridName)
            ->getFilterItem('GridFilterStringItem', $filterName, $strictly === 'strictly');

        $field = $filterItem->getInputField();
        $field->keyDown(13);
        $field->keyUp(13);
        $this->waitForAjax();
    }

    //@codingStandardsIgnoreStart
    /**
     * Check input value in string grid filter
     * Example: When I should see filter First SKU field value is equal to "123"
     * Example: And  should see filter First Name field value is equal to ""User"
     *
     * @When /^(?:|I )should see filter (?P<filterName>[\w\s]+) field value is equal to "(?P<value>(?:[^"]|\\")*)"$/
     * @When /^(?:|I )should see filter (?P<filterName>[\w\s]+) field value is equal to ""(?P<value>(?:[^"]|\\")*)" in "(?P<filterGridName>[\w\s]+)"$/
     * @When /^(?:|I )should see filter (?P<filterName>[\w\s]+) field value is equal to "(?P<value>(?:[^"]|\\")*)" in "(?P<filterGridName>[\w\s]+)" grid$/
     * @When /^(?:|I )should see filter (?P<filterName>.+) field value is equal to "(?P<value>(?:[^"]|\\")*)" in "(?P<filterGridName>[\w\s]+)" grid ?(?P<strictly>strictly)$/
     *
     * @param string $filterName
     * @param string $value
     * @param string $filterGridName
     * @param string $strictly
     */
    //@codingStandardsIgnoreEnd
    public function assertsInputValueInStringFilterEqualToExpectedValue(
        $filterName,
        $value = '',
        $filterGridName = 'Grid',
        string $strictly = ''
    ) {
        /** @var GridFilterStringItem $filterItem */
        $filterItem = $this
            ->getGridFilters($filterGridName)
            ->getFilterItem('GridFilterStringItem', $filterName, $strictly === 'strictly');

        $filterItem->open();

        $field = $filterItem->getInputField();

        static::assertEquals(
            $value,
            $field->getValue(),
            sprintf('The "%s" filter value is not equal to "%s"', $filterName, $value)
        );
    }
    //@codingStandardsIgnoreStart
    /**
     * Filter grid by string filter
     * Example: When I filter First Name as contains "Aadi"
     * Example: And filter Name as is equal to "User"
     *
     * @When /^(?:|I )filter (?P<filterName>[\w\s]+) as (?P<type>(?:|is empty|is not empty))$/
     * @When /^(?:|I )filter (?P<filterName>[\w\s]+) as (?P<type>[\w\s\=\<\>]+) "(?P<value>(?:[^"]|\\")*)"$/
     * @When /^(?:|I )filter "(?P<filterName>.+)" as (?P<type>[\w\s\=\<\>]+) "(?P<value>(?:[^"]|\\")*)"$/
     * @When /^(?:|I )filter (?P<filterName>[\w\s]+) as (?P<type>[\w\s\=\<\>]+) "(?P<value>(?:[^"]|\\")*)" in "(?P<filterGridName>[\w\s]+)"$/
     * @When /^(?:|I )filter (?P<filterName>[\w\s]+) as (?P<type>[\w\s\=\<\>]+) "(?P<value>(?:[^"]|\\")*)" in "(?P<filterGridName>[\w\s]+)" grid$/
     * @When /^(?:|I )filter (?P<filterName>.+) as (?P<type>[\w\s\=\<\>]+) "(?P<value>(?:[^"]|\\")*)" in "(?P<filterGridName>[\w\s]+)" grid ?(?P<strictly>strictly)$/
     *
     * @param string $filterName
     * @param string $type
     * @param string $value
     * @param string $filterGridName
     * @param string $strictly
     */
    //@codingStandardsIgnoreEnd
    public function applyStringFilter(
        $filterName,
        $type,
        $value = null,
        $filterGridName = 'Grid',
        string $strictly = ''
    ) {
        $value = $this->fixStepArgument($value);

        /** @var GridFilterStringItem $filterItem */
        $filterItem = $this
            ->getGridFilters($filterGridName)
            ->getFilterItem('GridFilterStringItem', $filterName, $strictly === 'strictly');

        $filterItem->open();
        $filterItem->selectType($type);
        // does not need set value if use filter 'is empty' or 'is not empty'
        if (!in_array($type, ['is empty', 'is not empty'])) {
            $filterItem->setFilterValue($value);
        }

        $filterItem->submit();
    }

    /**
     * Assert default filter type
     *
     * @When /^(?:|I )filter "(?P<filterName>[\w\s]+)" should have selected "(?P<type>[\w\s]+)" type$/
     *
     * @param string $filterName
     * @param string $type
     * @param string $filterGridName
     */
    public function assertSelectedFilterType($filterName, $type, $filterGridName = 'Grid')
    {
        /** @var GridFilterStringItem $filterItem */
        $filterItem = $this->getGridFilters($filterGridName)->getFilterItem('GridFilterStringItem', $filterName);

        $filterItem->open();
        $selected = $filterItem->getSelectedType();

        self::assertMatchesRegularExpression(
            sprintf('/%s/i', $type),
            $selected,
            sprintf('Chosen "%s" filter instead of "%s" type', $selected, $type)
        );

        $filterItem->close();
    }

    //@codingStandardsIgnoreStart
    /**
     * Filter grid by string filter
     * Example: I filter "Price (USD)" as equals "12" use "item" unit
     * Example: I filter "Price (USD)" as between "5,89" and "6,11" use "item" unit
     *
     * @When /^(?:|I )filter "(?P<filterName>.+)" as (?P<type>[\w\s\=\<\>]+) "(?P<value>[\w\s\,\.\_\%]+)" use "(?P<unitType>[\w\s\=\<\>]+)" unit$/
     * @When /^(?:|I )filter "(?P<filterName>.+)" as (?P<type>[\w\s\=\<\>]+) "(?P<value>[\w\s\,\.\_\%]+)" use "(?P<unitType>[\w\s\=\<\>]+)" unit in "(?P<filterGridName>[\w\s]+)" grid$/
     * @When /^(?:|I )filter "(?P<filterName>.+)" as (?P<type>(?:|between|not between)) "(?P<value>[\w\s\,\.\_\%]+)" and "(?P<secondValue>[\w\s\,\.\_\%]+)" use "(?P<unitType>[\w\s\=\<\>]+)" unit$/
     * @When /^(?:|I )filter "(?P<filterName>.+)" as (?P<type>(?:|between|not between)) "(?P<value>[\w\s\,\.\_\%]+)" and "(?P<secondValue>[\w\s\,\.\_\%]+)" use "(?P<unitType>[\w\s\=\<\>]+)" unit in "(?P<filterGridName>[\w\s]+)" grid$/
     *
     * @param string $filterName
     * @param string $type
     * @param string $value
     * @param string $secondValue
     * @param string $unitType
     * @param string $filterGridName
     * @param string $strictly
     */
    //@codingStandardsIgnoreEnd
    public function applyPriceFilter(
        $filterName,
        $type,
        $value = null,
        $secondValue = null,
        $unitType = null,
        $filterGridName = 'Grid',
        string $strictly = ''
    ) {
        /** @var GridFilterPriceItem $filterItem */
        $filterItem = $this
            ->getGridFilters($filterGridName)
            ->getFilterItem('GridFilterPriceItem', $filterName, $strictly === 'strictly');

        $filterItem->open();
        $filterItem->selectType($type);
        $filterItem->selectUnitType($unitType);
        $filterItem->setFilterValue($value);

        if ($type === 'between' && $secondValue !== null) {
            $filterItem->setSecondFilterValue($secondValue);
        }

        $filterItem->submit();
    }

    //@codingStandardsIgnoreStart
    /**
     * Filter grid by choice filter
     * Example: When I choose filter for Status as Is Any Of "Option 1"
     * Example: And I choose filter for Step as Is not Any Of "Option 2"
     *
     * @When /^(?:|I )choose filter for (?P<filterName>[\w\s]+) as (?P<type>(?:|Is Any Of|Is not Any Of|is any of|is not any of)) "(?P<value>[\w\s\,\.\_\%]+)"$/
     * @When /^(?:|I )choose filter for (?P<filterName>[\w\s]+) as (?P<type>(?:|Is Any Of|Is not Any Of|is any of|is not any of)) "(?P<value>[\w\s\,\.\_\%]+)" in "(?P<filterGridName>[\w\s]+)"$/
     * @When /^(?:|I )choose filter for (?P<filterName>[\w\s]+) as (?P<type>(?:|Is Any Of|Is not Any Of|is any of|is not any of)) "(?P<value>[\w\s\,\.\_\%]+)" in "(?P<filterGridName>[\w\s]+)" grid$/
     *
     * @param string $filterName
     * @param string $type
     * @param string $value
     * @param string $filterGridName
     */
    //@codingStandardsIgnoreEnd
    public function applyChoiceFilter($filterName, $type, $value = null, $filterGridName = 'Grid')
    {
        /** @var GridFilterStringItem $filterItem */
        $filterItem = $this->getGridFilters($filterGridName)->getFilterItem('GridFilterChoice', $filterName);

        $filterItem->open();
        $filterItem->selectType($type);
        $filterItem->setFilterValue($value);
        $filterItem->submit();
    }

    /**
     * Filter grid by choice tree filter
     *
     * Example: When I choose "Value" in the Test filter
     *
     * @When /^(?:|I )choose "(?P<value>(?:[^"]|\\")*)" in the (?P<filterName>[\w\s]+) filter$/
     *
     * @param string $filterName
     * @param string $value
     * @param string $filterGridName
     */
    public function applyChoiceTreeFilter($filterName, $value = null, $filterGridName = 'Grid')
    {
        /** @var GridFilterChoiceTree $filterItem */
        $filterItem = $this->getGridFilters($filterGridName)->getFilterItem('GridFilterChoiceTree', $filterName);

        $filterItem->open();
        $filterItem->setFilterValue($value);
        $filterItem->submit();
    }

    /**
     * Check that the item exists in grid filter options
     *
     * Example: Then I should see "Value" in the Test filter
     *
     * @When /^(?:|I )should see "(?P<value>[\w\s\,\.\_\%]+)" in the (?P<filterName>[\w\s]+) filter$/
     *
     * @param string $filterName
     * @param string $value
     */
    public function shouldSeeChoiceTreeFilterOption($filterName, $value = null)
    {
        /** @var GridFilterChoiceTree $filterItem */
        $filterItem = $this->getGridFilters('Grid')->getFilterItem('GridFilterChoiceTree', $filterName);

        $filterItem->open();
        $filterItem->checkValue($value, true);
        $filterItem->close();
    }

    /**
     * Check that the item in grid filter options does not exist
     *
     * Example: Then I should not see "Value" in the Test filter
     *
     * @When /^(?:|I )should not see "(?P<value>[\w\s\,\.\_\%]+)" in the (?P<filterName>[\w\s]+) filter$/
     *
     * @param string $filterName
     * @param string $value
     */
    public function shouldNotSeeChoiceTreeFilterOption($filterName, $value = null)
    {
        /** @var GridFilterChoiceTree $filterItem */
        $filterItem = $this->getGridFilters('Grid')->getFilterItem('GridFilterChoiceTree', $filterName);

        $filterItem->open();
        $filterItem->checkValue($value, false);
        $filterItem->close();
    }

    //@codingStandardsIgnoreStart
    /**
     * Filter grid by to dates between or not between
     * Date must be valid format for DateTime php class e.g. 2015-12-24, 2015-12-26 8:30:00, 30 Jun 2015
     * Example: When I filter Date Range as between "2015-12-24" and "2015-12-26"
     * Example: But when I filter Created At as not between "25 Jun 2015" and "30 Jun 2015"
     *
     * @When /^(?:|I )filter "?(?P<filterName>[^"]+)"? as (?P<type>(?:|equals|not equals)) "(?P<start>[^"]+)" as single value$/
     * @When /^(?:|when )(?:|I )filter "?(?P<filterName>[^"]+)"? as (?P<type>(?:|between|not between)) "(?P<start>[^"]+)" and "(?P<end>[^"]+)"$/
     * @When /^(?:|when )(?:|I )filter "?(?P<filterName>[^"]+)"? as (?P<type>(?:|between|not between)) "(?P<start>[^"]+)" and "(?P<end>[^"]+)" in "(?P<filterGridName>[^"]+)"$/
     *
     * @param string $filterName
     * @param string $type
     * @param string $start
     * @param string $end
     * @param string $filterGridName
     */
    //@codingStandardsIgnoreEnd
    public function applyDateTimeFilter($filterName, $type, $start, $end = null, $filterGridName = 'Grid')
    {
        $filterItem = $this->spin(function () use ($filterGridName, $filterName, $type) {
            /** @var GridFilterDateTimeItem $filterItem */
            $filterItem = $this->getGridFilters($filterGridName)->getFilterItem('GridFilterDateTimeItem', $filterName);
            $filterItem->open();
            $filterItem->selectType($type);

            return $filterItem;
        }, 10);

        if (!$filterItem) {
            self::fail(sprintf('Date time filter "%s" not found', $filterName));
        }

        $filterItem->setStartTime($start);

        if (null !== $end) {
            $filterItem->setEndTime($end);
        }

        $filterItem->submit();
    }

    /**
     * Asserts header in datepicker.
     *
     * Example: Then I should see following header in "Datepicker" filter:
     *             | S | M | T | W | T | F | S |
     *
     * @Then /^(?:|I )should see following header in "(?P<filterName>[^"]+)" filter:$/
     * @Then /^(?:|I )should see following header in "(?P<filterName>[^"]+)" filter in "(?P<filterGridName>[\w\s]+)":$/
     *
     * @param string $filterName
     * @param TableNode $table
     * @param string $filterGridName
     */
    public function iShouldSeeFollowingHeaderInDateTimeFilter(
        string $filterName,
        TableNode $table,
        $filterGridName = 'Grid'
    ) {
        $data = $table->getRows();
        self::assertNotEmpty($data);

        /** @var GridFilterDateTimeItem $filterItem */
        $filterItem = $this->getGridFilters($filterGridName)
            ->getFilterItem($filterGridName . 'FilterDateTimeItem', $filterName);

        $filterItem->open();
        $filterItem->selectType('equals');

        /** @var DateTimePicker $input */
        $input = $filterItem->findVisible('css', '.datepicker-input');
        $input = $this->elementFactory->wrapElement('DateTimePicker', $input);

        self::assertTrue($input->isVisible());
        self::assertEquals(reset($data), $input->getHeader());

        $filterItem->close();
    }

    /**
     * Check checkboxes in multiple select filter
     * Example: When I check "Task, Email" in Activity Type filter
     *
     * @When /^(?:|I )check "(?P<filterItems>.+)" in (?P<filterName>[\w\s]+) filter$/
     * @When /^(?:|I )check "(?P<filterItems>.+)" in (?P<filterName>[\w\s]+) filter in "(?P<filterGridName>[\w\s]+)"$/
     * @When /^(?:|I )check "(?P<filterItems>.+)" in "(?P<filterName>.+)" filter$/
     * @When /^(?:|I )check "(?P<filterItems>.+)" in "(?P<filterName>.+)" filter in "(?P<filterGridName>[\w\s]+)"$/
     *
     * @param string $filterName
     * @param string $filterItems
     * @param string $filterGridName
     */
    public function iCheckCheckboxesInFilter($filterName, $filterItems, $filterGridName = 'Grid')
    {
        /** @var MultipleChoice $filterItem */
        $filterItem = $this->getGridFilters($filterGridName)->getFilterItem('MultipleChoice', $filterName);
        $filterItem->checkItemsInFilter($filterItems);
    }

    /**
     * Check checkboxes in multiple select filter strictly (case-sensitive)
     * Example: When I check "Active, Inactive" strictly in Activity Type filter
     *
     * @When /^(?:|I )check "(?P<filterItems>.+)" strictly in (?P<filterName>[\w\s]+) filter$/
     * @When /^(?:|I )check "(?P<filterItems>.+)" strictly in (?P<filterName>[\w\s]+) filter in
     * "(?P<filterGridName>[\w\s]+)"$/
     * @When /^(?:|I )check "(?P<filterItems>.+)" strictly in "(?P<filterName>.+)" filter$/
     * @When /^(?:|I )check "(?P<filterItems>.+)" strictly in "(?P<filterName>.+)" filter in
     * "(?P<filterGridName>[\w\s]+)"$/
     *
     * @param string $filterName
     * @param string $filterItems
     * @param string $filterGridName
     */
    public function iCheckCheckboxesStrictlyInFilter($filterName, $filterItems, $filterGridName = 'Grid')
    {
        /** @var MultipleChoice $filterItem */
        $filterItem = $this->getGridFilters($filterGridName)->getFilterItem('MultipleChoice', $filterName);
        $filterItem->checkItemsInFilterStrict($filterItems);
    }

    //@codingStandardsIgnoreStart
    /**
     * Select value in select filter
     * Example: When I check "Task, Email" in "Activity Type" filter strictly
     *
     * @When /^(?:|I )check "(?P<filterItems>.+)" in "(?P<filterLabel>.+)" filter strictly$/
     * @When /^(?:|I )check "(?P<filterItems>.+)" in "(?P<filterLabel>.+)" filter in "(?P<filterGridName>[\w\s]+)" strictly$/
     *
     * @param string $filterLabel
     * @param string $filterItems
     * @param string $filterGridName
     */
    //@codingStandardsIgnoreEnd
    public function iCheckItemsInFilterStrictly($filterLabel, $filterItems, $filterGridName = 'Grid')
    {
        /** @var MultipleChoice $filterItem */
        $filterItem = $this->getGridFilters($filterGridName)->getFilterItem('MultipleChoice', $filterLabel, true);
        $filterItem->checkItemsInFilter($filterItems);
    }

    //@codingStandardsIgnoreStart
    /**
     * Verify checkbox state in checkbox grid filter
     *
     * Example: Then I should see filter Brand contains checked "ACME"
     *
     * @Then /^(?:|I )should see filter (?P<filterName>[\w\s]+) contains checked "(?P<value>(?:[^"]|\\")*)"$/
     * @Then /^(?:|I )should see filter (?P<filterName>[\w\s]+) contains checked ""(?P<value>(?:[^"]|\\")*)" in "(?P<filterGridName>[\w\s]+)"$/
     * @Then /^(?:|I )should see filter (?P<filterName>[\w\s]+) contains checked "(?P<value>(?:[^"]|\\")*)" in "(?P<filterGridName>[\w\s]+)" grid$/
     */
    //@codingStandardsIgnoreEnd
    public function assertsInputValueInCheckboxFilterContainCheckedValue(
        string $filterName,
        string $value = '',
        string $filterGridName = 'Grid'
    ): void {
        /** @var MultipleChoice $filterItem */
        $filterItem = $this->getGridFilters($filterGridName)->getFilterItem('MultipleChoice', $filterName);
        self::assertTrue($filterItem->isItemChecked($value));
    }

    //@codingStandardsIgnoreStart
    /**
     * Verify checkbox state in checkbox grid filter
     *
     * Example: Then I should see filter Brand contains checked "ACME"
     *
     * @Then /^(?:|I )should see filter (?P<filterName>[\w\s]+) contains unchecked "(?P<value>(?:[^"]|\\")*)"$/
     * @Then /^(?:|I )should see filter (?P<filterName>[\w\s]+) contains unchecked ""(?P<value>(?:[^"]|\\")*)" in "(?P<filterGridName>[\w\s]+)"$/
     * @Then /^(?:|I )should see filter (?P<filterName>[\w\s]+) contains unchecked "(?P<value>(?:[^"]|\\")*)" in "(?P<filterGridName>[\w\s]+)" grid$/
     */
    //@codingStandardsIgnoreEnd
    public function assertsInputValueInCheckboxFilterContainUncheckedValue(
        string $filterName,
        string $value = '',
        string $filterGridName = 'Grid'
    ): void {
        /** @var MultipleChoice $filterItem */
        $filterItem = $this->getGridFilters($filterGridName)->getFilterItem('MultipleChoice', $filterName);
        self::assertFalse($filterItem->isItemChecked($value));
    }

    /**
     * Reset filter
     * Example: And I reset Activity Type filter
     *
     * @When /^(?:|I )reset (?P<filterName>[\w\s\:\(\)\#]+) filter$/
     * @When /^(?:|I )reset "(?P<filterName>[^"]+)" filter in grid$/
     * @When /^(?:|I )reset "(?P<filterName>[^"]+)" filter in "(?P<filterGridName>[\w\s]+)"$/
     *
     * @param string $filterName
     * @param string $filterGridName
     */
    public function resetFilter($filterName, $filterGridName = 'Grid')
    {
        $this->spin(function () use ($filterName, $filterGridName) {
            $filterItem = $this->getGridFilters($filterGridName)->getFilterItem('GridFilterDateTimeItem', $filterName);
            $filterItem->reset();
            $filterItem->find('css', 'button.reset-filter');

            return !$filterItem || !$filterItem->isValid() || !$filterItem->isVisible();
        }, 5);

        $filterItem = $this->getGridFilters($filterGridName)->getFilterItem('GridFilterDateTimeItem', $filterName);
        $filterItem->reset();
    }

    /**
     * @When /^(?:|I )reset "(?P<filterName>[\w\s\:\(\)\#\/]+)" filter$/
     * @When /^(?:|I )reset "(?P<filterName>[\w\s\:\(\)\#\/]+)" filter on grid "(?P<filterGridName>[\w\s]+)"$/
     *
     * @param string $filterName
     * @param string $filterGridName
     */
    public function resetFilterOfGrid($filterName, $filterGridName = 'Grid')
    {
        $filterItem = $this->getGridFilters($filterGridName)
            ->getFilterItem($filterGridName . 'FilterItem', $filterName);
        $filterItem->reset();
    }

    /**
     * Example: Then I should see filter hints in frontend grid:
     *            | Any Text: contains "Lamp" |
     *
     * @When /^(?:|I )should see filter hints in frontend grid:$/
     * @When /^(?:|I )should see filter hints in "(?P<gridName>[^"]+)" frontend grid:$/
     */
    public function shouldSeeFrontendGridWithFilterHints(TableNode $table, string $gridName = 'Grid')
    {
        $hints = array_filter(
            array_map(
                function ($item) {
                    $label = trim($this->createElement('FrontendGridFilterHintLabel', $item)->getText());
                    $text = trim($this->createElement('FrontendGridFilterHint', $item)->getText());

                    return $label && $text ? sprintf('%s %s', $label, $text) : '';
                },
                $this->getGridFilters($gridName)->findAll('css', 'span.filter-criteria-hint-item')
            )
        );

        foreach ($table->getRows() as $row) {
            [$hint] = $row;

            $this->assertTrue(
                in_array($hint, $hints, true),
                sprintf('Hint "%s" not found on page', $hint)
            );
        }
    }

    /**
     * Example: Then should see filter hints in grid:
     *            | Any Text: contains "Lamp" |
     *
     * @When /^(?:|I )should see filter hints in grid:$/
     * @When /^(?:|I )should see filter hints in "(?P<gridName>[^"]+)" grid:$/
     */
    public function shouldSeeGridWithFilterHints(TableNode $table, string $gridName = 'Grid')
    {
        $hints = array_filter(
            array_map(
                function ($item) {
                    $text = trim($this->createElement('GridFilterHintLabel', $item)->getText());

                    return $text ? trim(sprintf('%s', str_replace('Reset', '', $text))) : '';
                },
                $this->getGridFilters($gridName)->findAll('xpath', '//div[@class="filter-item oro-drop"]')
            )
        );

        foreach ($table->getRows() as $row) {
            [$hint] = $row;

            $this->assertTrue(
                in_array($hint, $hints, true),
                sprintf('Hint "%s" not found on page', $hint)
            );
        }
    }

    /**
     * @When /^(?:|I )check All Visible records in grid$/
     * @When /^(?:|I )check All Visible records in "(?P<gridName>[^"]+)"$/
     * @When /^(?:|I )check All Visible records in "(?P<gridName>[^"]+)" grid$/
     *
     * @param string $gridName
     */
    public function iCheckAllVisibleRecordsInGrid($gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $grid->massCheck('All visible');
    }

    /**
     * @When /^(?:|I )check all records in grid$/
     * @When /^(?:|I )check all records in "(?P<gridName>[^"]+)"$/
     * @When /^(?:|I )check all records in "(?P<gridName>[^"]+)" grid$/
     *
     * @param string $gridName
     */
    public function iCheckAllRecordsInGrid($gridName = null)
    {
        $grid = $this->getGrid($gridName);
        if (!count($grid->getRows())) {
            self::fail('Grid has no records to check');
        }
        $grid->massCheck('All');
    }

    /**
     * Asserts that no record with provided content in grid
     * Example: And there is no "Glorious workflow" in grid
     *
     * @Then /^there is no "(?P<record>(?:[^"]|\\")*)" in grid$/
     * @Then /^there is no "(?P<record>(?:[^"]|\\")*)" in "(?P<gridName>[^"]+)"/
     * @param string $record
     */
    public function thereIsNoInGrid($record, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $gridRow = $grid->findElementContains('GridRow', $record);
        self::assertFalse($gridRow->isIsset(), sprintf('Grid still has record with "%s" content', $record));
    }

    /**
     * Assert that provided grid element has no records in it
     * Example: there is no records in Frontend Grid
     *
     * @Then /^there is no records in grid$/
     * @Then /^there are no records in grid$/
     * @Then all records should be deleted
     * @Then /^there is no records in "(?P<gridName>[^"]+)"$/
     * @Then /^there are no records in "(?P<gridName>[^"]+)"$/
     */
    public function thereIsNoRecordsInGrid($gridName = null)
    {
        $grid = $this->getGrid($gridName);
        self::assertCount(0, $grid->getRows());
    }

    /**
     * Click on row action. Row will founded by it's content
     * Example: And click view Charlie in grid
     * Example: When I click edit Call to Jennyfer in grid
     * Example: And I click delete Sign a contract with Charlie in grid
     * Example: And I click "Delete Current User" on row "John" in grid
     *
     * @Given /^(?:|I )click (?P<action>(?:|Clone|(?!\bon)\w)*) (?P<content>(?:[^"]|\\")*) in grid$/
     * @Given /^(?:|I )click (?P<action>(?:|Clone|(?!\bon)\w)*) (?P<content>(?:[^"]|\\")*) in "(?P<gridName>[^"]+)"$/
     * @Given /^(?:|I )click (?P<action>(?:|Clone|(?!\bon)\w)*) "(?P<content>.+)" in grid$/
     * @Given /^(?:|I )click (?P<action>(?:|Clone|(?!\bon)\w)*) "(?P<content>.+)" in "(?P<gridName>[^"]+)"$/
     * @Given /^(?:|I )click "(?P<action>[^"]*)" on row "(?P<content>[^"]*)" in grid$/
     * @Given /^(?:|I )click "(?P<action>[^"]*)" on row "(?P<content>[^"]*)" in "(?P<gridName>[^"]+)"$/
     * @Given /^(?:|I )click (?P<action>[\w\s]*) on (?P<content>(?:[^"]|\\")*) in grid "(?P<gridName>[^"]+)"$/
     * @Given /^(?:|I )click "(?P<action>[^"]*)" on row "(?P<content>[^"]*)" in grid "(?P<gridName>[^"]+)"$/
     *
     * @param string $content
     * @param string $action
     * @param string $gridName
     */
    public function clickActionInRow($content, $action, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $grid->clickActionLink($content, $action);
    }

    /**
     * Check that row action links existing. Row searching by it's content
     * Example: I should see following actions for Dutch in grid:
     *           | View   |
     *           | Edit   |
     *           | Delete |
     *
     * @Given /^(?:|I )should see following actions for (?P<content>(?:[^"]|\\")*) in grid:$/
     * @Given /^(?:|I )should see following actions for (?P<content>(?:[^"]|\\")*) in "(?P<gridName>[^"]+)":$/
     */
    public function actionsForRowExist($content, TableNode $table, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        /** @var GridRow $row */
        $row = $grid->getRowByContent($content);

        $actions = array_map(
            function (Element $action) {
                return $action->getText() ?: $action->getAttribute('title');
            },
            $row->getActionLinks()
        );

        $rows = array_column($table->getRows(), 0);

        foreach ($rows as $item) {
            self::assertContains($item, $actions);
        }
    }

    /**
     * Example: I should see only following actions for row #1 on "UsersGrid" grid:
     *            | View |
     *
     * @Given /^(?:|I )should see only following actions for row #(?P<number>\d+) on grid:$/
     * @Given /^(?:|I )should see only following actions for row #(?P<number>\d+) on "(?P<gridName>[^"]+)" grid:$/
     *
     * @param int $number
     * @param TableNode $table
     * @param string|null $gridName
     */
    public function iShouldSeeOnlyFollowingActionsForRow($number, TableNode $table, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        /** @var GridRow $row */
        $row = $grid->getRowByNumber($number);

        $actions = array_map(
            function (Element $action) {
                return $action->getText() ?: $action->getAttribute('title');
            },
            $row->getActionLinks()
        );

        $rows = array_column($table->getRows(), 0);

        self::assertEquals($rows, $actions);
    }

    /**
     * Check that row action links existing. Row searching by it's content
     * Example: I should not see following actions for Dutch in grid:
     *           | Delete |
     *
     * @Given /^(?:|I )should not see following actions for (?P<content>(?:[^"]|\\")*) in grid:$/
     * @Given /^(?:|I )should not see following actions for (?P<content>(?:[^"]|\\")*) in "(?P<gridName>[^"]+)":$/
     */
    public function actionsForRowNotExist($content, TableNode $table, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        /** @var GridRow $row */
        $row = $grid->getRowByContent($content);

        $actions = array_map(
            function (Element $action) {
                return $action->getText() ?: $action->getAttribute('title');
            },
            $row->getActionLinks()
        );

        $rows = array_column($table->getRows(), 0);

        foreach ($rows as $item) {
            self::assertNotContains($item, $actions);
        }
    }

    /**
     * Click on row in grid
     * Example: When click on Charlie in grid
     *
     * @Given /^(?:|I )click on (?P<content>(?:[^"]|\\")*) in grid$/
     * @Given /^(?:|I )click on (?P<content>(?:[^"]|\\")*) in grid "(?P<gridName>[^"]+)"$/
     */
    public function clickOnRow($content, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        //Spin prevents misclick while grid is rerendered dynamically
        $result = $this->spin(function () use ($grid, $content) {
            try {
                $grid->getRowByContent($content)->click();
            } catch (\Exception $exception) {
                return null;
            }

            return true;
        });

        if ($result === null) {
            $row = $grid->getRowByContent($content);
            self::assertNotNull($row, sprintf('Row %s is not found', $content));

            $row->click();
        }

        // Keep this check for sure that ajax is finish
        $this->waitForAjax();
    }

    /**
     * Click on first row action.
     * Example: And click "view" on first row in grid
     *
     * @Given /^(?:|I )click "(?P<action>[^"]*)" on first row in grid$/
     * @Given /^(?:|I )click "(?P<action>[^"]*)" on first row in "(?P<gridName>[^"]+)" grid$/
     * @Given /^(?:|I )click "(?P<action>[^"]*)" on first row in "(?P<gridName>[^"]+)"$/
     *
     * @param string $action
     * @param string $gridName
     */
    public function clickActionOnFirstRow($action, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        if (!empty($grid->getRows()[0])) {
            $row = $grid->getRows()[0];
            $link = $row->getActionLink($action);
            $link->click();
        }
    }

    /**
     * Expand grid view options.
     * Example: I click Options in grid view
     *
     * @Given I click Options in grid view
     * @Given I click Options in grid view in "(?P<gridName>[^"]+)"
     */
    public function clickViewOptions($gridName = null)
    {
        if ($gridName === null) {
            $this->elementFactory->createElement('GridViewOptionsLink')->click();
        } else {
            $grid = $this->getGrid($gridName);
            $grid->getElement($grid->getMappedChildElementName('GridViewOptionsLink'));
        }
    }

    /**
     * Expand grid view list on grid.
     * Example: I click grid view list on "TestGrid" grid
     *
     * @Given /^(?:|I )click grid view list$/
     * @Given /^(?:|I )click grid view list (?:|on|in) "(?P<gridName>[^"]+)" grid$/
     *
     * @param string|null $gridName
     */
    public function clickViewList($gridName = null)
    {
        $list = $this->getViewList($gridName);
        $list->press();
    }

    /**
     * Click on item in grid view list.
     * Example: Given I click on "Some view" in grid view list
     *
     * @Given I click on :title in grid view list
     * @Given I click on :title in grid view list in "(?P<gridName>[^"]+)"
     */
    public function clickLinkInViewList(string $title, string $gridName = null)
    {
        $list = $this->getViewList($gridName);
        $list->press();
        $list->clickLink($title);
    }

    private function getViewList(string $gridName = null): Element
    {
        if ($gridName === null) {
            $list = $this->elementFactory->createElement('GridViewList');
        } else {
            $grid = $this->getGrid($gridName);
            $list = $grid->getElement($grid->getMappedChildElementName('GridViewList'));
        }
        self::assertTrue($list->isValid(), 'Grid view list not found on the page');

        return $list;
    }

    /**
     * Click on item in grid view options.
     * Example: Given I click on "Some item" in grid view options
     *
     * @Given I click on :title in grid view options
     * @Given I click on :title in grid view options in "(?P<gridName>[^"]+)"
     *
     * @param string $title
     * @param string|null $gridName
     */
    public function clickLinkInViewOptions($title, $gridName = null)
    {
        $this->getViewOptions($gridName)->clickLink($title);
    }

    /**
     * Check that item in grid view options exists.
     * Example: Then I should see "Some item" in grid view options
     *
     * @Then I should see :title in grid view options
     * @Then I should see :title in grid view options in "(?P<gridName>[^"]+)"
     *
     * @param string $title
     * @param string|null $gridName
     */
    public function iShouldSeeItemInViewOptions($title, $gridName = null)
    {
        self::assertNotNull($this->getViewOptions($gridName)->findLink($title));
    }

    /**
     * Check that item in grid view options does not exist.
     * Example: Then I should not see "Some item" in grid view options
     *
     * @Then I should not see :title in grid view options
     * @Then I should not see :title in grid view options in "(?P<gridName>[^"]+)"
     *
     * @param string $title
     * @param string|null $gridName
     */
    public function iShouldNotSeeItemInViewOptions($title, $gridName = null)
    {
        self::assertNull($this->getViewOptions($gridName)->findLink($title));
    }

    /**
     * @param string|null $gridName
     * @return Element
     */
    private function getViewOptions($gridName = null)
    {
        if ($gridName === null) {
            return $this->elementFactory->createElement('GridViewOptions');
        }

        $grid = $this->getGrid($gridName);

        return $grid->getElement($grid->getMappedChildElementName('GridViewOptions'));
    }

    /**
     * @When /^(?:|I )click "(?P<button>(.+))" in confirmation dialogue$/
     */
    public function clickInConfirmationDialogue($button)
    {
        $modal = $this->elementFactory->createElement('Modal');
        $modal->clickOrPress($button);
    }

    /**
     * @When /^(?:|I )should see "(?P<message>(.+))" in confirmation dialogue$/
     */
    public function shouldSeeInConfirmationDialogue($message)
    {
        $this->waitForAjax();
        $element = $this->elementFactory->createElement('Modal');
        static::assertStringContainsString(
            $message,
            $element->getText(),
            \sprintf('Confirmation dialogue does not contains text %s', $message)
        );
    }

    /**
     * @When /^(?:|I )confirm deletion$/
     */
    public function confirmDeletion()
    {
        $this->clickInConfirmationDialogue('Yes, Delete');
    }

    /**
     * @When cancel deletion
     */
    public function cancelDeletion()
    {
        $this->clickInConfirmationDialogue('Cancel');
    }

    /**
     * @Then /^(?:|I )should see success message with number of records were deleted$/
     */
    public function iShouldSeeSuccessMessageWithNumberOfRecordsWereDeleted()
    {
        $found = $this->spin(function (GridContext $context) {
            $flashMessage = $context->getSession()->getPage()->find('css', '.flash-messages-holder');
            $regex = '/\d+ entities have been deleted successfully/';

            return preg_match($regex, $flashMessage->getText()) > 0;
        });

        self::assertTrue($found, 'Can\'t find flash message');
    }

    /**
     * Check that mass action link is not available in grid mass actions
     * Example: Then I shouldn't see Delete action
     *
     * @Then /^(?:|I )shouldn't see (?P<action>(?:[^"]|\\")*) action$/
     * @Then /^(?:|I )shouldn't see (?P<action>(?:[^"]|\\")*) action in "(?P<gridName>[^"]+)"$/
     */
    public function iShouldNotSeeMassAction($action, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        self::assertFalse(
            $grid->hasMassActionLink($action),
            sprintf('%s mass action should not be accessible', $action)
        );
    }

    /**
     * Check that mass action link is available in grid mass actions
     * Example: Then I should see Delete action
     *
     * @Then /^(?:|I )should see (?P<action>(?:[^"]|\\")*) action$/
     * @Then /^(?:|I )should see (?P<action>(?:[^"]|\\")*) action in "(?P<gridName>[\w\s]+)"$/
     */
    public function iShouldSeeMassAction($action, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        self::assertTrue(
            $grid->hasMassActionLink($action),
            sprintf('%s mass action should be accessible', $action)
        );
    }

    /**
     * Check that record with provided name exists in grid
     * Example: Then I should see First test group in grid
     *
     * @Then /^(?:|I )should see (?P<recordName>(?:[^"]|\\")*) in grid$/
     * @Then /^(?:|I )should see (?P<recordName>(?:[^"]|\\")*) in grid "(?P<gridName>[^"]+)"$/
     * @Then /^(?:|I )should see "(?P<recordName>(?:[^"]|\\")*)" in grid$/
     * @Then /^(?:|I )should see "(?P<recordName>(?:[^"]|\\")*)" in grid "(?P<gridName>[^"]+)"$/
     */
    public function iShouldSeeRecordInGrid($recordName, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $grid->getRowByContent($recordName);
    }

    /**
     * Check that given collection of records exists in grid
     * Example: Then I should see following records in grid:
     *            | Alice1  |
     *            | Alice10 |
     * @Then /^(?:|I )should see following records in grid:$/
     * @Then /^(?:|I )should see following records in "(?P<gridName>[^"]+)":$/
     */
    public function iShouldSeeFollowingRecordsInGrid(TableNode $table, $gridName = null)
    {
        $errorMessage = <<<TEXT
            ---
            You can't use more then one column in this method
            It just asserts that given strings are in the grid
            Example: Then I should see following records in grid:
                       | Alice1  |
                       | Alice10 |

            Guess, you can use another method...

            And I should see following grid:
                | First name | Last name | Primary Email     | Enabled | Status |
                | John       | Doe       | admin@example.com | Enabled | Active |

TEXT;

        self::assertCount(1, $table->getRow(0), $errorMessage);
        foreach ($table->getRows() as [$value]) {
            $this->iShouldSeeRecordInGrid($value, $gridName);
        }
    }

    /**
     * Check column is not present in grid
     * Example: Then I shouldn't see Example column in grid
     *
     * @Then /^(?:|I )shouldn't see "(?P<columnName>(?:[^"]|\\")*)" column in grid$/
     * @Then /^(?:|I )shouldn't see "(?P<columnName>(?:[^"]|\\")*)" column in "(?P<gridName>[^"]+)"$/
     * @param string $columnName
     * @param null|string $gridName
     */
    public function iShouldNotSeeColumnInGrid($columnName, $gridName = null)
    {
        self::assertFalse(
            $this->getGrid($gridName)->getHeader()->hasColumn($columnName),
            sprintf('"%s" column is in grid', $columnName)
        );
    }

    /**
     * Check column is present in grid
     * Example: Then I should see Example column in grid
     *
     * @Then /^(?:|I )should see "(?P<columnName>(?:[^"]|\\")*)" column in grid$/
     * @Then /^(?:|I )should see "(?P<columnName>(?:[^"]|\\")*)" column in "(?P<gridName>[^"]+)"$/
     * @param string $columnName
     * @param null|string $gridName
     */
    public function iShouldSeeColumnInGrid($columnName, $gridName = null)
    {
        self::assertTrue(
            $this->getGrid($gridName)->getHeader()->hasColumn($columnName),
            sprintf('"%s" column is not in grid', $columnName)
        );
    }

    /**
     * Check filter is not present in grid
     * Example: Then I should see Example filter in grid
     *
     * @Then /^(?:|I )should see "(?P<filterName>(?:[^"]|\\")*)" filter in grid$/
     * @Then /^(?:|I )should see "(?P<filterName>(?:[^"]|\\")*)" filter in "(?P<gridName>[^"]+)"$/
     * @param string $filterName
     * @param null|string $gridName
     */
    public function iShouldSeeFilterInGrid($filterName, $gridName = 'Grid')
    {
        self::assertTrue(
            $this->getGridFilters($gridName)
                ->hasFilterItem($gridName . 'FilterItem', $filterName),
            sprintf('"%s" filter is in grid', $filterName)
        );
    }

    /**
     * Check filter is present in grid
     * Example: Then I should not see Example filter in grid
     *
     * @Then /^(?:|I )should not see "(?P<filterName>(?:[^"]|\\")*)" filter in grid$/
     * @Then /^(?:|I )should not see "(?P<filterName>(?:[^"]|\\")*)" filter in "(?P<gridName>[^"]+)"$/
     * @param string $filterName
     * @param null|string $gridName
     */
    public function iShouldNotSeeFilterInGrid($filterName, $gridName = 'Grid')
    {
        self::assertFalse(
            $this->getGridFilters($gridName)
                ->hasFilterItem($gridName . 'FilterItem', $filterName),
            sprintf('"%s" filter is in grid', $filterName)
        );
    }

    /**
     * Check visibility checkbox for specified column
     * Show this column in grid
     *
     * @Given /^(?:|I) show column (?P<columnName>(?:[^"]|\\")*) in grid$/
     * @Given /^(?:|I) show column (?P<columnName>(?:[^"]|\\")*) in "(?P<gridName>[^"]+)"/
     * @Given /^(?:|I) mark as visible column (?P<columnName>(?:[^"]|\\")*) in grid$/
     * @Given /^(?:|I) mark as visible column (?P<columnName>(?:[^"]|\\")*) in "(?P<gridName>[^"]+)"/
     */
    public function iShowColumnInGrid($columnName, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $columnManager = $this->getGridColumnManager($grid);
        $columnManager->open();
        $columnManager->checkColumnVisibility($columnName);
        $columnManager->close();
    }

    /**
     * Uncheck visibility checkbox for specified column
     * Hide this column in grid
     *
     * @Given /^(?:|I) hide column (?P<columnName>(?:[^"]|\\")*) in grid$/
     * @Given /^(?:|I) hide column (?P<columnName>(?:[^"]|\\")*) in "(?P<gridName>[^"]+)"/
     * @Given /^(?:|I) mark as not visible column (?P<columnName>(?:[^"]|\\")*) in grid$/
     * @Given /^(?:|I) mark as not visible column (?P<columnName>(?:[^"]|\\")*) in "(?P<gridName>[^"]+)"/
     */
    public function iHideColumnInGrid($columnName, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $columnManager = $this->getGridColumnManager($grid);
        $columnManager->open();
        $columnManager->uncheckColumnVisibility($columnName);
        $columnManager->close();
    }

    /**
     * Hide all columns in grid except mentioned
     *
     * @When /^(?:|I) hide all columns in grid except (?P<exceptions>(?:[^"]|\\")*)$/
     * @When /^(?:|I) hide all columns in "(?P<gridName>[^"]+)" except (?P<exceptions>(?:[^"]|\\")*)$/
     * @When /^(?:|I) hide all columns in grid$/
     * @When /^(?:|I) hide all columns in "(?P<gridName>[^"]+)"$/
     *
     * @param string $exceptions
     * @param string|null $gridName
     */
    public function iHideAllColumnsInGrid($exceptions = '', $gridName = null)
    {
        $exceptions = explode(',', $exceptions);
        $exceptions = array_map('trim', $exceptions);
        $exceptions = array_filter($exceptions);

        $columnManager = $this->getGridColumnManager($this->getGrid($gridName));
        $columnManager->open();
        $columnManager->hideAllColumns($exceptions);
        $columnManager->close();
    }

    /**
     * Show all columns in grid except mentioned
     *
     * @When /^(?:|I) show all columns in grid except (?P<exceptions>(?:[^"]|\\")*)$/
     * @When /^(?:|I) show all columns in "(?P<gridName>[^"]+)" except (?P<exceptions>(?:[^"]|\\")*)$/
     * @When /^(?:|I) show all columns in grid$/
     * @When /^(?:|I) show all columns in "(?P<gridName>[^"]+)"$/
     *
     * @param string $exceptions
     * @param string|null $gridName
     */
    public function iShowAllColumnsInGrid($exceptions = '', $gridName = null): void
    {
        $exceptions = explode(',', $exceptions);
        $exceptions = array_map('trim', $exceptions);
        $exceptions = array_filter($exceptions);

        $columnManager = $this->getGridColumnManager($this->getGrid($gridName));
        $columnManager->open();
        $columnManager->showAllColumns($exceptions);
        $columnManager->close();
    }

    /**
     * Asserts per page value on current page with provided amount
     *
     * @Then /^per page amount should be (\d+)$/
     */
    public function perPageAmountShouldBe($expectedAmount)
    {
        /** @var GridToolBarTools $gridToolBarTools */
        $gridToolBarTools = $this->elementFactory->createElement('GridToolBarTools');
        $perPage = $gridToolBarTools->getPerPageAmount();

        self::assertNotNull($perPage, 'Grid per page control elements not found on current page');
        self::assertEquals($expectedAmount, $perPage);
    }

    /**
     * Records in table on current page should match the count.
     * Example: Then records in grid should be 5
     *          Then records in "Customer Quotes" should be 1
     *
     * @Then /^records in current (?P<name>(?:page|[\s\w]+)) (?:|grid )?should be (?P<count>(?:\d+))$/
     * @Then /^records in grid should be (?P<count>(?:\d+))$/
     * @Then /^records in "(?P<gridName>[^"]+)" should be (?P<count>(?:\d+))$/
     */
    public function recordsInGridShouldBe($count, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $gridRows = $grid->getRows();

        self::assertCount((int)$count, $gridRows);
    }

    /**
     * Example: Then I refresh "UsersGrid" grid
     *
     * @Then /^(?:|I )refresh "(?P<gridName>[^"]+)" grid$/
     *
     * @param string $gridName
     */
    public function iRefreshGrid($gridName)
    {
        $grid = $this->getGrid($gridName);
        $refreshButton = $grid->getElement($grid->getMappedChildElementName('GridToolbarActionRefresh'));
        $refreshButton->click();
    }

    /**
     * Example: Then I reset "UsersGrid" grid
     *
     * @Then /^(?:|I )reset grid$/
     * @Then /^(?:|I )reset "(?P<gridName>[^"]+)" grid$/
     *
     * @param string $gridName
     */
    public function iResetGrid($gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $refreshButton = $grid->getElement($grid->getMappedChildElementName('GridToolbarActionReset'));
        $refreshButton->click();
    }

    /**
     * Show specified filter for grid
     *
     * @Given /^(?:|I) show filter "(?P<filter>(?:[^"]|\\")*)" in grid$/
     * @Given /^(?:|I) show filter "(?P<filter>(?:[^"]|\\")*)" in "(?P<gridName>[^"]+)" grid$/
     */
    public function iShowFilterInGrid(string $filter, string $gridName = null)
    {
        $grid = $this->getGrid($gridName);

        $filtersButton = $grid->getMappedChildElementName('GridFiltersButton');
        if ($grid->getElements($filtersButton)) {
            $grid->getElement($filtersButton)->open();
        }

        $gridSettingsButton = $grid->getElement($grid->getMappedChildElementName('GridColumnManagerButton'));
        $gridSettingsButton->click();

        $filterButton = $grid->getElement($grid->getMappedChildElementName('GridFilterManagerButton'));
        $filterButton->clickForce();

        // Actually element "GridFilterManager" points to all filter dropdowns, so we have to find out
        // which one is the actual filter manager dropdown.
        /** @var GridFilterManager[]|null $filterDropdowns */
        $filterDropdowns = $this->spin(function () use ($grid) {
            $elements = $grid->getElements($grid->getMappedChildElementName('GridFilterManager'));

            return array_filter($elements, function (Element $element) {
                return $element->isVisible();
            });
        }, 3);

        self::assertNotNull($filterDropdowns, 'Filter manager dropdown was not found');
        $filterManager = array_shift($filterDropdowns);

        $filterManager->checkColumnFilter($filter);

        $gridSettingsClose = $grid->getElement($grid->getMappedChildElementName('GridSettingsManagerClose'));
        $gridSettingsClose->click();
    }

    /**
     * Hide specified filter for grid
     *
     * @Given /^(?:|I) hide filter "(?P<filter>(?:[^"]|\\")*)" in "(?P<gridName>[^"]+)" grid$/
     *
     * @param string $filter
     * @param string $gridName
     */
    public function iHideFilterInGrid($filter, $gridName)
    {
        $grid = $this->getGrid($gridName);

        $filtersButton = $grid->getMappedChildElementName('GridFiltersButton');
        if ($grid->getElements($filtersButton)) {
            $grid->getElement($filtersButton)->open();
        }

        $gridSettingsButton = $grid->getElement($grid->getMappedChildElementName('GridColumnManagerButton'));
        $gridSettingsButton->click();

        $filterButton = $grid->getElement($grid->getMappedChildElementName('GridFilterManagerButton'));
        $filterButton->click();

        /** @var GridFilterManager $filterManager */
        $filterManager = $grid->getElement($grid->getMappedChildElementName('GridFilterManager'));
        $filterManager->uncheckColumnFilter($filter);

        $gridSettingsClose = $grid->getElement($grid->getMappedChildElementName('GridSettingsManagerClose'));
        $gridSettingsClose->click();
    }

    /**
     * @When /^(?:|I )should see following (filters|columns) in the grid settings in exact order:$/
     */
    public function iShouldSeeFollowingFiltersColumnsInExactOrder(TableNode $table)
    {
        $expectedItems = [];
        foreach ($table->getRows() as $item) {
            $expectedItems[] = $item[0];
        }

        $actualTable = $this->elementFactory->createElement('GridFilterManager');
        $availableItems = $actualTable->findAll('css', 'td.title-cell label');

        foreach ($availableItems as $exactIndex => $itemName) {
            self::assertTrue(isset($expectedItems[$exactIndex]), 'Number of actual items exceeds expected');
            self::assertEquals(
                $expectedItems[$exactIndex],
                $itemName->getText(),
                'Expected items/order does not match'
            );
        }
    }

    /**
     * @When /^(?:|I )should see following filters in the grid settings:$/
     */
    public function iShouldSeeFollowingFiltersInGridSettings(TableNode $table)
    {
        $linkElement = $this->elementFactory->findElementContainsByXPath('Tab Link', 'Filters', false);
        $linkElement->click();

        $expectedItems = [];
        foreach ($table->getRows() as $item) {
            $expectedItems[] = $item[0];
        }

        $actualTable = $this->elementFactory->createElement('GridFilterManager');
        $availableItems = $actualTable->findAll('css', 'td.title-cell label');
        $availableValues = array_map(function (NodeElement $item) {
            return $item->getText();
        }, $availableItems);
        $expectedValues = array_values($expectedItems);
        $diff = array_diff($expectedValues, $availableValues);
        self::assertEmpty($diff, sprintf('Attributes: %s are not present in the grid', implode(',', $diff)));
    }

    /**
     * @When /^(?:|I )should not see following filters in the grid settings:$/
     */
    public function iShouldNotSeeFollowingFiltersInGridSettings(TableNode $table)
    {
        $linkElement = $this->elementFactory->findElementContainsByXPath('Tab Link', 'Filters', false);
        $linkElement->click();

        $expectedItems = [];
        foreach ($table->getRows() as $item) {
            $expectedItems[] = $item[0];
        }

        $actualTable = $this->elementFactory->createElement('GridFilterManager');
        $availableItems = $actualTable->findAll('css', 'td.title-cell label');
        $availableValues = array_map(function (NodeElement $item) {
            return $item->getText();
        }, $availableItems);
        $expectedValues = array_values($expectedItems);
        $intersect = array_intersect($availableValues, $expectedValues);
        self::assertEmpty($intersect, sprintf('Attributes: %s are present in the grid', implode(',', $intersect)));
    }

    /**
     * @When /^(?:|I )should see following columns in the grid settings:$/
     */
    public function iShouldSeeFollowingColumnsInGridSettings(TableNode $table)
    {
        $linkElement = $this->elementFactory->findElementContainsByXPath('Tab Link', 'Grid', false);
        $linkElement->click();

        $expectedItems = [];
        foreach ($table->getRows() as $item) {
            $expectedItems[] = $item[0];
        }

        $actualTable = $this->elementFactory->createElement('GridColumnManager');
        $availableItems = $actualTable->findAll('css', 'td.title-cell label');
        $availableValues = array_map(function (NodeElement $item) {
            return $item->getText();
        }, $availableItems);
        $expectedValues = array_values($expectedItems);
        $diff = array_diff($expectedValues, $availableValues);
        self::assertEmpty($diff, sprintf('Attributes: %s are not present in the grid', implode(',', $diff)));
    }

    /**
     * @When /^(?:|I )should not see following columns in the grid settings:$/
     */
    public function iShouldNotSeeFollowingColumnsInGridSettings(TableNode $table)
    {
        $linkElement = $this->elementFactory->findElementContainsByXPath('Tab Link', 'Grid', false);
        $linkElement->click();

        $expectedItems = [];
        foreach ($table->getRows() as $item) {
            $expectedItems[] = $item[0];
        }

        $actualTable = $this->elementFactory->createElement('GridColumnManager');
        $availableItems = $actualTable->findAll('css', 'td.title-cell label');
        $availableValues = array_map(function (NodeElement $item) {
            return $item->getText();
        }, $availableItems);
        $expectedValues = array_values($expectedItems);
        $intersect = array_intersect($availableValues, $expectedValues);
        self::assertEmpty($intersect, sprintf('Attributes: %s are present in the grid', implode(',', $intersect)));
    }

    /**
     * @When /^I select following records in "(?P<name>[\s\w]+)" grid:$/
     * @When /^I select following records in grid:$/
     * @param TableNode $table
     * @param string $name
     */
    public function iSelectFollowingRecordsInGrid(TableNode $table, $name = 'Grid')
    {
        $grid = $this->getGrid($name);

        foreach ($table->getRows() as $index => $record) {
            $grid->getRowByContent(reset($record))
                ->checkMassActionCheckbox();
        }
    }

    /**
     * @Given /^I should not see "(?P<gridName>[\s\w]+)" grid$/
     */
    public function iShouldNotSeeGrid($gridName = 'Grid')
    {
        $grid = $this->elementFactory->createElement($gridName);

        self::assertFalse(
            $grid->isIsset(),
            sprintf('Grid "%s" was found on page, but it should not.', $gridName)
        );
    }

    /**
     * @Given /^I should see "(?P<gridName>[\s\w]+)" grid$/
     */
    public function iShouldSeeGrid($gridName = 'Grid')
    {
        $grid = $this->elementFactory->createElement($gridName);

        self::assertTrue(
            $grid->isIsset(),
            sprintf('Grid "%s" was not found on page', $gridName)
        );
    }

    /**
     * Example: I should see "Preview Image" element in grid row containing "PSKU1" for grid "gridName"
     *
     * @When /^(?:|I )I should see "(?P<element>[^"]+)" element in grid row containing "(?P<content>[^"]+)"$/
     * @When /^(?:|I )I should see "(?P<element>[^"]+)" element in grid row containing "(?P<content>[^"]+)"
     * for "(?P<gridName>[^"]+)"$/
     */
    public function iShouldSeeElementInGridRowContainingContent(
        string $elementName,
        string $content,
        string $gridName = null
    ) {
        $grid = $this->getGrid($gridName);
        $rowElement = $grid->getRowByContent($content);

        self::assertNotNull($rowElement, sprintf('There is no row containing "%s"', $content));
        self::assertTrue($rowElement->isValid(), sprintf('There is no row containing "%s"', $content));

        $element = $rowElement->getElement($elementName);
        self::assertNotNull(
            $element,
            sprintf('There is no row with content "%s" containing element "%s"', $content, $elementName)
        );
        self::assertTrue(
            $element->isValid(),
            sprintf('There is no row with content "%s" containing element "%s"', $content, $elementName)
        );
    }

    /**
     * Example: I should see following elements in "Grid" grid:
     *            | Action System Button  |
     *            | Action Default Button |
     *            | Filter Button         |
     * @When /^(?:|I )should see following elements in grid:$/
     * @When /^(?:|I )should see following elements in "(?P<gridName>[^"]+)":$/
     * @When /^(?:|I )should see following elements in "(?P<toolbar>[\w\s]+)" for grid:$/
     * @When /^(?:|I )should see following elements in "(?P<toolbar>[\w\s]+)" for "(?P<gridName>[^"]+)":$/
     */
    public function iShouldSeeElementsInGrid(TableNode $table, $toolbar = null, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $rows = $table->getRows();

        if (!is_null($toolbar)) {
            $toolbar = $grid->getElement($toolbar);

            foreach ($rows as $item) {
                $element = $this->createElement(reset($item), $toolbar);
                self::assertTrue(
                    $element->isIsset(),
                    sprintf('Element "%s" not found on the page', reset($item))
                );
            }
        } else {
            foreach ($rows as $item) {
                self::assertTrue(
                    $grid->getElement(reset($item))->isIsset(),
                    sprintf('Element "%s" not found on the page', reset($item))
                );
            }
        }
    }

    /**
     * Example: I should not see following elements in "Grid" grid:
     *            | Action System Button  |
     *            | Action Default Button |
     *            | Filter Button         |
     * @When /^(?:|I )should not see following elements in grid:$/
     * @When /^(?:|I )should not see following elements in "(?P<gridName>[^"]+)":$/
     * @When /^(?:|I )should not see following elements in "(?P<toolbar>[\w\s]+)" for grid:$/
     * @When /^(?:|I )should not see following elements in "(?P<toolbar>[\w\s]+)" for "(?P<gridName>[^"]+)":$/
     */
    public function iShouldNotSeeElementsInGrid(TableNode $table, $toolbar = null, $gridName = null)
    {
        $grid = $this->getGrid($gridName);
        $rows = $table->getRows();

        if (!is_null($toolbar)) {
            $toolbar = $grid->getElement($toolbar);

            foreach ($rows as $item) {
                $element = $this->createElement(reset($item), $toolbar);
                self::assertFalse(
                    $element->isIsset(),
                    sprintf('Element "%s" is exist on the page', reset($item))
                );
            }
        } else {
            foreach ($rows as $item) {
                self::assertFalse(
                    $grid->getElement(reset($item))->isIsset(),
                    sprintf('Element "%s" is exist on the page', reset($item))
                );
            }
        }
    }

    /**
     * @param string $stringNumber
     *
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
                return (int)$stringNumber;
        }
    }

    /**
     * @param GridInterface|Element $grid
     * @param string $element
     * @return GridPaginator|Element
     */
    private function getGridPaginator($grid, $element = 'GridToolbarPaginator')
    {
        return $this->createElement($grid->getMappedChildElementName($element), $grid);
    }

    /**
     * @param string $gridName
     * @return GridFilters|Element
     */
    private function getGridFilters($gridName)
    {
        $filters = $this->elementFactory->createElement($gridName . 'Filters');
        if (!$filters->isVisible()) {
            $gridToolbarActions = $this->elementFactory->createElement($gridName . 'ToolbarActions');
            if ($gridToolbarActions->isVisible()) {
                $gridToolbarActions->getActionByTitle('Filter Toggle')->click();
            }

            $filterState = $this->elementFactory->createElement($gridName . 'FiltersState');
            if ($filterState->isValid() && $filterState->isVisible()) {
                $filterState->click();
            }
        }

        return $filters;
    }

    /**
     * @param GridInterface|Element $grid
     * @return GridColumnManager|Element
     */
    private function getGridColumnManager($grid)
    {
        /** @var GridColumnManager $columnManager */
        $columnManager = $this->createElement($grid->getMappedChildElementName('GridColumnManager'), $grid);
        $columnManager->setGrid($grid);

        return $columnManager;
    }

    /**
     * @param string $button
     * @param string|null $gridName
     */
    private function pressPaginationControlButton($button, $gridName = null)
    {
        $grid = $this->getGrid($gridName);

        $gridPaginator = $this->createElement($grid->getMappedChildElementName('GridToolbarPaginator'), $grid);
        $gridPaginator->pressButton($button);
    }

    /**
     * Example: I should see next rows in "Discounts" table
     *   | Description | Discount |
     *   | Amount      | -$2.00   |
     *
     * @Then /^(?:|I )should see next rows in "(?P<elementName>[\w\s]+)" table$/
     * @Then /^(?:|I )should see following rows in "(?P<elementName>[\w\s]+)" table$/
     * @param TableNode $expectedTableNode
     * @param string $elementName
     */
    public function iShouldSeeNextRowsInTable(TableNode $expectedTableNode, $elementName)
    {
        /** @var Table $table */
        $table = $this->createElement($elementName);

        static::assertInstanceOf(Table::class, $table, sprintf('Element should be of type %s', Table::class));

        $expectedRows = $expectedTableNode->getRows();
        $headers = array_shift($expectedRows);
        $actualRows = array_map(function (TableRow $row) use ($headers) {
            return $row->getCellValues($headers);
        }, $table->getRows());

        foreach ($expectedRows as $expectedRow) {
            self::assertContainsEquals($expectedRow, $actualRows);
        }
    }

    /**
     * Example: I should see next rows in "Discounts" table in the exact order
     *   | Description | Discount |
     *   | Amount 1     | -$2.00   |
     *   | Amount 2     | -$5.00   |
     *
     * @Then /^(?:|I )should see next rows in "(?P<elementName>[\w\s]+)" table in the exact order$/
     * @param TableNode $expectedTableNode
     * @param string $elementName
     */
    public function iShouldSeeExactlyNextRowsInTable(TableNode $expectedTableNode, $elementName)
    {
        /** @var Table $table */
        $table = $this->createElement($elementName);

        static::assertInstanceOf(Table::class, $table, sprintf('Element should be of type %s', Table::class));

        $expectedRows = $expectedTableNode->getRows();
        $headers = array_shift($expectedRows);
        $actualRows = array_map(function (TableRow $row) use ($headers) {
            return $row->getCellValues($headers);
        }, $table->getRows());

        self::assertEquals(array_values($expectedRows), array_values($actualRows));
    }

    /**
     * Example: I should see no records in "Discounts" table
     *
     * @Then /^I should see no records in "(?P<elementName>[\w\s]+)" table$/
     * @param string $elementName
     */
    public function iShouldSeeNoRecordsInTable($elementName)
    {
        /** @var Table $table */
        $table = $this->createElement($elementName);

        static::assertInstanceOf(Table::class, $table, sprintf('Element should be of type %s', Table::class));

        $rows = $table->getRows();
        self::assertCount(0, $rows);
    }

    //@codingStandardsIgnoreStart
    /**
     * Example: I should see mass action checkbox in row with "shirt_main" content for grid
     * Example: I should see mass action checkbox in row with "shirt_main" content for "Frontend Grid"
     *
     * @Then /^I should see mass action checkbox in row with (?P<content>(?:[^"]|\\")*) content for grid$/
     * @Then /^I should see mass action checkbox in row with (?P<content>(?:[^"]|\\")*) content for "(?P<gridName>[^"]+)"$/
     */
    //@codingStandardsIgnoreEnd
    public function iShouldSeeMassActionCheckbox(string $content, string $gridName = null)
    {
        static::assertTrue(
            $this->getGrid($gridName)->getRowByContent($content)->hasMassActionCheckbox(),
            sprintf('Grid row with "%s" content has no mass action checkbox in it', $content)
        );
    }

    //@codingStandardsIgnoreStart
    /**
     * Example: I should not see mass action checkbox in row with "shirt_main" content for grid
     * Example: I should not see mass action checkbox in row with "shirt_main" content for "Frontend Grid"
     *
     * @Then /^I should not see mass action checkbox in row with (?P<content>(?:[^"]|\\")*) content for grid$/
     * @Then /^I should not see mass action checkbox in row with (?P<content>(?:[^"]|\\")*) content for "(?P<gridName>[^"]+)"$/
     */
    //@codingStandardsIgnoreEnd
    public function iShouldNotSeeMassActionCheckbox(string $content, string $gridName = null)
    {
        static::assertFalse(
            $this->getGrid($gridName)->getRowByContent($content)->hasMassActionCheckbox(),
            sprintf('Grid row with "%s" content has mass action checkbox in it', $content)
        );
    }

    /**
     * Example: I should see next options in "Some select element":
     *   | Please select |
     *   | Option A      |
     *   | Option B      |
     *
     * @Then /^(?:|I )should see next options in "(?P<selectElementName>[\w\s]+)"/
     * @param TableNode $expectedTableNode
     * @param string $selectElementName
     */
    public function iShouldSeeNextOptionsInSelect(TableNode $expectedTableNode, $selectElementName)
    {
        $selectElement = $this->createElement($selectElementName);
        /** @var Element[] $optionElements */
        $optionElements = $selectElement->findAll('css', 'option');

        $optionValues = [];
        foreach ($optionElements as $optionElement) {
            $optionValues[] = trim($optionElement->getText()); //Removes spaces at the beginning
        }

        $expectedRows = $expectedTableNode->getRows();

        foreach ($expectedRows as $rowKey => $expectedRow) {
            self::assertContains(
                $expectedRow[0],
                $optionValues,
                sprintf('There is no such sorting option: "%s"', $expectedRow[0])
            );
        }
    }

    /**
     * @param string $value
     * @param Table $grid
     * @param int $rowNumber
     * @param string $columnTitle
     * @return array
     */
    private function normalizeValueByMetadata($value, Table $grid, $rowNumber, $columnTitle): array
    {
        $metadata = null;
        $metadataPos = strpos($columnTitle, '{{');
        if ($metadataPos > 0) {
            $metadata = substr($columnTitle, $metadataPos);
            $metadata = trim(str_replace(['{{', '}}'], ['{', '}'], $metadata));
            $metadata = json_decode($metadata, true);
            $columnTitle = trim(substr($columnTitle, 0, $metadataPos));
        }

        $cellValue = $grid->getRowByNumber($rowNumber)->getCellValue($columnTitle);
        if ($metadata && array_key_exists('type', $metadata) && $metadata['type'] === 'array') {
            $separator = $metadata['separator'] ?? ',';
            $value = explode($separator, $value);
            $cellValue = explode($separator, $cellValue);
            $value = array_map('trim', $value);
            $cellValue = array_map('trim', $cellValue);
            sort($value);
            sort($cellValue);
        }

        if ($cellValue instanceof \DateTime) {
            $value = new \DateTime($value);
        }

        return [$value, $cellValue, $columnTitle];
    }
}
