<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Driver;

use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Selector\Xpath\Escaper;
use Behat\Mink\Selector\Xpath\Manipulator;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\AssertTrait;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\ElementValueInterface;
use WebDriver\Element;
use WebDriver\Key;

/**
 * Contains overrides of some Selenium2Driver methods as well as new methods related to selenium driver functionality
 */
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

        if ($value instanceof ElementValueInterface) {
            $value->set($xpath, $this);

            return;
        }

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
            if ('text' === $element->attribute('type')) {
                $this->setTextInputElement($element, $value);
                $this->triggerEvent($xpath, 'keyup');
                $this->triggerEvent($xpath, 'change');

                return;
            }
        } elseif ('textarea' === $elementName && 'true' === $element->attribute('aria-hidden')) {
            $this->fillTinyMce($element, $value);
            $this->triggerEvent($xpath, 'keyup');
            $this->triggerEvent($xpath, 'change');

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
    protected function setTextInputElement(Element $element, $value)
    {
        $value = json_encode($value);
        $script = <<<JS
var node = {{ELEMENT}};
node.value = $value;
JS;
        $this->executeJsOnElement($element, $script);
    }

    /**
     * Wait PAGE load
     * @param int $time Time should be in milliseconds
     * @return bool
     */
    public function waitPageToLoad($time = 60000)
    {
        $jsCheck = <<<JS
        (function () {
            if (document["readyState"] !== "complete") {
                return false;
            }
            
            if (document.title === "Loading...") {
                return false;
            }
            
            if (jQuery == null || jQuery.active) {
                return false;
            }
            
            if (document.body.classList.contains('loading')) {
                return false;
            }

            if (document.querySelector('.loader-mask.shown') !== null) {
                return false;
            }
            
            if (document.querySelector('div.lazy-loading') !== null) {
                return false;
            }
            
            return true;
        })();
JS;

        $result = $this->wait($time, $jsCheck);

        if (!$result) {
            self::fail(sprintf('Wait for page init more than %d seconds', $time / 1000));
        }

        return $result;
    }

    /**
     * Wait AJAX request
     * @param int $time Time should be in milliseconds
     * @return bool
     */
    public function waitForAjax($time = 120000)
    {
        $jsAppActiveCheck = <<<JS
        (function () {
            if (document['readyState'] !== 'complete') {
                return false;
            }
            
            if (document.title === 'Loading...') {
                return false;
            }
            
            if (document.body.classList.contains('loading')) {
                return false;
            }

            if (document.querySelector('.loader-mask.shown, .lazy-loading') !== null) {
                return false;
            }
            
            try {
                if (jQuery == null || jQuery.active) {
                    return false;
                }
                
                if (!window.mediatorCachedForSelenium) {
                    window.mediatorCachedForSelenium = require('oroui/js/mediator');
                }
                
                var isInAction = window.mediatorCachedForSelenium.execute('isInAction')
                
                if (isInAction !== false || jQuery.active) {
                    return false;
                }
            } catch (e) {
                return false;
            }

            return true;
        })();
JS;

        $result = $this->wait($time, $jsAppActiveCheck);

        if (!$result) {
            self::fail(sprintf('Wait for ajax more than %d seconds', $time / 1000));
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function doubleClick($xpath)
    {
        // Original method doesn't work properly with chromedriver,
        // as it doesn't generate a pair of mouseDown/mouseUp events
        // mouseDown event is used to postpone single click handler
        $script = 'Syn.trigger("dblclick", {}, {{ELEMENT}})';
        $this->withSyn()->executeJsOnXpath($xpath, $script);
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
    protected function executeJsOnElement(Element $element, $script, $sync = true)
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

    /**
     * Trigger given event $eventName on DOM element, found by $xpath
     *
     * This method created to trigger events instead of $this->keyup, $this->blur, etc, becaouse of "Syn" library error
     * (in Syn.trigger function), used in these methods.
     *
     * @param string $xpath
     * @param string $eventName
     */
    private function triggerEvent($xpath, $eventName)
    {
        $script = <<<JS
// Function to triger an event. Cross-browser compliant. See http://stackoverflow.com/a/2490876/135494
var triggerEvent = function (element, eventName) {
    var event;
    if (document.createEvent) {
        event = document.createEvent("HTMLEvents");
        event.initEvent(eventName, true, true);
    } else {
        event = document.createEventObject();
        event.eventType = eventName;
    }
    event.eventName = eventName;
    if (document.createEvent) {
        element.dispatchEvent(event);
    } else {
        element.fireEvent("on" + event.eventType, event);
    }
}
var node = {{ELEMENT}};
triggerEvent(node, '$eventName');
JS;
        $this->executeJsOnXpath($xpath, $script);
    }

    /**
     * @param NodeElement $element
     */
    public function switchToIFrameByElement(NodeElement $element)
    {
        $id = $element->getAttribute('id');

        if ($id === null) {
            $elementXpath = $element->getXpath();
            $id = sprintf('iframe-%s', md5($elementXpath));

            $function = <<<JS
(function(){
    var iframeElement = document.evaluate(
        "{$elementXpath}",
        document,
        null,
        XPathResult.FIRST_ORDERED_NODE_TYPE,
        null
    ).singleNodeValue;
    iframeElement.id = "{$id}";
})()
JS;

            $this->executeScript($function);
        }

        parent::switchToIFrame($id);
    }
}
