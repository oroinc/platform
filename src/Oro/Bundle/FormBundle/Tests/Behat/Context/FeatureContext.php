<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Context;

use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /**
     * @When /^I choose (?P<tableContent>([\w\s]+)) "(?P<value>([\w\s]+))" in (?P<rowNum>([\d]+)) row$/
     */
    public function chooseValueInRow($tableContent, $value, $rowNum)
    {
        /** @var Table $table */
        $table = $this->findElementContains('Table', $tableContent);
        $row = $table->getRowByNumber($rowNum);
        $row->find('css', 'button.entity-select-btn')->click();
        $this->waitForAjax();
        $priceList = $this->spin(function (FeatureContext $context) use ($value) {
            $priceList = $context->getPage()->find('named', ['content', $value]);
            return $priceList ? $priceList : false;
        });
        $priceList->click();
        $this->waitForAjax();
    }

    /**
     * @When /^I drag (?P<rowNum>([\d]+)) row on top in "(?P<tableContent>([\w\s]+))" table$/
     */
    public function dragRowOnTop($rowNum, $tableContent)
    {
        /** @var Table $table */
        $table = $this->findElementContains('Table', $tableContent);
        $table->getRowByNumber($rowNum);
        $class = str_replace(' ', '.', $table->getAttribute('class'));
        $this->getSession()->executeScript('
            $(document).ready(function(){
                var table = $("table.' . $class . ' .sortable-wrapper").closest("table");
                var lastRow = table.find("tbody tr").eq(' . ($rowNum - 1) . ');
                table.find("tbody").prepend(lastRow);
                table.find(".sortable-wrapper").sortable("option", "stop")();
            })
        ');
    }

    /**
     * @Then I should see drag-n-drop icon present in :tableContent table
     */
    public function assertDragNDropIconOnTableLine($tableContent)
    {
        /** @var Table $table */
        $table = $this->findElementContains('Table', $tableContent);
        self::assertTrue(
            $table->has('css', 'tbody tr i.handle'),
            "There is no Drag-n-drop icon among rows in '$tableContent' table"
        );
    }

    /**
     * Click on button or link on left panel in configuration menu
     * Example: Given I click "Edit" on left panel
     * Example: When I click "Save and Close" on left panel
     *
     * @When /^(?:|I )click "(?P<button>(?:[^"]|\\")*)" on left panel$/
     */
    public function pressButtonOnLeftPanel($button)
    {
        $leftPanel = $this->getPage()->find('css', 'div.left-panel');
        try {
            $leftPanel->pressButton($button);
        } catch (ElementNotFoundException $e) {
            if ($this->getSession()->getPage()->hasLink($button)) {
                $leftPanel->clickLink($button);
            } else {
                throw $e;
            }
        }
    }
}
