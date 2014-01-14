<?php

namespace Oro\Bundle\UserBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class Groups
 *
 * @package Oro\Bundle\UserBundle\Tests\Selenium\Pages
 * @method \Oro\Bundle\UserBundle\Tests\Selenium\Pages\Groups openGroups() openGroups()
 * @method \Oro\Bundle\UserBundle\Tests\Selenium\Pages\Groups assertTitle() assertTitle($title, $message = '')
 */
class Groups extends AbstractPageFilteredGrid
{
    const URL = 'user/group';

    public function __construct($testCase, $redirect = true)
    {
        $this->redirectUrl = self::URL;
        parent::__construct($testCase, $redirect);

    }

    public function add()
    {
        $this->test->byXpath("//a[@title = 'Create group']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return new Group($this->test);
    }

    public function open($entityData = array())
    {
        return;
    }
}
