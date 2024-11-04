<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\FormBundle\Tests\Behat\Context\ClearableInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

/**
 * Fill field with many entities e.g. contexts field in send email form
 *
 * !!! For multiselects where order matters use Select2EntitiesOrdered element instead !!!
 */
class Select2Entities extends Element implements ClearableInterface
{
    #[\Override]
    public function setValue($values)
    {
        $values = true === is_array($values) ? $values : [$values];
        $this->getDriver()->waitForAjax();
        $this->clearExcept($values);
        if (!$this->isActive()) {
            $this->focus();
        }

        foreach ($values as $value) {
            if ($this->hasValue($value)) {
                continue;
            }

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
        $this->close();
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
        $this->getPage()->waitFor(5, function (NodeElement $element) {
            return null === $element->find('css', 'ul.select2-results li.select2-searching');
        });

        $resultHolder = $this->getPage()->findVisible('css', 'div.select2-drop-active ul.select2-result-sub');

        if (!$resultHolder) {
            $resultHolder = $this->getPage()->findVisible('css', 'div.select2-drop-active ul.select2-results');
        }

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
     * Type into input
     * @param string $value
     */
    public function type($value)
    {
        $this->getDriver()->typeIntoInput($this->getXpath(), $value);
    }

    /**
     * Focus on input and wait until it will be focused
     */
    #[\Override]
    public function focus()
    {
        $mask = $this->getPage()->find('css', '#select2-drop-mask');
        if ($mask && $mask->isVisible()) {
            $mask->click();
        }

        if (!empty($this->getSearchResults())) {
            parent::click();
        }

        if (!$this->isActive()) {
            parent::click();
        }

        $this->waitFor(5, function (Select2Entities $element) {
            return $element->isActive();
        });
    }

    #[\Override]
    public function blur()
    {
        $this->close();
        parent::blur();
    }

    /**
     * Check if input is active and ready for input
     */
    public function isActive()
    {
        return $this->hasClass('select2-focused')
            && !$this->hasClass('select2-active')
            && $this->getParent()->getParent()->getParent()->hasClass('select2-container-active');
    }

    public function isOpened(): bool
    {
        return $this->getParent()->getParent()->getParent()->hasClass('select2-dropdown-open');
    }

    /**
     * Close search results dropdown
     */
    public function close()
    {
        if ($this->isOpened()) {
            $this->getDriver()->executeJsOnXpath($this->getXpath(), '$({{ELEMENT}}).select2("close")');

            if ($this->isOpened()) {
                $this->getDriver()->keyDown($this->getXpath(), 27);
            }
        }
    }

    protected function clearExcept(array $values = [])
    {
        $closeLinksXpathProto = '//*[contains(concat(" ",normalize-space(@class)," ")," select2-search-choice ")%s]'
            . '//a[contains(@class, "select2-search-choice-close")]';
        if ($values) {
            $exceptSubEls = [];
            foreach ($values as $value) {
                $exceptSubEls[] = sprintf('./div[not(text()="%s")]', $value);
            }

            $closeLinksXpath = sprintf($closeLinksXpathProto, ' and ' . implode(' and ', $exceptSubEls));
        } else {
            $closeLinksXpath = sprintf($closeLinksXpathProto, '');
        }

        $closeLinks = $this->getParent()->getParent()->findAll('xpath', $closeLinksXpath);
        if (!$closeLinks) {
            return;
        }

        /** @var NodeElement $closeLink */
        $driver = $this->getDriver();
        foreach ($closeLinks as $closeLink) {
            // Click with javascript because element is not visible, only pseudo class ::before
            // https://symfony.com/doc/current/components/css_selector.html#limitations-of-the-cssselector-component
            $driver->executeJsOnXpath($closeLink->getXpath(), '{{ELEMENT}}.click()');
        }

        $this->waitFor(5, function () use ($closeLink) {
            return !$closeLink->isValid();
        });
    }

    #[\Override]
    public function clear()
    {
        $this->clearExcept();
    }

    #[\Override]
    public function getValue()
    {
        $valueElements = $this->getParent()->getParent()->findAll('css', 'li.select2-search-choice');

        $value = array_map(function (NodeElement $element) {
            return $element->getText();
        }, $valueElements);

        $this->close();

        return $value;
    }

    protected function hasValue(string $value): bool
    {
        $searchXpath = sprintf(
            '//*[contains(concat(" ",normalize-space(@class)," ")," select2-search-choice ") and ./div[text()="%s"]]',
            $value
        );

        return (bool)$this->getParent()->getParent()->find('xpath', $searchXpath);
    }
}
