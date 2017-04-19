<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @Given I add the following columns:
     *
     * @param TableNode $table
     */
    public function iAddTheFollowingColumns(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            list($column) = $row;
            $this->addColumn($column);
        }
    }

    /**
     * Method implements column functionality
     *
     * @param string $column
     */
    private function addColumn($column)
    {
        $this->clickLinkInColumnDesigner('Choose a field');
        $this->selectValue($column);
        $this->clickLinkInColumnDesigner('Add');
    }

    /**
     * @param string $value
     */
    private function selectValue($value)
    {
        $this->getPage()
            ->find(
                'xpath',
                "//div[@id='select2-drop']/div/input"
            )
            ->setValue($value);
        $this->getPage()
            ->find(
                'xpath',
                "//div[@id='select2-drop']//div[contains(., '{$value}')]"
            )
            ->click();
    }

    /**
     * @param string $link
     *
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     */
    private function clickLinkInColumnDesigner($link)
    {
        $columnDesigner = $this->getPage()->find('css', 'div#oro_report-column-form');
        $columnDesigner->clickLink($link);
    }
}
