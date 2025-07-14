<?php

namespace Oro\Bundle\SidebarBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SidebarBundle\Entity\Widget;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use PHPUnit\Framework\TestCase;

class WidgetTest extends TestCase
{
    /**
     * @dataProvider propertiesDataProvider
     */
    public function testSettersAndGetters(string $property, mixed $value): void
    {
        $obj = new Widget();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider(): array
    {
        return [
            ['user', $this->createMock(AbstractUser::class)],
            ['widgetName', 'test'],
            ['placement', 'left'],
            ['position', 1],
            ['state', 'WIDGET_MAXIMIZED_HOVER'],
            ['settings', ['a' => 'b']],
            ['organization', new Organization()]
        ];
    }
}
