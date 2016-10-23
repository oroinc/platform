<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Driver;

use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Selector\Xpath\Escaper;
use Behat\Mink\Selector\Xpath\Manipulator;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\AssertTrait;
use WebDriver\Element;
use WebDriver\Key;

class OroSelenium2Driver extends Selenium2Driver
{
    use AssertTrait;

    /**
     * @var Manipulator
     */
    private $xpathManipulator;

    /**
     * @var Escaper
     */
    private $xpathEscaper;

    /**
     * {@inheritdoc}
     */
    public function __construct($browserName, $desiredCapabilities, $wdHost)
    {
        $this->xpathManipulator = new Manipulator();
        $this->xpathEscaper = new Escaper();

        parent::__construct($browserName, $desiredCapabilities, $wdHost);
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($xpath, $value)
    {
        $element = $this->findElement($xpath);
        $elementName = strtolower($element->name());

        if ('select' === $elementName) {
            if (is_array($value)) {
                $this->deselectAllOptions($element);

                foreach ($value as $option) {
                    $this->selectOptionOnElement($element, $option, true);
                }

                return;
            }

            $this->selectOptionOnElement($element, $value);

            return;
        }

        if ('input' === $elementName) {
            $classes = explode(' ', $element->attribute('class'));

            if (true === in_array('select2-input', $classes, true)) {
                $parent = $this->findElement($this->xpathManipulator->prepend('/../../..', $xpath));

                if (in_array('select2-container-multi', explode(' ', $parent->attribute('class')), true)) {
                    $this->fillSelect2Entities($xpath, $value);

                    return;
                }

                $this->findElement($xpath)->postValue(['value' => [$value]]);

                return;
            } elseif ('text' === $element->attribute('type')) {
                $this->fillTextInput($element, $value);

                return;
            }
        } elseif ('textarea' === $elementName && 'true' === $element->attribute('aria-hidden')) {
            $this->fillTinyMce($element, $value);

            return;
        }

        parent::setValue($xpath, $value);
    }

    /**
     * @param string $xpath
     * @param string $value
     */
    public function typeIntoInput($xpath, $value)
    {
        $element = $this->findElement($xpath);
        $elementName = strtolower($element->name());

        if (in_array($elementName, array('input', 'textarea'))) {
            $existingValueLength = strlen($element->attribute('value'));
            $value = str_repeat(Key::BACKSPACE . Key::DELETE, $existingValueLength) . $value;
        }

        $element->postValue(array('value' => array($value)));
    }

    /**
     * Set content tinymce editor with value
     *
     * @param Element $element Form element that was replaced by TinyMCE
     * @param string  $value   Text for set into tiny
     */
    protected function fillTinyMce(Element $element, $value)
    {
        $fieldId = $element->attribute('id');

        $isTinyMce = $this->evaluateScript(
            sprintf('null != tinyMCE.get("%s");', $fieldId)
        );

        self::assertNotNull(
            $isTinyMce,
            sprintf('Field was guessed as tinymce, but can\'t find tiny with id "%s" on page', $fieldId)
        );

        $this->executeScript(
            sprintf('tinyMCE.get("%s").setContent("%s");', $fieldId, $value)
        );
    }


    /**
     * @param Element $element
     * @param string $value
     */
    protected function fillTextInput(Element $element, $value)
    {
        $existingValueLength = strlen($element->attribute('value'));
        $value = str_repeat(Key::BACKSPACE . Key::DELETE, $existingValueLength) . $value;

        $element->postValue(array('value' => array($value)));
    }

    /**
     * Fill field with many entities
     * See contexts field in send email form
     * It will remove all existed entities in field
     *
     * @param string $xpath
     * @param string|array $values Any string(s) for search entity
     */
    protected function fillSelect2Entities($xpath, $values)
    {
        $input = $this->findElement($xpath);

        // Remove all existing entities
        $results = $this->findElementXpaths($this->xpathManipulator->prepend(
            '/../../li/a[contains(@class, "select2-search-choice-close")]',
            $xpath
        ));

        foreach ($results as $result) {
            $this->executeJsOnXpath($result, '{{ELEMENT}}.click()');
        }

        $this->waitForAjax();

        $values = true === is_array($values) ? $values : [$values];

        foreach ($values as $value) {
            $input->postValue(['value' => [$value]]);
            $this->wait(30000, "0 == $('ul.select2-results li.select2-searching').length");

            $results = $this->getEntitiesSearchResultXpaths();
            $firstResult = $this->findElement(array_shift($results));

            self::assertNotEquals(
                'select2-no-results',
                $firstResult->attribute('class'),
                sprintf('Not found result for "%s"', $value)
            );

            $firstResult->click();
            $this->waitForAjax();
        }
    }

    /**
     * @return string[]
     */
    protected function getEntitiesSearchResultXpaths()
    {
        $resultsHoldersXpaths = [
            '//ul[contains(@class, "select2-result-sub")]',
            '//ul[contains(@class, "select2-result")]',
        ];

        while ($resultsHoldersXpath = array_shift($resultsHoldersXpaths)) {
            foreach ($this->findElementXpaths($resultsHoldersXpath) as $xpath) {
                $resultsHolder = $this->findElement($xpath);

                if ($resultsHolder->displayed()) {
                    return $this->findElementXpaths($xpath.'/li');
                }
            }
        }

        return [];
    }

    /**
     * Wait PAGE load
     * @param int $time Time should be in milliseconds
     */
    public function waitPageToLoad($time = 60000)
    {
        $this->wait(
            $time,
            '"complete" == document["readyState"] '.
            '&& document.title !=="Loading..." '
        );
    }

    /**
     * Wait AJAX request
     * @param int $time Time should be in milliseconds
     */
    public function waitForAjax($time = 60000)
    {
        $this->waitPageToLoad($time);

        $jsAppActiveCheck = <<<JS
        (function () {
            if (typeof(jQuery) == "undefined" || jQuery == null) {
                return false;
            }

            var isAppActive = 0 !== jQuery("div.loader-mask.shown").length;
            try {
                if (!window.mediatorCachedForSelenium) {
                    window.mediatorCachedForSelenium = require('oroui/js/mediator');
                }
                isAppActive = isAppActive || window.mediatorCachedForSelenium.execute('isInAction');
            } catch (e) {
                return false;
            }

            return !(jQuery && (jQuery.active || jQuery(document.body).hasClass('loading'))) && !isAppActive;
        })();
JS;
        $this->wait($time, $jsAppActiveCheck);
    }

    /**
     * @param string $xpath
     *
     * @return Element
     */
    private function findElement($xpath)
    {
        return $this->getWebDriverSession()->element('xpath', $xpath);
    }

    /**
     * {@inheritdoc}
     */
    public function executeJsOnXpath($xpath, $script, $sync = true)
    {
        return $this->executeJsOnElement($this->findElement($xpath), $script, $sync);
    }

    /**
     * {@inheritdoc}
     */
    public function executeJsOnElement(Element $element, $script, $sync = true)
    {
        $script  = str_replace('{{ELEMENT}}', 'arguments[0]', $script);

        $options = array(
            'script' => $script,
            'args'   => array(array('ELEMENT' => $element->getID())),
        );

        if ($sync) {
            return $this->getWebDriverSession()->execute($options);
        }

        return $this->getWebDriverSession()->execute_async($options);
    }

    /**
     * @param Element $element
     * @param string  $value
     * @param bool    $multiple
     */
    protected function selectOptionOnElement(Element $element, $value, $multiple = false)
    {
        $escapedValue = $this->xpathEscaper->escapeLiteral($value);
        // The value of an option is value attribute or the normalized version of its text
        $optionQuery = sprintf('.//option[@value = %s or normalize-space(.) = %1$s]', $escapedValue);
        $option = $element->element('xpath', $optionQuery);

        if ($multiple || !$element->attribute('multiple')) {
            if (!$option->selected()) {
                $option->click();
            }

            return;
        }

        // Deselect all options before selecting the new one
        $this->deselectAllOptions($element);
        $option->click();
    }

    /**
     * Deselects all options of a multiple select
     *
     * Note: this implementation does not trigger a change event after deselecting the elements.
     *
     * @param Element $element
     */
    private function deselectAllOptions(Element $element)
    {
        $script = <<<JS
var node = {{ELEMENT}};
var i, l = node.options.length;
for (i = 0; i < l; i++) {
    node.options[i].selected = false;
}
JS;

        $this->executeJsOnElement($element, $script);
    }

    private function waitFor($timeout, \Closure $function)
    {
        $start = microtime(true);
        $end = $start + $timeout / 1000.0;

        do {
            $result = $function();
            usleep(100000);
        } while (microtime(true) < $end && !$result);

        return (bool) $result;
    }
}
