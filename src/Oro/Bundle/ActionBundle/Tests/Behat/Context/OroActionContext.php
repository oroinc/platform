<?php

namespace Oro\Bundle\ActionBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\ActionBundle\Tests\Behat\Element\PageActionButtonsContainerElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class OroActionContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @Given /^(?:|I )click on page action "([^"]*)"$/
     *
     * @param string $actionName
     */
    public function iClickOnPageAction($actionName)
    {
        /** @var PageActionButtonsContainerElement $actionsContainer */
        $actionsContainer = $this->elementFactory->createElement('PageActionButtonsContainer');
        $action = $actionsContainer->getAction($actionName);
        $action->click();
    }

    /**
     * @Given /^(?:|I )should see available page actions:$/
     */
    public function iShouldSeeAvailablePageActions(TableNode $table)
    {
        $actions = $this->getActionLabels(true);

        foreach ($table->getRows() as $row) {
            static::assertContains(\strtolower($row[0]), $actions);
        }
    }

    /**
     * @Given /^(?:|I )should not see following page actions:$/
     */
    public function iShouldNotSeeFollowingPageActions(TableNode $table)
    {
        $actions = $this->getActionLabels();

        foreach ($table->getRows() as $row) {
            static::assertNotContains(\strtolower($row[0]), $actions);
        }
    }

    protected function getActionLabels(bool $lowercase = false): array
    {
        /** @var PageActionButtonsContainerElement $actionsContainer */
        $actionsContainer = $this->elementFactory->createElement('PageActionButtonsContainer');
        $actions = $actionsContainer->getPageActions();

        return array_map(function (Element $action) use ($lowercase) {
            return ($lowercase ? \strtolower($action->getText()) : $action->getText());
        }, $actions);
    }
}
