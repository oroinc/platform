<?php

namespace Oro\Bundle\UIBundle\Tests\Behat\Context;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Oro\Bundle\UIBundle\Tests\Behat\Element\ContextSelector;

class ContextSelectorContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @Given /^(?:|I )select "([^"]*)" context$/
     *
     * @param string $context
     */
    public function iSelectContext($context)
    {
        /** @var ContextSelector $contextSelector */
        $contextSelector = $this->elementFactory->createElement('ContextSelector');
        $contextSelector->select($context);
    }
}
