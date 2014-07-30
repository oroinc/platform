<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Model;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EmailBundle\Form\Model\Email;

class EmailTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed  $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $obj = new Email();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider()
    {
        return [
            ['gridName', 'testGridName'],
            ['entityClass', 'testEntityClass'],
            ['entityId', 123],
            ['from', 'test@example.com'],
            ['to', ['testGridName']],
            ['subject', 'testSubject'],
            ['body', 'testBody'],
            ['gridName', 'testGridName'],
            ['gridName', 'testGridName'],
            ['gridName', 'testGridName'],
            ['gridName', 'testGridName'],
        ];
    }

    public function testHasEntity()
    {
        $obj = new Email();
        $this->assertFalse($obj->hasEntity());

        $obj->setEntityClass('Test\Entity');
        $this->assertFalse($obj->hasEntity());

        $obj->setEntityId(123);
        $this->assertTrue($obj->hasEntity());

        $obj->setEntityClass(null);
        $this->assertFalse($obj->hasEntity());
    }
}
