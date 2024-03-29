<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Mink\Selector\SelectorsHandler;
use Behat\Mink\Selector\Xpath\Manipulator;

/**
 * Utility for element selectors manipulations
 */
class SelectorManipulator extends Manipulator
{
    /**
     * The logic for searching text in Behat tests is the following:
     * Take a piece of text as the input.
     * Replace certain specified characters (', ", `, \) with spaces.
     * Trim any spaces from the beginning and end of the text.
     * Replace consecutive duplicate spaces with a single space.
     *
     * The resulting text is split into individual words based on spaces
     * and a selector is constructed using the groups of words obtained from the split.
     *
     * Example, looking for the text " Product2Localization1`\"'&reg@;> ",
     * it is broken into "Product2Localization1" and "&reg@;>",
     * then builds a selector that looks for the occurrence of "Product2Localization1" and "&reg@;>" in the string
     *
     * @param array|string $cssSelector
     * @param string $text
     * @return string
     */
    public function addContainsSuffix($cssSelector, $text)
    {
        [$selectorType, $locator] = $this->parseSelector($cssSelector);

        if ($selectorType !== 'css') {
            throw new \InvalidArgumentException('Method "addContainsSuffix" support only css selectors');
        }
        $text = trim(str_replace(["'", '"', "`", "\\"], ' ', $text));
        $text = preg_replace('/\s{2,}/', ' ', $text);

        $selector = implode(',', array_map(function ($variant) use ($locator) {
            $containsSuffix = implode(':', array_map([$this, 'getContainsFromString'], explode(' ', $variant)));

            return $locator.':'.$containsSuffix;
        }, self::getVariants($text)));

        return $selector;
    }

    /**
     * @param string $text
     *
     * @return string
     */
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
        [$selectorType, $locator] = $this->parseSelector($selector);

        return $selectorsHandler->selectorToXpath($selectorType, $locator);
    }

    /**
     * @param string $xpath
     * @param string $text
     * @param bool   $useChildren
     *
     * @return array
     */
    public function getContainsXPathSelector($xpath, $text, $useChildren = true)
    {
        return $this->getXPathSelector(
            $xpath,
            sprintf("contains(%s, '%s')", $this->getToLowerXPathExpr('.'), strtolower($text)),
            $useChildren
        );
    }

    /**
     * @param string $xpath
     * @param string $text
     *
     * @return array
     */
    public function getExactMatchXPathSelector($xpath, $text)
    {
        return [
            'type' => 'xpath',
            'locator' => $xpath . sprintf("[%s='%s']", $this->getToLowerXPathExpr('text()'), strtolower($text))
        ];
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

    /**
     * @param string $expr
     *
     * @return string
     */
    protected function getToLowerXPathExpr($expr)
    {
        return sprintf(
            "translate(%s, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')",
            $this->getNormalizedSpacesXPathExpr($expr)
        );
    }

    /**
     * Replace &nbsp; with normal space and remove not needed spaces
     *
     * @param string $expr
     * @return string
     */
    protected function getNormalizedSpacesXPathExpr($expr)
    {
        return sprintf("normalize-space(translate(%s, '\xc2\xa0', ' '))", $expr);
    }

    /**
     * @param string $xpath
     * @param string $xpathCondition
     * @param bool   $useChildren
     *
     * @return array
     */
    protected function getXPathSelector($xpath, $xpathCondition, $useChildren = true)
    {
        $embedCondition = sprintf('text()[%s]', $xpathCondition);
        if ($useChildren) {
            $embedCondition = sprintf('descendant-or-self::node()[%s]', $embedCondition);
        }

        $length = strlen($xpath);
        if ($xpath[$length - 1] === ']') {
            $pos = strpos($xpath, '[');
            $xpath = sprintf(
                '%s[%s and (%s)]',
                substr($xpath, 0, $pos),
                substr($xpath, $pos + 1, $length - $pos - 2),
                $embedCondition
            );
        } else {
            $xpath .= sprintf('[%s]', $embedCondition);
        }

        return ['type' => 'xpath', 'locator' => $xpath];
    }
}
