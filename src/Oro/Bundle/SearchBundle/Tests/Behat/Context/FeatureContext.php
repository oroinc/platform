<?php

namespace Oro\Bundle\SearchBundle\Tests\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Symfony\Component\DomCrawler\Crawler;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
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
            preg_match('/([\w\s]+).+(\d+)/', $link->textContent, $matches);
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
     * @Given /^(?:|I )filter result by "(?P<type>[^"]+)" type$/
     */
    public function iFilterResultByEntityType($type)
    {
        $typeLink = $this->getPage()->find('css', ".search-entity-types-column ul li a:contains('$type')");
        self::assertNotNull($typeLink, "Columt type '$type' not found");

        $typeLink->click();
    }

    /**
     * @Given /^(?:|I )select "(?P<type>[^"]+)" from search types$/
     */
    public function iSelectFromSearchTypes($type)
    {
        $typeSelectButton = $this->createElement('TypeSelectButton');
        self::assertTrue($typeSelectButton->isValid());

        $typeSelectButton->press();

        $selector = $this->createElement('TypeSelector');
        $typeLink = $selector->find('css', "li a:contains('$type')");
        self::assertTrue($typeLink->isValid(), "Type '$type' not found in select entities type");

        $typeLink->click();
    }

    /**
     * @Given /^(?:|I )should see (?P<number>\d+) search suggestion(?:|s)$/
     */
    public function iShouldSeeSearchSuggestion($number)
    {
        $suggestions = $this->createElement('SearchSuggestionsDropdown');
        $this->spin(function () use ($suggestions) {
            return $suggestions->isVisible();
        });
        self::assertTrue($suggestions->isValid(), 'Search suggestions not found');
        self::assertCount(
            (int) $number,
            $suggestions->findAll('css', 'li')
        );
    }
}
