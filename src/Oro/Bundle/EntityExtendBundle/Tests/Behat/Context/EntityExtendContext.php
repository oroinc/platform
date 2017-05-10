<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Behat\Context;

use Oro\Bundle\FormBundle\Tests\Behat\Element\Select2Entity;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class EntityExtendContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @Then /^"([^"]*)" was set to "([^"]*)" and is not editable$/
     */
    public function wasSetToAndIsNotEditable($field, $isBidirectional)
    {
        /** @var Select2Entity $element */
        $element = $this->createElement($field);
        $select2Div = $element->getParent();

        static::assertEquals($isBidirectional, $this->createElement('EntitySelector', $select2Div)->getText());
        static::assertNotNull($this->createElement('Select2Entity', $select2Div)->getAttribute('disabled'));
    }

    /**
     * @Then /^I should not see "([^"]*)" entity for "([^"]*)" select$/
     */
    public function iShouldNotSeeEntityForSelect($entityName, $field)
    {
        /** @var Select2Entity $element */
        $element = $this->createElement($field);
        $element->fillSearchField($entityName);

        $suggestions = $element->getSuggestions();
        static::assertCount(1, $suggestions);
        static::assertEquals('No matches found', reset($suggestions)->getText());

        $element->close();
    }
}
