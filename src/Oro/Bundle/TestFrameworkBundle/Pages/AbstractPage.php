<?php

namespace Oro\Bundle\TestFrameworkBundle\Pages;

use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Login;
use PHPUnit_Framework_Assert;

/**
 * Class AbstractPage
 *
 * @package Oro\Bundle\TestFrameworkBundle\Pages
 */
abstract class AbstractPage
{
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
        // @codingStandardsIgnoreStart
        $this->test->currentWindow()->size(array('width' => intval(viewportWIDTH), 'height' => intval(viewportHEIGHT)));
        // @codingStandardsIgnoreУтв
        if (!is_null($this->redirectUrl) && $redirect) {
            $this->test->url($this->redirectUrl);
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
                $status = $testCase->execute(
                    array(
                        'script' => "return typeof(jQuery.isActive) == 'undefined' || !jQuery.isActive()",
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
     * Reload current page specified in redirect URL
     *
     * @return $this
     */
    public function refresh()
    {
        if (!is_null($this->redirectUrl)) {
            $this->test->url($this->redirectUrl);
            $this->waitPageToLoad();
            $this->waitForAjax();
        }

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
     * @param $title
     * @param string $message
     * @return mixed
     */
    public function assertTitle($title, $message = '')
    {
        PHPUnit_Framework_Assert::assertEquals(
            $title,
            $this->test->title(),
            $message
        );
        return $this;
    }

    /**
     * @param $messageText
     * @param string $message
     * @return $this
     */
    public function assertMessage($messageText, $message = '')
    {
        PHPUnit_Framework_Assert::assertTrue(
            $this->isElementPresent(
                "//div[@id = 'flash-messages']//div[@class = 'message']"
            ),
            'Flash message is missing'
        );
        $actualResult = $this->test->byXPath(
            "//div[@id = 'flash-messages']//div[@class = 'message']"
        )->attribute('innerHTML');

        PHPUnit_Framework_Assert::assertEquals($messageText, trim($actualResult), $message);
        return $this;
    }

    /**
     * @param $messageText
     * @param string $message
     * @return $this
     */
    public function assertErrorMessage($messageText, $message = '')
    {
        PHPUnit_Framework_Assert::assertTrue(
            $this->isElementPresent("//div[contains(@class,'alert') and not(contains(@class, 'alert-empty'))]"),
            'Flash message is missing'
        );
        $actualResult = $this->test->byXPath(
            "//div[contains(@class,'alert') and not(contains(@class, 'alert-empty'))]/div"
        )->text();

        PHPUnit_Framework_Assert::assertEquals($messageText, trim($actualResult), $message);
        return $this;
    }
    /**
     * @param $xpath
     * @param string $message
     * @return $this
     */
    public function assertElementPresent($xpath, $message = '')
    {
        PHPUnit_Framework_Assert::assertTrue(
            $this->isElementPresent($xpath),
            $message
        );
        return $this;
    }

    /**
     * @param $xpath
     * @param string $message
     * @return $this
     */
    public function assertElementNotPresent($xpath, $message = '')
    {
        PHPUnit_Framework_Assert::assertFalse(
            $this->isElementPresent($xpath),
            $message
        );
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
}
