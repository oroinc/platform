<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Driver;

use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Selector\Xpath\Manipulator;
use WebDriver\Element;

class OroSelenium2Driver extends Selenium2Driver
{
    /**
     * @var Manipulator
     */
    private $xpathManipulator;

    /**
     * {@inheritdoc}
     */
    public function __construct($browserName, $desiredCapabilities, $wdHost)
    {
        $this->xpathManipulator = new Manipulator();

        parent::__construct($browserName, $desiredCapabilities, $wdHost);
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($xpath, $value)
    {
        $element = $this->findElement($xpath);
        $elementName = strtolower($element->name());

        if ('input' === $elementName) {
            $classes = explode(' ', $element->attribute('class'));

            if (true === in_array('select2-offscreen', $classes, true)) {
                $this->fillSelect2Entity($xpath, $value);

                return;
            } elseif (true === in_array('select2-input', $classes, true)) {
                $this->fillSelect2Entities($xpath, $value);

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
     * Set content tinymce editor with value
     *
     * @param Element $element Form element that was replaced by TinyMCE
     * @param string  $value   Text for set into tiny
     *
     * @throws ExpectationException
     */
    protected function fillTinyMce(Element $element, $value)
    {
        $fieldId = $element->attribute('id');

        $isTinyMce = $this->evaluateScript(
            sprintf('null != tinyMCE.get("%s");', $fieldId)
        );

        if (!$isTinyMce) {
            throw new ExpectationException(
                sprintf('Field was guessed as tinymce, but can\'t find tiny with id "%s" on page', $fieldId),
                $this
            );
        }

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
        $script = <<<JS
var node = {{ELEMENT}};
node.value = '$value';
JS;
        $this->executeJsOnElement($element, $script);
    }

    /**
     * Fill field with many entity
     * See contexts, to fields in send email form
     *
     * @param string $xpath
     * @param string|array $values
     * @throws ExpectationException
     */
    protected function fillSelect2Entities($xpath, $values)
    {
        $values = is_array($values) ? $values : [$values];
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

        foreach ($values as $value) {
            $input->postValue(['value' => [$value]]);
            $this->wait(3000, "0 == $('ul.select2-results li.select2-searching').length");

            if (!$results = $this->findElementXpaths('//ul[contains(@class, "select2-result-sub")]/li')) {
                $results = $this->findElementXpaths('//ul[contains(@class, "select2-results")]/li');
            }

            $firstResult = $this->findElement(array_shift($results));

            if ('select2-no-results' === $firstResult->attribute('class')) {
                throw new ExpectationException(sprintf('Not found result for "%s"', $value), $this);
            }

            $firstResult->click();
        }
    }

    /**
     * Fill select2entity field, like owner, country, state
     * If more then 1 result found in search, then foreach results and click on the result that exactly matches
     * If more then 1 result found and no one is exactly matches, then "Too many results" exception will thrown
     *
     * @param string $xpath
     * @param string $value
     * @throws ExpectationException
     * @throws \Exception
     */
    protected function fillSelect2Entity($xpath, $value)
    {
        $this
            ->findElement($this->xpathManipulator->prepend('/../a/span[contains(@class, "select2-arrow")]', $xpath))
            ->click();

        foreach ($this->findElementXpaths('//div[contains(@class, "select2-search")]/input') as $input) {
            $element = $this->findElement($input);
            if ($element->displayed()) {
                $element->postValue(['value' => [$value]]);
            }
        }

        $this->wait(3000, "0 == $('ul.select2-results li.select2-searching').length");
        $results = $this->findElementXpaths('//ul[contains(@class, "select2-results")]/li');

        if (1 < count($results)) {
            foreach ($results as $result) {
                $element = $this->findElement($result);

                if ($element->text() == $value) {
                    $element->click();

                    return;
                }
            }

            throw new ExpectationException(sprintf('Too many results for "%s"', $value), $this);
        }

        $firstResult = $this->findElement(array_shift($results));

        if ('select2-no-results' === $firstResult->attribute('class')) {
            throw new ExpectationException(sprintf('Not found result for "%s"', $value), $this);
        }

        $firstResult->click();
    }

    /**
     * Wait PAGE load
     * @param int $time Time should be in milliseconds
     */
    public function waitPageToLoad($time = 15000)
    {
        $this->wait(
            $time,
            '"complete" == document["readyState"] '.
            '&& (typeof($) != "undefined" '.
            '&& document.title !=="Loading..." '.
            '&& $ !== null)'
        );
    }

    /**
     * Wait AJAX request
     * @param int $time Time should be in milliseconds
     */
    public function waitForAjax($time = 15000)
    {
        $this->waitPageToLoad($time);

        $jsAppActiveCheck = <<<JS
        (function () {
            var isAppActive = 0 !== $("div.loader-mask.shown").length;
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
     * Executes JS on a given element - pass in a js script string and {{ELEMENT}} will
     * be replaced with a reference to the element
     *
     * @example $this->executeJsOnXpath($xpath, 'return {{ELEMENT}}.childNodes.length');
     *
     * @param Element $element the webdriver element
     * @param string  $script  the script to execute
     * @param Boolean $sync    whether to run the script synchronously (default is TRUE)
     *
     * @return mixed
     */
    private function executeJsOnElement(Element $element, $script, $sync = true)
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
}
