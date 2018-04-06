<?php

namespace Oro\Bundle\UIBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class DragAndDropContext extends OroFeatureContext implements OroPageObjectAware
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
     * @When /^(?:|I )resize Sidebar Drag Handler by vector \[([-?\d]+),([-?\d]+)\]$/
     * @param string $elementName
     * @param integer $xOffset
     * @param integer $yOffset
     */
    public function iResizeSidebarByOffset($xOffset = 0, $yOffset = 0)
    {
        $this->oroMainContext->dragAndDropElementToAnotherOne(
            "Sidebar Drag Handler",
            null,
            (int) $xOffset,
            (int) $yOffset
        );
    }
}
