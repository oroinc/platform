<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Entity;

use Oro\Bundle\DataGridBundle\Entity\GridViewUser;
use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\UserBundle\Entity\User;

class GridViewUserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provider
     * @param $property
     * @param $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $gridView = new GridViewUser();

        call_user_func_array(array($gridView, 'set' . ucfirst($property)), array($value));
        static::assertEquals($value, call_user_func_array(array($gridView, 'get' . ucfirst($property)), array()));
    }

    public function provider()
    {
        $user = new User();
        $user->setUsername('username');

        $gridView = new GridView();
        $gridView->setName('test');

        return [
            ['user', $user],
            ['gridName', 'grid'],
            ['alias', 'test'],
            ['gridView', $gridView]
        ];
    }
}
