<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Metadata;

use Oro\Bundle\SecurityBundle\Attribute\Acl as AclAttribute;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor as AclAttributeAncestor;
use Oro\Bundle\SecurityBundle\Metadata\AclAttributeStorage;
use PHPUnit\Framework\TestCase;

class AclAttributeStorageTest extends TestCase
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testStorage(): void
    {
        $storage = new AclAttributeStorage();

        $storage->add(
            AclAttribute::fromArray(['id' => 'attribute_wo_bindings', 'type' => 'entity'])
        );
        $storage->add(
            AclAttribute::fromArray(['id' => 'attribute_with_class_bindings', 'type' => 'entity']),
            'Acme\SomeClass'
        );
        $storage->add(
            AclAttribute::fromArray(['id' => 'attribute_with_method_bindings', 'type' => 'entity']),
            'Acme\SomeClass',
            'SomeMethod'
        );

        $storage->add(
            AclAttribute::fromArray(['id' => 'attribute1', 'type' => 'entity'])
        );
        $storage->addAncestor(
            AclAttributeAncestor::fromArray(['value' => 'attribute1']),
            'Acme\SomeClass1'
        );
        $storage->add(
            AclAttribute::fromArray(['id' => 'attribute2', 'type' => 'entity'])
        );
        $storage->addAncestor(
            AclAttributeAncestor::fromArray(['value' => 'attribute2']),
            'Acme\SomeClass1',
            'SomeMethod'
        );

        $this->assertEquals(
            'attribute_wo_bindings',
            $storage->findById('attribute_wo_bindings')->getId()
        );
        $this->assertEquals(
            'attribute_with_class_bindings',
            $storage->findById('attribute_with_class_bindings')->getId()
        );
        $this->assertEquals(
            'attribute_with_class_bindings',
            $storage->find('Acme\SomeClass')->getId()
        );
        $this->assertEquals(
            'attribute_with_method_bindings',
            $storage->findById('attribute_with_method_bindings')->getId()
        );
        $this->assertEquals(
            'attribute_with_method_bindings',
            $storage->find('Acme\SomeClass', 'SomeMethod')->getId()
        );
        $this->assertEquals(
            'attribute1',
            $storage->findById('attribute1')->getId()
        );
        $this->assertEquals(
            'attribute1',
            $storage->find('Acme\SomeClass1')->getId()
        );
        $this->assertEquals(
            'attribute2',
            $storage->findById('attribute2')->getId()
        );
        $this->assertEquals(
            'attribute2',
            $storage->find('Acme\SomeClass1', 'SomeMethod')->getId()
        );

        // test 'has' method
        $this->assertTrue($storage->has('Acme\SomeClass'));
        $this->assertFalse($storage->has('Acme\UnknownClass'));
        $this->assertTrue($storage->has('Acme\SomeClass', 'SomeMethod'));
        $this->assertFalse($storage->has('Acme\SomeClass', 'UnknownMethod'));
        $this->assertFalse($storage->has('Acme\UnknownClass', 'SomeMethod'));

        // test isKnownClass and isKnownMethod methods
        $this->assertTrue($storage->isKnownClass('Acme\SomeClass'));
        $this->assertFalse($storage->isKnownClass('Acme\UnknownClass'));
        $this->assertTrue($storage->isKnownMethod('Acme\SomeClass', 'SomeMethod'));
        $this->assertFalse($storage->isKnownMethod('Acme\SomeClass', 'UnknownMethod'));
        $this->assertFalse($storage->isKnownMethod('Acme\UnknownClass', 'SomeMethod'));

        // test attribute override
        $this->assertEquals(
            'entity',
            $storage->findById('attribute2')->getType()
        );
        $storage->add(
            AclAttribute::fromArray(['id' => 'attribute2', 'type' => 'action'])
        );
        $this->assertEquals(
            'action',
            $storage->findById('attribute2')->getType()
        );

        // test duplicate bindings
        $storage->addAncestor(
            AclAttributeAncestor::fromArray(['value' => 'attribute2']),
            'Acme\SomeClass1',
            'SomeMethod'
        );
        $this->expectException(\RuntimeException::class);
        $storage->addAncestor(
            AclAttributeAncestor::fromArray(['value' => 'attribute1']),
            'Acme\SomeClass1',
            'SomeMethod'
        );
    }

    public function testSerialization(): void
    {
        $storage = new AclAttributeStorage();
        $storage->add(
            AclAttribute::fromArray(['id' => 'attribute', 'type' => 'entity']),
            'Acme\SomeClass',
            'SomeMethod'
        );
        $this->assertEquals('attribute', $storage->findById('attribute')->getId());
        $this->assertEquals('attribute', $storage->find('Acme\SomeClass', 'SomeMethod')->getId());

        $data = serialize($storage);
        $storage = unserialize($data);
        $this->assertEquals('attribute', $storage->findById('attribute')->getId());
        $this->assertEquals('attribute', $storage->find('Acme\SomeClass', 'SomeMethod')->getId());
    }
}
