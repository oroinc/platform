<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Attribute;

use Oro\Bundle\SecurityBundle\Attribute\Acl;

class AclTest extends \PHPUnit\Framework\TestCase
{
    public function testAttribute()
    {
        $attribute = new Acl(
            id: 'test_acl',
            type: 'SomeType',
            ignoreClassAcl: true,
            class: \stdClass::class,
            permission: 'SomePermission',
            groupName: 'SomeGroup',
            label: 'SomeLabel',
            description: 'SomeDescription',
            category: 'SomeCategory',
        );
        $this->assertEquals('test_acl', $attribute->getId());
        $this->assertEquals('SomeType', $attribute->getType());
        $this->assertEquals(\stdClass::class, $attribute->getClass());
        $this->assertEquals('SomePermission', $attribute->getPermission());
        $this->assertEquals('SomeGroup', $attribute->getGroup());
        $this->assertEquals('SomeLabel', $attribute->getLabel());
        $this->assertEquals('SomeDescription', $attribute->getDescription());
        $this->assertEquals('SomeCategory', $attribute->getCategory());
        $this->assertTrue($attribute->getIgnoreClassAcl());
    }

    public function testAttributeWithEmptyId()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Acl(id: '');
    }

    public function testAttributeWithInvalidId()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Acl(id: 'test acl');
    }

    public function testAttributeWithMissingId()
    {
        $this->expectException(\InvalidArgumentException::class);
        Acl::fromArray([]);
    }

    public function testAttributeWithNull()
    {
        $attribute = Acl::fromArray();

        $this->assertNull($attribute->getId());
        $this->assertNull($attribute->getType());
        $this->assertNull($attribute->getClass());
        $this->assertNull($attribute->getPermission());
        $this->assertNull($attribute->getGroup());
        $this->assertNull($attribute->getLabel());
        $this->assertNull($attribute->getDescription());
        $this->assertNull($attribute->getCategory());
    }

    public function testAttributeWithEmptyType()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Acl(id: 'test', type: '');
    }

    public function testAttributeWithMissingType()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Acl(id: 'test');
    }
}
