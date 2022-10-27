<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\SelectorManipulator;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use WebDriver\Key;

/**
 * The context for testing Query Designer related features.
 */
class QueryDesignerContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @When /^(?:|I )add the following columns:$/
     */
    public function iAddTheFollowingColumns(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            [$column, $functionName, $label] = array_pad($row, 3, null);
            $this->addColumns(explode('->', $column), $functionName, $label);
        }
    }

    /**
     * @When /^(?:|I )add the following grouping columns:$/
     */
    public function iAddTheFollowingGroupingColumns(TableNode $table)
    {
        foreach ($table->getRows() as [$column]) {
            $this->addGroupingColumns(explode('->', $column));
        }
    }

    /**
     * Selects field for "Grouping by date -> Date Field"
     * Example: When I select "Created At" from date grouping field
     *
     * @When /^(?:|I )select "(?P<field>(?:[^"]|\\")*)" from date grouping field$/
     *
     * @param string $field
     */
    public function selectDateGroupingField($field)
    {
        $field = $this->fixStepArgument($field);
        $dateField = $this->createElement('Date Field')->getParent();
        $dateField->clickLink('Choose a field');
        $this->selectField([$field]);
    }

    /**
     * @Given /^(?:|I )should see "(?P<column>(?:[^"]|\\")*)" grouping column/
     */
    public function shouldSeeGroupingColumn(string $column)
    {
        $this->checkGroupingColumn($column, true);
    }

    /**
     * @Given /^(?:|I )should not see "(?P<column>(?:[^"]|\\")*)" grouping column/
     */
    public function shouldNotSeeGroupingColumn(string $column)
    {
        $this->checkGroupingColumn($column, false);
    }

    /**
     * @param string[] $columns
     * @param string $functionName
     * @param string $label
     */
    private function addColumns($columns, $functionName, $label)
    {
        $this->clickLinkInColumnDesigner('Choose a field');
        $this->selectField($columns);
        if ($functionName) {
            $this->setFunctionValue($functionName);
        }
        if ($label) {
            $this->setLabel($label);
        }
        $this->clickLinkInColumnDesigner('Add');
    }

    /**
     * @param string[] $columns
     */
    private function addGroupingColumns($columns)
    {
        $this->clickLinkInGroupingDesigner('Choose a field');
        $this->selectField($columns);
        $this->clickLinkInGroupingDesigner('Add');
    }

    /**
     * @param string $link
     */
    private function clickLinkInColumnDesigner($link)
    {
        $columnDesigner = $this->createElement('Columns');
        $columnDesigner->clickLink($link);
    }

    /**
     * @param string $link
     */
    private function clickLinkInGroupingDesigner($link)
    {
        $groupingDesigner = $this->createElement('Grouping');
        $groupingDesigner->clickLink($link);
    }

    /**
     * @param string[] $path
     */
    private function selectField(array $path)
    {
        $selectorManipulator = new SelectorManipulator();
        foreach ($path as $key => $column) {
            $typeTitle = $key === count($path) - 1 ? 'Fields' : 'Related entities';
            $this->getPage()
                ->find('xpath', "//div[@id='select2-drop']/div/input")
                ->setValue($column);
            $selector = $selectorManipulator->getExactMatchXPathSelector(
                sprintf("//div[@id='select2-drop']//div[text()='%s']/..//div", $typeTitle),
                $column
            );
            $fieldElement = $this->getPage()
                ->find($selector['type'], $selector['locator']);
            if (!$fieldElement) {
                throw new \RuntimeException(sprintf('The field "%s" not found.', $column));
            }
            $fieldElement->click();
        }
    }

    /**
     * @param string $value
     */
    private function setFunctionValue($value)
    {
        $columnFunction = $this->createElement('Column Function');
        $columnFunction->selectOption($value);
    }

    private function setLabel(string $value)
    {
        $columnLabel = $this->createElement('Column Label');
        $columnLabel->setValue($value);
    }

    private function checkGroupingColumn(string $column, bool $isShouldSee): void
    {
        $this->clickLinkInGroupingDesigner('Choose a field');
        $this->waitForAjax();

        $searchResult = $this->spin(function () use ($column) {
            $searchResult = $this->getPage()
                ->find(
                    'xpath',
                    "//div[@id='select2-drop']//div[contains(., '{$column}')]"
                );
            if ($searchResult && $searchResult->isVisible()) {
                return $searchResult;
            }

            return null;
        }, 5);

        if ($isShouldSee === true) {
            self::assertNotNull(
                $searchResult,
                sprintf('The field "%s" was not found in the grouping columns.', $column)
            );
        } else {
            self::assertNull(
                $searchResult,
                sprintf('The field "%s" appears in the grouping columns, but it should not.', $column)
            );
        }

        $this->getDriver()->typeIntoInput("//div[@id='select2-drop']/div/input", Key::ESCAPE);
    }
}
