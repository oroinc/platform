<?php

namespace Oro\Bundle\SidebarBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\SidebarBundle\Entity\SidebarState;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

class SidebarStateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider propertiesDataProvider
     */
    public function testSettersAndGetters(string $property, string|object $value)
    {
        $obj = new SidebarState();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider(): array
    {
        return [
            ['user', $this->createMock(AbstractUser::class)],
            ['position', 'SIDEBAR_RIGHT'],
            ['state', 'SIDEBAR_MINIMIZED'],
        ];
    }
}
