<?php

namespace Oro\Bundle\InstallerBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Login;

/**
 * Class OroFinish
 *
 * @package Oro\Bundle\InstallerBundle\Tests\Selenium\Pages
 */
class OroFinish extends AbstractPage
{
    public function __construct($testCase, $redirect = true)
    {
        parent::__construct($testCase, $redirect);
    }

    public function lunch()
    {
        //save current windows
        $oldWindows = $this->test->windowHandles();
        $this->test->moveto($this->test->byXpath("//a[@class = 'button primary']"));
        $this->test->byXpath("//a[@class = 'button primary']")->click();
        $this->waitPageToLoad();
        //switch to new window
        $newWindows = $this->test->windowHandles();
        //diff arrays
        $login = array_diff($newWindows, $oldWindows);
        $this->test->window(reset($login));
        $this->waitPageToLoad();
        $this->assertTitle('Login');
        return new Login($this->test, array());
    }
}
