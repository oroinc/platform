<?php

namespace Oro\Bundle\SearchBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Symfony\Component\DomCrawler\Crawler;

class SearchContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * Assert entity types and result count on search results page
     * Example: And I should see following search entity types:
     *            | Type            | N | isSelected |
     *            | All             | 3 | yes        |
     *            | Business Units  | 1 |            |
     *            | Calendar Events | 1 |            |
     *            | Organizations   | 1 |            |
     *
     * @Then /^(?:|I )should see following search entity types:$/
     */
    public function iShouldSeeFollowingSearchEntityTypes(TableNode $table)
    {
        $entityTypes = $this->getPage()->find('css', '.search-entity-types-column');
        self::assertNotNull($entityTypes, 'Search entity types column not found');

        $crawler = new Crawler($entityTypes->getHtml());
        $links = [];

        /** @var \DOMElement $link */
        foreach ($crawler->filter('ul li') as $link) {
            preg_match('/([\w\s]+).(\d+)/', $link->textContent, $matches);
            $links[trim($matches[1])] = [
                'number' => $matches[2],
                'isSelected' => false !== stripos($link->getAttribute('class'), 'selected'),
            ];
        }

        foreach ($table as $row) {
            $type = $row['Type'];
            self::assertTrue(array_key_exists($type, $links), sprintf('Type "%s" not found', $type));
            self::assertSame($row['N'], $links[$type]['number'], sprintf(
                'Expected %s number of "%s" type, but got %s',
                $row['N'],
                $type,
                $links[$type]['number']
            ));
            self::assertEquals(
                (bool) $row['isSelected'],
                $links[$type]['isSelected'],
                sprintf(
                    'Expected that "%s" type is %s, but it is not',
                    $type,
                    (bool) $row['isSelected'] ? 'active' : 'not active'
                )
            );
        }
    }

    /**
     * Assert search results with its types
     * Example: And I should see following search results:
     *            | Title                | Type          |
     *            | Common Organization  | Organization  |
     *            | Common Event         | Calendar      |
     *            | Common Business Unit | Business Unit |
     *
     * @Then /^(?:|I )should see following search results:$/
     */
    public function iShouldSeeFollowingSearchResults(TableNode $table)
    {
        $gridBody = $this->getPage()->find('css', '.grid-container tbody');
        self::assertNotNull($gridBody, 'Search results table not found');

        $crawler = new Crawler($gridBody->getHtml());
        $results = [];
        $crawler->filter('tr')->each(function (Crawler $tr) use (&$results) {
            $results[trim($tr->filter('h1')->first()->text())] = [
                'type' => trim($tr->filter('div.sub-title')->first()->text()),
            ];
        });

        foreach ($table as $row) {
            $title = $row['Title'];
            $type  = $row['Type'];

            self::assertTrue(array_key_exists($title, $results), sprintf('Result "%s" not found', $title));
            self::assertEquals($type, $results[$title]['type'], sprintf(
                'Expect that result "%s" has "%s" type, but it has "%s" type',
                $title,
                $type,
                $results[$title]['type']
            ));
        }
    }

    /**
     * Filter search results by entity type
     * Example: Given I filter result by "Calendar" type
     *
     * @Given /^(?:|I )filter result by "(?P<type>[^"]+)" type$/
     */
    public function iFilterResultByEntityType($type)
    {
        $typeLink = $this->getPage()->find('css', ".search-entity-types-column ul li a:contains('$type')");
        self::assertNotNull($typeLink, "Columt type '$type' not found");

        $typeLink->click();
    }

    /**
     * Select search type of entity in search type
     * Example: Given I click "Search"
     *          And I select "Business Unit" from search types
     *          And I type "Some kind of Business Unit" in "search"
     *
     * @Given /^(?:|I )select "(?P<type>[^"]+)" from search types$/
     */
    public function iSelectFromSearchTypes($type)
    {
        $typeSelectElement = $this->createElement('TypeSelectElement');
        self::assertTrue($typeSelectElement->isValid());

        $typeSelectElement->press();

        $list = $this->createElement('TypeSelectList');
        $option = $list->find('xpath', "//li[./*[text()='$type']]");
        self::assertTrue($option->isValid(), "Type '$type' not found in select entities type");

        $option->click();
    }

    /**
     * Clear value of search type of entity in search type
     * Example: Given I click "Search"
     *          And I clear search type select
     *          And I type "Some query" in "search"
     *
     * @Given /^(?:|I )clear search type select$/
     */
    public function iClearSearchTypes()
    {
        $typeSelectElement = $this->createElement('TypeSelectElement');
        self::assertTrue($typeSelectElement->isValid());

        $clearIcon = $typeSelectElement->find('css', ".select2-search-choice-close");

        self::assertTrue($clearIcon->isValid(), "Clear icon is not found in entities select element");

        $clearIcon->click();
    }

    /**
     * Assert number of suggestion in entity search after typing in search input area
     * Example: Given I click "Search"
     *          And type "Common" in "search"
     *          And I should see 3 search suggestions
     *
     * @Given /^(?:|I )should see (?P<number>\d+) search suggestion(?:|s)$/
     */
    public function iShouldSeeSearchSuggestion($number)
    {
        $suggestions = $this->createElement('SearchSuggestionList');
        $this->spin(function () use ($suggestions) {
            return $suggestions->isVisible();
        });
        self::assertTrue($suggestions->isValid(), 'Search suggestions not found');

        // wait for search delay on user input and getting data from API
        usleep(300000);
        $this->waitForAjax();

        self::assertCount(
            (int) $number,
            $suggestions->findAll('css', 'li:not(.loading)')
        );
    }
}
