<?php

namespace Oro\Bundle\ActionBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\ActionBundle\Tests\Behat\Element\PageActionButtonsContainerElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class OroActionContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

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
     *
     * @param TableNode $table
     */
    public function iShouldSeeAvailablePageActions(TableNode $table)
    {
        $actions = $this->getActionLabels();

        foreach ($table->getRows() as $row) {
            self::assertContains($row[0], $actions, '', true);
        }
    }

    /**
     * @Given /^(?:|I )should not see following page actions:$/
     *
     * @param TableNode $table
     */
    public function iShouldNotSeeFollowingPageActions(TableNode $table)
    {
        $actions = $this->getActionLabels();

        foreach ($table->getRows() as $row) {
            self::assertNotContains($row[0], $actions, '', true);
        }
    }

    /**
     * @return array
     */
    protected function getActionLabels()
    {
        /** @var PageActionButtonsContainerElement $actionsContainer */
        $actionsContainer = $this->elementFactory->createElement('PageActionButtonsContainer');
        $actions = $actionsContainer->getPageActions();

        return array_map(function (Element $action) {
            return $action->getText();
        }, $actions);
    }
}
