<?php

namespace Oro\Bundle\TestFrameworkBundle\Pages\Objects;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractEntity;
use Oro\Bundle\TestFrameworkBundle\Pages\Entity;
use Oro\Bundle\TestFrameworkBundle\Pages\Page;

class OroFinish extends Page
{
    public function __construct($testCase, $redirect = true)
    {
        parent::__construct($testCase, $redirect);
    }

    public function lunch()
    {
        //save current windows
        $oldWindows = $this->test->windowHandles();
        $this->test->moveto($this->byXpath("//a[@class = 'button primary']"));
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
