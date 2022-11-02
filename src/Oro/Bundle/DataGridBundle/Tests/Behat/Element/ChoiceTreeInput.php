<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use WebDriver\Key;

class ChoiceTreeInput extends Element
{
    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        $valueElements = $this->getParent()->getParent()->findAll('css', 'li.select2-search-choice');

        return array_map(function (NodeElement $element) {
            return $element->getText();
        }, $valueElements);
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($values)
    {
        $this->getDriver()->waitForAjax();
        $this->focus();
        $values = true === is_array($values) ? $values : [$values];

        foreach ($values as $value) {
            $this->type($value);
            $searchResults = array_filter(
                $this->getSearchResults(),
                static function ($element) {
                    return !empty(trim($element->getText()));
                }
            );

            self::assertCount(
                1,
                $searchResults,
                sprintf(
                    'Too many results "%s" for "%s"',
                    implode(', ', array_map([$this, 'elementText'], $searchResults)),
                    $value
                )
            );
            $result = array_shift($searchResults);
            self::assertNotEquals('No matches found', $result->getText(), sprintf('No matches found for "%s"', $value));
            $result->click();
        }
    }

    /**
     * Check that the item exists/not exists in results
     */
    public function checkValue(string $value, bool $isShouldSee): void
    {
        $this->focus();
        $this->type($value);
        $this->getDriver()->waitForAjax();
        $searchResults = $this->getSearchResults();

        if ($isShouldSee === true) {
            foreach ($searchResults as $searchResult) {
                static::assertStringContainsString(
                    $value,
                    $searchResult->getText()
                );
            }
        } else {
            foreach ($searchResults as $searchResult) {
                static::assertStringNotContainsString(
                    $value,
                    $searchResult->getText()
                );
            }
        }

        $this->getDriver()->typeIntoInput($this->getXpath(), Key::ESCAPE);
    }

    /**
     * Focus on input and wait until it will be focused
     */
    public function focus()
    {
        $mask = $this->getPage()->find('css', '#select2-drop-mask');
        if ($mask && $mask->isVisible()) {
            $mask->click();
        }

        if (!empty($this->getSearchResults())) {
            parent::click();
        }
    }

    /**
     * Type into input
     * @param string $value
     */
    private function type($value)
    {
        $this->getDriver()->typeIntoInput($this->getXpath(), $value);
    }

    /**
     * @return array|NodeElement[]
     */
    private function getSearchResults()
    {
        $this->getPage()->waitFor(5, function (NodeElement $element) {
            return null === $element->find('css', 'ul.select2-results li.select2-searching');
        });

        $resultHolder = $this->getPage()->findVisible('css', 'div.select2-drop-active ul.select2-results');
        if (!$resultHolder) {
            return [];
        }

        // Wait for show one result or go further
        $resultHolder->waitFor(5, function (NodeElement $element) {
            return 1 === count($element->findAll('css', 'li'));
        });

        return $resultHolder->findAll('css', 'li');
    }

    /**
     * Return element text
     * @param NodeElement $element
     * @return string
     */
    public static function elementText(NodeElement $element)
    {
        return $element->getText();
    }
}
