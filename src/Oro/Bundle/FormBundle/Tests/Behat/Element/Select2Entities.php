<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\FormBundle\Tests\Behat\Context\ClearableInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

/**
 * Fill field with many entities e.g. contexts field in send email form
 */
class Select2Entities extends Element implements ClearableInterface
{
    /**
     * {@inheritdoc}
     */
    public function setValue($values)
    {
        $this->clear();
        $this->focus();
        $values = true === is_array($values) ? $values : [$values];

        foreach ($values as $value) {
            $this->type($value);
            $searchResults = $this->getSearchResults();
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
     * Return element text
     * @param NodeElement $element
     * @return string
     */
    public static function elementText(NodeElement $element)
    {
        return $element->getText();
    }

    /**
     * @return array|NodeElement[]
     */
    public function getSearchResults()
    {
        $resultHolder = $this->getPage()->findVisible('css', 'div.select2-drop-active ul.select2-result-sub');

        if (!$resultHolder) {
            $resultHolder = $this->getPage()->findVisible('css', 'div.select2-drop-active ul.select2-results');
        }

        if ($resultHolder) {
            return $resultHolder->findAll('css', 'li');
        }

        return [];
    }

    /**
     * Type into input
     * @param string $value
     */
    public function type($value)
    {
        $this->getDriver()->typeIntoInput($this->getXpath(), $value);
        $this->getPage()->waitFor(5, function (NodeElement $element) {
            return null === $element->find('css', 'ul.select2-results li.select2-searching');
        });
    }

    /**
     * Focus on input and wait until it will be focused
     */
    public function focus()
    {
        parent::focus();

        $this->waitFor(5, function (NodeElement $element) {
            return $element->hasClass('select2-focused') && !$element->hasClass('select2-active');
        });
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $driver = $this->getDriver();
        $closeLinks = $this->getParent()->getParent()
            ->findAll('css', 'a.select2-search-choice-close');

        /** @var NodeElement $closeLink */
        foreach ($closeLinks as $closeLink) {
            // Click with javascript because element is not visible, only pseudo class ::before
            // https://symfony.com/doc/current/components/css_selector.html#limitations-of-the-cssselector-component
            $driver->executeJsOnXpath($closeLink->getXpath(), '{{ELEMENT}}.click()');
        }
    }
}
