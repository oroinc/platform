<?php

namespace Oro\Bundle\TestFrameworkBundle\Pages;

use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Login;
use PHPUnit_Framework_Assert;

/**
 * Class AbstractPage
 *
 * @package Oro\Bundle\TestFrameworkBundle\Pages
 *
 */
abstract class AbstractPage
{
    const URL = null;
    protected $redirectUrl = null;

    /** @var \PHPUnit_Extensions_Selenium2TestCase */
    protected $test;

    /**
     * @param $testCase
     * @param bool $redirect
     */
    public function __construct($testCase, $redirect = true)
    {
        $this->test = $testCase;
        $url = static::URL;
        if (is_null($url)) {
            $url = $this->redirectUrl;
        }
        if (!is_null($url) && $redirect) {
            $this->test->url($url);
            $this->waitPageToLoad();
            $this->waitForAjax();
        }
    }

    /**
     * @return \PHPUnit_Extensions_Selenium2TestCase
     */
    public function getTest()
    {
        return $this->test;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (preg_match('/open(.+)/i', "{$name}", $result) > 0) {
            if (isset($arguments[0])) {
                //get name space from arguments
                $namespace = $arguments[0];
                $namespace .= '\\Tests\Selenium';
                unset($arguments[0]);
                $arguments = array_values($arguments);
            } else {
                //get called class namespace
                $namespace = implode('\\', array_slice(explode('\\', get_class($this->test)), 0, -1));
            }
            $class = $namespace . '\\Pages\\' . $result[1];
            $class = new \ReflectionClass($class);
            return $class->newInstanceArgs(array_merge(array($this->test), $arguments));
        }

        return null;
    }

    /**
     * Wait PAGE load
     */
    public function waitPageToLoad()
    {
        // writes javascript errors to screenshots
        $jsCode = <<<JS
            if (!window.onerror) {
                window.onerror = function (errorMsg, url, lineNumber, column, errorObj) {
                    var html =
                        '<div style="background: rgba(255,255,200,0.7); color: rgb(0,0,255); ' +
                            'pointer-events: none; position: absolute; top: 15px; left: 15px; right: 15px; ' +
                            'z-index: 999999; padding: 10px; border: 1px solid red;  border-radius: 5px;' +
                            'box-shadow: 0 0 40px rgba(0,0,0,0.5); white-space: pre">' +
                            '<h5>Js error occured</h5>' + errorMsg + '\\n' +
                            'at ' + url + ':' + lineNumber + ':' + column;
                    if (errorObj && errorObj.stack) {
                        html += '<h5>Stack trace:</h5>' + errorObj.stack;
                    }
                    html += '</div>';
                    $('body').append(html);

                    // Tell browser to run its own error handler as well
                    return false;
                }
            }
JS;

        $this->test->execute(array(
            'script' =>$jsCode,
            'args' =>
                array()
        ));

        $this->test->waitUntil(
            function (\PHPUnit_Extensions_Selenium2TestCase $testCase) {
                $status = $testCase->execute(
                    array('script' => "return 'complete' == document['readyState']", 'args' => array())
                );
                if ($status) {
                    return true;
                } else {
                    return null;
                }
            },
            intval(MAX_EXECUTION_TIME)
        );

        $this->test->waitUntil(
            function (\PHPUnit_Extensions_Selenium2TestCase $testCase) {
                $status = $testCase->execute(
                    array(
                        'script' =>
                            "return typeof(document['page-rendered'])=='undefined' || !!document['page-rendered']",
                        'args' =>
                            array()
                    )
                );
                if ($status) {
                    return true;
                } else {
                    return null;
                }
            },
            intval(MAX_EXECUTION_TIME)
        );

        $this->test->timeouts()->implicitWait(intval(TIME_OUT));
    }

    /**
     * Wait AJAX request
     */
    public function waitForAjax()
    {

        $this->test->waitUntil(
            function (\PHPUnit_Extensions_Selenium2TestCase $testCase) {
                $jsAppActiveCheck = <<<JS
                    var isAppActive = false;
                    try {
                        if (!window.mediatorCachedForSelenium) {
                            window.mediatorCachedForSelenium = require('oroui/js/mediator');
                        }
                        isAppActive = window.mediatorCachedForSelenium.execute('isInAction');
                    } catch(e) {};
                    return !(jQuery && (jQuery.active || jQuery(document.body).hasClass('loading'))) && !isAppActive;
JS;
                $status = $testCase->execute(
                    array(
                        'script' => $jsAppActiveCheck,
                        'args' => array()
                    )
                );
                if ($status) {
                    return true;
                } else {
                    return null;
                }
            },
            intval(MAX_EXECUTION_TIME)
        );

        $this->test->timeouts()->implicitWait(intval(TIME_OUT));
    }

