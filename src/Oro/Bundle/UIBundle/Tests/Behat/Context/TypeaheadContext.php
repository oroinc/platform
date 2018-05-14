<?php

namespace Oro\Bundle\UIBundle\Tests\Behat\Context;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class TypeaheadContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * Assert number of typeahead suggestions after typing in input area
     * Example: When I focus on "Text" field
     *          Then I should see 3 typeahead suggestions for "Text"
     *
     * @Given /^I should see (?P<number>\d+) typeahead suggestions for "(?P<field>[^"]+)"$/
     */
    public function iShouldSeeTypeaheadSuggestionsNumber($number, $field)
    {
        $suggestions = $this->getSuggestionsElement($field);
        self::assertTrue($suggestions->isValid(), 'Typeahead suggestions not found');
        self::assertCount(
            (int) $number,
            $suggestions->findAll('css', 'li')
        );
    }

    /**
     * Assert suggestion in typeahead suggestions after typing in input area
     * Example: When I focus on "Text" field
     *          Then I should see "users" in typeahead suggestions for "Text"
     *
     * @Given /^I should see "(?P<suggestion>[^"]+)" in typeahead suggestions for "(?P<field>[^"]+)"$/
     */
    public function iShouldSeeTypeaheadSuggestion($suggestion, $field)
    {
        $suggestions = $this->getSuggestionsElement($field);
        $link = $suggestions->find(
            'css',
            sprintf('li a:contains("%s")', $suggestion)
        );
        self::assertTrue(
            $link && $link->isValid(),
            sprintf('Suggestion "%s" not found in typeahead', $suggestion)
        );

        return $link;
    }

    /**
     * Assert suggestion in typeahead suggestions after typing in input area
     * Example: When I focus on "Text" field
     *          Then I should see "users" in typeahead suggestions for "Text"
     *
     * @Given /^I should not see "(?P<suggestion>[^"]+)" in typeahead suggestions for "(?P<field>[^"]+)"$/
     */
    public function iShouldNotSeeTypeaheadSuggestion($suggestion, $field)
    {
        $suggestions = $this->getSuggestionsElement($field);
        $link = $suggestions->find(
            'css',
            sprintf('li a:contains("%s")', $suggestion)
        );
        self::assertTrue(
            $link === null,
            sprintf('Suggestion "%s" found in typeahead', $suggestion)
        );
    }

    /**
     * Select suggestion from typeahead suggestions after typing in input area
     * Example: When I focus on "Text" field
     *          And I select "users" from typeahead suggestions for "Text"
     *
     * @Given /^I select "(?P<suggestion>[^"]+)" from typeahead suggestions for "(?P<field>[^"]+)"$/
     */
    public function iSelectTypeaheadSuggestion($suggestion, $field)
    {
        $link = $this->iShouldSeeTypeaheadSuggestion($suggestion, $field);

        if ($link) {
            $link->click();
        }
    }

    /**
     * @param string $field
     * @return Element
     */
    protected function getSuggestionsElement($field)
    {
        $field = $this->createElement($field);
        $suggestions = $this->createElement('TypeaheadSuggestionsDropdown', $field->getParent());
        $this->spin(function () use ($suggestions) {
            return $suggestions->isVisible();
        });

        return $suggestions;
    }
}
