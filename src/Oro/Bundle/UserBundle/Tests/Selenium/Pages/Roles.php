<?php

namespace Oro\Bundle\UserBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class Roles
 *
 * @package Oro\Bundle\UserBundle\Tests\Selenium\Pages
 * @method Roles openRoles(string $bundlePath)
 * @method Role add()
 * @method Role open(array $filter)
 * {@inheritdoc}
 */
class Roles extends AbstractPageFilteredGrid
{
    const NEW_ENTITY_BUTTON = "//a[@title='Create Role']";
    const URL = 'user/role';

    public function entityNew()
    {
        return new Role($this->test);
    }

    public function entityView()
    {
        return new Role($this->test);
    }
}
