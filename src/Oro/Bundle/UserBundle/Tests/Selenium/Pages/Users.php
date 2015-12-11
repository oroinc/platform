<?php

namespace Oro\Bundle\UserBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class Users
 *
 * @package Oro\Bundle\UserBundle\Tests\Selenium\Pages
 * @method Users openUsers(string $bundlePath)
 * @method User open(array $filter)
 * @method User add()
 * @method Users changePage(integer $page)
 * {@inheritdoc}
 */
class Users extends AbstractPageFilteredGrid
{
    const NEW_ENTITY_BUTTON = "//a[@title='Create User']";
    const URL = 'user';

    public function entityNew()
    {
        $user = new User($this->test);
        return $user->init(true);
    }

    public function entityView()
    {
        return new User($this->test);
    }
}
