<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Mink\Selector\SelectorsHandler;
use Behat\Mink\Selector\Xpath\Manipulator;

class SelectorManipulator extends Manipulator
{
    /**
     * @param array|string $cssSelector
     * @param string $text
     * @return string
     */
    public function addContainsSuffix($cssSelector, $text)
    {
        list($selectorType, $locator) = $this->parseSelector($cssSelector);

        if ($selectorType !== 'css') {
            throw new \InvalidArgumentException('Method "addContainsSuffix" support only css selectors');
        }

        $text = trim(preg_replace('/\W/i', ' ', $text));

        $selector = implode(',', array_map(function ($variant) use ($locator) {
            $containsSuffix = implode(':', array_map([$this, 'getContainsFromString'], explode(' ', $variant)));

            return $locator.':'.$containsSuffix;
        }, self::getVariants($text)));

        return $selector;
    }

    public function getContainsFromString($text)
    {
        return sprintf('contains("%s")', $text);
    }

    /**
     * @param SelectorsHandler $selectorsHandler
     * @param array|string $selector
     *
     * @return string
     */
    public function getSelectorAsXpath(SelectorsHandler $selectorsHandler, $selector)
    {
        list($selectorType, $locator) = $this->parseSelector($selector);

        return $selectorsHandler->selectorToXpath($selectorType, $locator);
    }

    /**
     * @param string $text
     * @return array
     */
    protected static function getVariants($text)
    {
        return array_unique([
            $text,
            ucfirst($text),
            ucfirst(strtolower($text)),
            strtolower($text),
            strtoupper($text),
        ]);
    }

    /**
     * @param array|string $selector
     * @return array
     */
    protected function parseSelector($selector)
    {
        $selectorType = is_array($selector) ? $selector['type'] : 'css';
        $locator = is_array($selector) ? $selector['locator'] : $selector;

        return [$selectorType, $locator];
    }
}
