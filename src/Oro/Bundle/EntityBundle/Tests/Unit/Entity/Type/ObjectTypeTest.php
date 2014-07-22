<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Entity\Type;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Type;

class ObjectTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testSerialization()
    {
        $object = new \stdClass();
        $object->a = 'test1';

        $encoded = base64_encode(serialize($object));

        $platform = new MySqlPlatform();
        Type::overrideType(Type::OBJECT, 'Oro\Bundle\EntityBundle\Entity\Type\ObjectType');
        $type = Type::getType(Type::OBJECT);

        $actualDbValue = $type->convertToDatabaseValue($object, $platform);
        $this->assertEquals($encoded, $actualDbValue);
        $this->assertEquals($object, $type->convertToPHPValue($actualDbValue, $platform));
    }
}
