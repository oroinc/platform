<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Entity;

use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Entity\GridViewUser;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\TestCase;

class GridViewUserTest extends TestCase
{
    /**
     * @dataProvider provider
     */
    public function testSettersAndGetters(string $property, mixed $value): void
    {
        $gridView = new GridViewUser();

        call_user_func([$gridView, 'set' . ucfirst($property)], $value);
        self::assertEquals($value, call_user_func_array([$gridView, 'get' . ucfirst($property)], []));
    }

    public function provider(): array
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
