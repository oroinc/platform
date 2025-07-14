<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Attribute;

use Oro\Bundle\SecurityBundle\Attribute\Acl;
use PHPUnit\Framework\TestCase;

class AclTest extends TestCase
{
    public function testAttribute(): void
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

    public function testAttributeWithEmptyId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Acl(id: '');
    }

    public function testAttributeWithInvalidId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Acl(id: 'test acl');
    }

    public function testAttributeWithMissingId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Acl::fromArray([]);
    }

    public function testAttributeWithNull(): void
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

    public function testAttributeWithEmptyType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Acl(id: 'test', type: '');
    }

    public function testAttributeWithMissingType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Acl(id: 'test');
    }
}
