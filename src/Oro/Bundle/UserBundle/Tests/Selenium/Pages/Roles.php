<?php

namespace Oro\Bundle\UserBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class Roles
 *
 * @package Oro\Bundle\UserBundle\Tests\Selenium\Pages
 * @method \Oro\Bundle\UserBundle\Tests\Selenium\Pages\Roles openRoles() openRoles()
 * @method \Oro\Bundle\UserBundle\Tests\Selenium\Pages\Roles assertTitle() assertTitle($title, $message = '')
 */
class Roles extends AbstractPageFilteredGrid
{
    const URL = 'user/role';

    public function __construct($testCase, $redirect = true)
    {
        $this->redirectUrl = self::URL;
        parent::__construct($testCase, $redirect);

    }

    public function add()
    {
        $this->test->byXpath("//a[@title='Create role']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return new Role($this->test);
    }

    public function open($roleName = array())
    {
        $this->getEntity($roleName)->click();
        sleep(1);
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new Role($this->test);
    }
}
