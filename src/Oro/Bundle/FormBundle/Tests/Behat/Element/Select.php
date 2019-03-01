<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use WebDriver\Exception\StaleElementReference;

class Select extends Element
{
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
}