    /**
     * Reload current page
     *
     * @return $this
     */
    public function refresh()
    {
        if (!is_null($this->redirectUrl)) {
            $this->test->url($this->redirectUrl);
        } else {
            $this->test->refresh();
        }
        $this->waitPageToLoad();
        $this->waitForAjax();

        return $this;
    }

    /**
     * Verify element present
     *
     * @param string $locator
     * @param string $strategy
     * @return bool
     */
    public function isElementPresent($locator, $strategy = 'xpath')
    {
        $result = $this->test->elements($this->test->using($strategy)->value($locator));
        return !empty($result);
    }

    /**
     * @param $expectedTitle
     * @param string $message
     * @return $this
     */
    public function assertTitle($expectedTitle, $message = null)
    {
        $actual = $this->test->title();
        $constraint = new \PHPUnit_Framework_Constraint_IsEqual($expectedTitle);

        PHPUnit_Framework_Assert::assertThat($actual, $constraint, $message);

        return $this;
    }

    /**
     * @param $expectedMessage
     * @param string $message
     * @return $this
     * @throws  \PHPUnit_Framework_AssertionFailedError
     */
    public function assertMessage($expectedMessage, $message = 'Another flash message appears')
    {
        $this->assertElementPresent(
            "//div[@id = 'flash-messages']//div[@class = 'message']",
            'Flash message is missing'
        );

        $messageCssSelector = $this->test->using('css selector')->value('div#flash-messages div.message');

        $renderedMessages = array();
        /** @var \PHPUnit_Extensions_Selenium2TestCase_Element $messageElement */
        foreach ($this->test->elements($messageCssSelector) as $messageElement) {
            $renderedMessages[] = $this->trimHTML(trim($messageElement->attribute('innerHTML')));
        }

        PHPUnit_Framework_Assert::assertContains($expectedMessage, $renderedMessages, $message);

        return $this;
    }

    /**
     * Method trims html tags form string, used in assertMessage function
     * @param $value
     * @return string
     */
    protected function trimHTML($value)
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.AllowedElements', '');
        $config->set('HTML.AllowedAttributes', '');
        $config->set('URI.AllowedSchemes', []);
        $purifier = new \HTMLPurifier($config);

        return $purifier->purify($value);
    }

    /**
     * @param $expectedMessage
     * @param string $message
     * @return $this
     * @throws  \PHPUnit_Framework_AssertionFailedError
     */
    public function assertErrorMessage($expectedMessage, $message = 'Another flash message appears')
    {
        $this->assertElementPresent(
            "//div[contains(@class,'alert') and not(contains(@class, 'alert-empty'))]",
            'Flash message is missing'
        );
        $actualMessage = $this->test->byXPath(
            "//div[contains(@class,'alert') and not(contains(@class, 'alert-empty'))]/div"
        )->text();

        PHPUnit_Framework_Assert::assertEquals($expectedMessage, trim($actualMessage), $message);
        return $this;
    }

    /**
     * @param $xpath
     * @param string $message
     * @return $this
     * @throws  \PHPUnit_Framework_AssertionFailedError
     */
    public function assertElementPresent($xpath, $message = '')
    {
        if ($message === '') {
            $message = "Element {$xpath} is not present";
        }

        if (!$this->isElementPresent($xpath)) {
            PHPUnit_Framework_Assert::fail($message);
        }

        return $this;
    }

    /**
     * @param $xpath
     * @param string $message
     * @return $this
     * @throws  \PHPUnit_Framework_AssertionFailedError
     */
    public function assertElementNotPresent($xpath, $message = '')
    {
        if ($message === '') {
            $message = "Element {$xpath} is present when not expected";
        }

        if ($this->isElementPresent($xpath)) {
            PHPUnit_Framework_Assert::fail($message);
        }

        return $this;
    }

    /**
     * Clear input element when standard clear() does not help
     *
     * @param $element \PHPUnit_Extensions_Selenium2TestCase_Element
     */
    protected function clearInput($element)
    {
        $element->value('');
        $tx = $element->value();
        while ($tx!="") {
            $this->test->keysSpecial('backspace');
            $tx = $element->value();
        }
    }

    /**
     * @return \Oro\Bundle\UserBundle\Tests\Selenium\Pages\Login
     */
    public function logout()
    {
        $this->test->url('/user/logout');
        return new Login($this->test);
    }

    /**
     * @return $this
     */
    public function pin()
    {
        $this->test->byXPath("//div[@class='top-action-box']//button[@class='btn minimize-button']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }

    /**
     * @return $this
     */
    public function unpin()
    {
        $this->test->byXPath("//div[@class='top-action-box']//button[@class='btn minimize-button gold-icon']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }

    public function closeWidgetWindow()
    {
        $widgetCloseButton =
            "//div[starts-with(@class,'ui-dialog-titlebar ui-widget-header')]//button|span[contains(., 'close')]";
        while ($this->isElementPresent($widgetCloseButton)) {
            $this->test->byXPath($widgetCloseButton)->click();
            $this->waitForAjax();
        }

        return $this;
    }
}
