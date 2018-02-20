<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Context;

use Behat\Mink\Element\NodeElement;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;
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

        /** @var NodeElement $priceList */
        $priceList = $this->spin(function (FeatureContext $context) use ($value) {
            $priceList = $context->getPage()->find('named', ['content', $value]);

            if ($priceList && $priceList->isVisible()) {
                return $priceList;
            }

            return false;
        });

        $priceList->click();
        $this->waitForAjax();
    }

    /**
     * @When /^I remove "(?P<value>([\w\s]+))" from (?P<tableContent>([\w\s]+))$/
     */
    public function removeRow($value, $tableContent)
    {
        /** @var Table $table */
        $table = $this->findElementContains('Table', $tableContent);
        $row = $table->getRowByContent($value);
        $row->find('css', 'button.removeRow')->click();
    }

    /**
     * @When /^I drag (?P<rowNum>([\d]+)) row to the top in "(?P<tableContent>([\w\s]+))" table$/
     */
    public function dragRowToTop($rowNum, $tableContent)
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
            });
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
            "There is no drag-n-drop icon among rows in '$tableContent' table"
        );
    }
}
