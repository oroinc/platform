<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Annotation;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

class AclTest extends \PHPUnit\Framework\TestCase
{
    public function testAnnotation()
    {
        $annotation = new Acl(
            [
                'id' => 'test_acl',
                'type' => 'SomeType',
                'class' => \stdClass::class,
                'permission' => 'SomePermission',
                'group_name' => 'SomeGroup',
                'label' => 'SomeLabel',
                'description' => 'SomeDescription',
                'category' => 'SomeCategory',
                'ignore_class_acl' => true
            ]
        );
        $this->assertEquals('test_acl', $annotation->getId());
        $this->assertEquals('SomeType', $annotation->getType());
        $this->assertEquals(\stdClass::class, $annotation->getClass());
        $this->assertEquals('SomePermission', $annotation->getPermission());
        $this->assertEquals('SomeGroup', $annotation->getGroup());
        $this->assertEquals('SomeLabel', $annotation->getLabel());
        $this->assertEquals('SomeDescription', $annotation->getDescription());
        $this->assertEquals('SomeCategory', $annotation->getCategory());
        $this->assertTrue($annotation->getIgnoreClassAcl());
    }

    public function testAnnotationWithEmptyId()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Acl(['id' => '']);
    }

    public function testAnnotationWithInvalidId()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Acl(['id' => 'test acl']);
    }

    public function testAnnotationWithMissingId()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Acl([]);
    }

    public function testAnnotationWithEmptyType()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Acl(['id' => 'test', 'type' => '']);
    }

    public function testAnnotationWithMissingType()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Acl(['id' => 'test']);
    }
}
