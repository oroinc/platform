<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class QueryDesignerContext extends OroFeatureContext implements OroPageObjectAware
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
            $this->addColumns(explode('->', $column));
        }
    }

    /**
     * Method implements column functionality
     *
     * @param array $columns
     */
    private function addColumns($columns)
    {
        $this->clickLinkInColumnDesigner('Choose a field');
        foreach ($columns as $key => $column) {
            $typeTitle = $key === count($columns) - 1 ? 'Fields' : 'Related entities';
            $this->getPage()
                ->find(
                    'xpath',
                    "//div[@id='select2-drop']/div/input"
                )
                ->setValue($column);
            $this->getPage()
                ->find(
                    'xpath',
                    sprintf(
                        "//div[@id='select2-drop']//div[contains(.,'%s')]/..//div[contains(.,'%s')]",
                        $typeTitle,
                        $column
                    )
                )
                ->click();
        }
        $this->clickLinkInColumnDesigner('Add');
    }

    /**
     * @param string $link
     *
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     */
    private function clickLinkInColumnDesigner($link)
    {
        $columnDesigner = $this->createElement('Query Designer');
        $columnDesigner->clickLink($link);
    }
}
