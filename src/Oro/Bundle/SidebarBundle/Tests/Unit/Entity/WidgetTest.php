<?php

namespace Oro\Bundle\SidebarBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SidebarBundle\Entity\Widget;
use Symfony\Component\Security\Core\User\UserInterface;

class WidgetTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider propertiesDataProvider
     */
    public function testSettersAndGetters(string $property, mixed $value)
    {
        $obj = new Widget();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider(): array
    {
        return [
            ['user', $this->createMock(UserInterface::class)],
            ['widgetName', 'test'],
            ['placement', 'left'],
            ['position', 1],
            ['state', 'WIDGET_MAXIMIZED_HOVER'],
            ['settings', ['a' => 'b']],
            ['organization', new Organization()]
        ];
    }
}
