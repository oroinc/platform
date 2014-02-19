<?php

namespace Oro\Bundle\UserBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class Users
 *
 * @package Oro\Bundle\UserBundle\Tests\Selenium\Pages
 * @method \Oro\Bundle\UserBundle\Tests\Selenium\Pages\Users openUsers() openUsers()
 * @method \Oro\Bundle\UserBundle\Tests\Selenium\Pages\Users assertTitle() assertTitle($title, $message = '')
 */
class Users extends AbstractPageFilteredGrid
{
    const URL = 'user';

    public function __construct($testCase, $redirect = true)
    {
        $this->redirectUrl = self::URL;
        parent::__construct($testCase, $redirect);

    }

    /**
     * @return User
     */
    public function add()
    {
        $this->test->byXPath("//a[@title='Create user']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        $user = new User($this->test);
        return $user->init(true);
    }

    /**
     * @param array $entityData
     *
     * @return User
     */
    public function open($entityData = array())
    {
        $user = $this->getEntity($entityData);
        $user->click();
        sleep(1);
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new User($this->test);
    }
}
