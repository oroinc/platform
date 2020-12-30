<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Selector\Xpath\Escaper;
use Behat\Mink\Session;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactory;
use WebDriver\Exception\StaleElementReference;

/**
 * Select control that treats text of selected option as value
 * (checks what user sees on UI)
 */
class Select extends Element
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

    /**
     * {@inheritdoc}
     */
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
     * @return NodeElement|null
     */
    public function getSelectedOption()
    {
        return $this->find('css', 'option[selected]');
    }

    /**
     * @return string|null
     */
    public function getValue()
    {
        $text = null;

        $value = parent::getValue();

        $escapedValue = $this->xpathEscaper->escapeLiteral($value);
        $optionQuery = sprintf('.//option[@value = %s or normalize-space(.) = %1$s]', $escapedValue);
        $option = $this->find('xpath', $optionQuery);

        if (null !== $option) {
            $text = $option->getText();
        }

        return $text;
    }
}
