<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\AliasCollection;

class AliasCollectionTest extends \PHPUnit\Framework\TestCase
{
    /** @var AliasCollection */
    protected $aliasCollection;

    protected function setUp()
    {
        $this->aliasCollection = new AliasCollection();
    }

    public function testIsEmpty()
    {
        $this->assertTrue($this->aliasCollection->isEmpty());

        $this->aliasCollection->add('test_alias', 'test_id');
        $this->assertFalse($this->aliasCollection->isEmpty());

        $this->aliasCollection->remove('test_alias');
        $this->assertTrue($this->aliasCollection->isEmpty());
    }

    public function testClear()
    {
        $this->aliasCollection->add('test_alias', 'test_id');

        $this->aliasCollection->clear();
        $this->assertTrue($this->aliasCollection->isEmpty());
    }

    public function testGetAliases()
    {
        $this->aliasCollection->add('test_alias', 'test_id');
        $this->aliasCollection->add('another_alias', 'test_alias');
        $this->assertEquals(
            ['test_alias', 'another_alias'],
            $this->aliasCollection->getAliases('test_id')
        );
        $this->assertEquals(
            [],
            $this->aliasCollection->getAliases('unknown')
        );
    }

    public function testAdd()
    {
        $this->aliasCollection->add('test_alias', 'test_id');
        $this->aliasCollection->add('another_alias', 'test_alias');
        $this->assertTrue($this->aliasCollection->has('test_alias'));
        $this->assertTrue($this->aliasCollection->has('another_alias'));
        $this->assertEquals('test_id', $this->aliasCollection->getId('test_alias'));
        $this->assertEquals('test_id', $this->aliasCollection->getId('another_alias'));
    }

    public function testAddDuplicate()
    {
        $this->aliasCollection->add('test_alias', 'test_id');
        $this->aliasCollection->add('test_alias', 'test_id');
        $this->assertTrue($this->aliasCollection->has('test_alias'));
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\AliasAlreadyExistsException
     * @expectedExceptionMessage The "test_alias" sting cannot be used as an alias for "another_id" item because it is already used for "test_id" item.
     */
    // @codingStandardsIgnoreEnd
    public function testRedefine()
    {
        $this->aliasCollection->add('test_alias', 'test_id');
        $this->aliasCollection->add('test_alias', 'another_id');
    }

    public function testRemove()
    {
        // another_alias -> test_alias -> test_id
        $this->aliasCollection->add('test_alias', 'test_id');
        $this->aliasCollection->add('another_alias', 'test_alias');
        $this->assertTrue($this->aliasCollection->has('test_alias'));
        $this->assertTrue($this->aliasCollection->has('another_alias'));

        $this->aliasCollection->remove('another_alias');
        $this->assertTrue($this->aliasCollection->has('test_alias'));
        $this->assertFalse($this->aliasCollection->has('another_alias'));

        $this->aliasCollection->remove('test_alias');
        $this->assertFalse($this->aliasCollection->has('test_alias'));
    }

    public function testRemoveIntermediateAlias()
    {
        // last_alias -> another_alias -> test_alias -> test_id
        $this->aliasCollection->add('test_alias', 'test_id');
        $this->aliasCollection->add('another_alias', 'test_alias');
        $this->aliasCollection->add('last_alias', 'another_alias');
        $this->assertTrue($this->aliasCollection->has('test_alias'));
        $this->assertTrue($this->aliasCollection->has('another_alias'));

        $this->aliasCollection->remove('test_alias');
        $this->assertFalse($this->aliasCollection->has('test_alias'));
        $this->assertFalse($this->aliasCollection->has('another_alias'));
        $this->assertFalse($this->aliasCollection->has('last_alias'));
    }

    public function testRemoveById()
    {
        // another_alias -> test_alias -> test_id
        $this->aliasCollection->add('test_alias', 'test_id');
        $this->aliasCollection->add('another_alias', 'test_alias');
        $this->assertTrue($this->aliasCollection->has('test_alias'));
        $this->assertTrue($this->aliasCollection->has('another_alias'));

        $this->aliasCollection->removeById('test_id');
        $this->assertFalse($this->aliasCollection->has('test_alias'));
        $this->assertFalse($this->aliasCollection->has('another_alias'));
    }

    public function testGetIdUndefined()
    {
        $this->assertNull($this->aliasCollection->getId('test_alias'));
    }

    /**
     * No any exception is expected
     */
    public function testRemoveUndefined()
    {
        $this->aliasCollection->remove('test_alias');
    }

    /**
     * No any exception is expected
     */
    public function testRemoveByIdUndefined()
    {
        $this->aliasCollection->removeById('test_id');
    }
}
