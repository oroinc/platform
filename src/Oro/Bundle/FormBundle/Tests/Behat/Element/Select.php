<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Selector\Xpath\Escaper;
use Behat\Mink\Session;
use Oro\Bundle\FormBundle\Tests\Behat\Context\ClearableInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactory;
use WebDriver\Exception\StaleElementReference;

/**
 * Select control that treats text of selected option as value
 * (checks what user sees on UI)
 */
class Select extends Element implements ClearableInterface
{
    /**
     * @var Escaper
     */
    private $xpathEscaper;

    /**
     * @param Session $session
     * @param OroElementFactory $elementFactory
     * @param array|string $selector
     */
    public function __construct(
        Session $session,
        OroElementFactory $elementFactory,
        $selector = ['type' => 'xpath', 'locator' => '/html/body']
    ) {
        $this->xpathEscaper = new Escaper();

        parent::__construct(
            $session,
            $elementFactory,
            $selector
        );
    }

    #[\Override]
    public function setValue($value)
    {
        $this->setValueWithRetry($value);
    }

    /**
     * @param string|bool|array $value
     * @param bool $retryOnStaleElement
     * @throws StaleElementReference
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     */
    protected function setValueWithRetry($value, $retryOnStaleElement = true)
    {
        try {
            if (is_array($value)) {
                self::assertTrue(
                    $this->hasAttribute('multiple'),
                    'Only multiple select can be selected by several values'
                );

                foreach ($value as $option) {
                    $this->selectOption($option, true);
                }
            } else {
                $this->selectOption($value);
            }
        } catch (StaleElementReference $e) {
            if ($retryOnStaleElement) {
                $this->spin(function () use ($value) {
                    $this->setValueWithRetry($value, false);
                }, 5);
            } else {
                throw $e;
            }
        }
    }

    /**
     * A field behind a select2 widget was a Select2Entity element before the native select wrap took priority.
     * The "clear field" steps rely on the clear function of that element.
     */
    #[\Override]
    public function clear()
    {
        // A select with a select2 widget shows the clear button of the widget next to it.
        // A click on that button is what Select2Entity::clear() did.
        $close = $this->find(
            'xpath',
            'preceding-sibling::div[contains(@class, "select2-container")][1]'
            . '//*[contains(@class, "select2-search-choice-close")]'
        );
        if (null !== $close && $close->isVisible()) {
            $close->click();

            return;
        }

        // A native select or a select2 without the clear button: set the value in JS.
        // Then send the events that a real user action produces.
        $this->getDriver()->executeJsOnXpath(
            $this->getXpath(),
            'const select = {{ELEMENT}};
            const empty = Array.from(select.options).find((o) => "" === o.value);
            if (empty) {
                empty.selected = true;
            } else {
                select.selectedIndex = -1;
            }
            select.dispatchEvent(new Event("input", { bubbles: true }));
            select.dispatchEvent(new Event("change", { bubbles: true }));'
        );
    }

    /**
     * @return NodeElement|null
     */
    public function getSelectedOption()
    {
        return $this->find('css', 'option[selected]');
    }

    /**
     * @return string|null
     */
    #[\Override]
    public function getValue()
    {
        $text = null;

        $value = parent::getValue();

        // A field behind a select2 widget was a Select2Entity element, and the value assertions rely on its result:
        // the visible choice of the widget, or the placeholder for an empty value.
        $chosen = $this->find(
            'xpath',
            'preceding-sibling::div[contains(@class, "select2-container")][1]'
            . '//span[contains(@class, "select2-chosen")]'
        );
        if (null !== $chosen) {
            return $chosen->getText();
        }

        $escapedValue = $this->xpathEscaper->escapeLiteral($value);
        $optionQuery = sprintf('.//option[@value = %s or normalize-space(.) = %1$s]', $escapedValue);
        $option = $this->find('xpath', $optionQuery);

        if (null !== $option) {
            $text = $option->getText();
        }

        return $text;
    }
}
