<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\RawLayout;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RawLayoutTest extends \PHPUnit\Framework\TestCase
{
    /** @var RawLayout */
    protected $rawLayout;

    protected function setUp()
    {
        $this->rawLayout = new RawLayout();
    }

    public function testIsEmpty()
    {
        $this->assertTrue($this->rawLayout->isEmpty());

        $this->rawLayout->add('root', null, 'root');
        $this->assertFalse($this->rawLayout->isEmpty());
    }

    public function testClear()
    {
        $this->rawLayout->add('root', null, 'root');

        $this->rawLayout->clear();
        $this->assertTrue($this->rawLayout->isEmpty());
    }

    public function testGetRootId()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');

        // do test
        $this->assertEquals('root', $this->rawLayout->getRootId());
    }

    public function testGetParentId()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');

        // do test
        $this->assertEquals('root', $this->rawLayout->getParentId('header'));
    }

    public function testGetParentIdByAlias()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');
        $this->rawLayout->addAlias('header_alias', 'header');

        // do test
        $this->assertEquals('root', $this->rawLayout->getParentId('header_alias'));
    }

    public function testGetParentIdForRootItem()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');

        // do test
        $this->assertEquals(null, $this->rawLayout->getParentId('root'));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" item does not exist.
     */
    public function testGetParentIdForUnknownItem()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');

        // do test
        $this->rawLayout->getParentId('unknown');
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testGetParentIdWithEmptyId($id)
    {
        $this->rawLayout->getParentId($id);
    }

    public function testResolveId()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');
        $this->rawLayout->addAlias('test_header', 'header');
        $this->rawLayout->addAlias('another_header', 'test_header');

        // do test
        $this->assertEquals('header', $this->rawLayout->resolveId('header'));
        $this->assertEquals('header', $this->rawLayout->resolveId('test_header'));
        $this->assertEquals('header', $this->rawLayout->resolveId('another_header'));
        $this->assertEquals('unknown', $this->rawLayout->resolveId('unknown'));
    }

    public function testHas()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');
        $this->rawLayout->addAlias('test_header', 'header');
        $this->rawLayout->addAlias('another_header', 'test_header');

        // do test
        $this->assertTrue($this->rawLayout->has('header'));
        $this->assertTrue($this->rawLayout->has('test_header'));
        $this->assertTrue($this->rawLayout->has('another_header'));
        $this->assertFalse($this->rawLayout->has('unknown'));
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testAddWithEmptyId($id)
    {
        $this->rawLayout->add($id, null, 'root');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid "id" argument type. Expected "string", "integer" given.
     */
    public function testAddWithNotStringId()
    {
        $this->rawLayout->add(123, null, 'root');
    }

    /**
     * @dataProvider invalidIdDataProvider
     */
    public function testAddWithInvalidId($id)
    {
        $this->expectException('\Oro\Component\Layout\Exception\InvalidArgumentException');
        $this->expectExceptionMessage(
            sprintf(
                'The "%s" string cannot be used as the item id because it contains illegal characters. '
                . 'The valid item id should start with a letter and only contain '
                . 'letters, numbers, underscores ("_"), hyphens ("-") and colons (":").',
                $id
            )
        );
        $this->rawLayout->add($id, null, 'root');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemAlreadyExistsException
     * @expectedExceptionMessage The "root" item already exists. Remove existing item before add the new item with the same id.
     */
    // @codingStandardsIgnoreEnd
    public function testAddDuplicate()
    {
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('root', null, 'root');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The "another_root" item cannot be the root item because another root item ("root") already exists.
     */
    // @codingStandardsIgnoreEnd
    public function testRedefineRoot()
    {
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('another_root', null, 'root');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" sibling item does not exist.
     */
    public function testAddToUnknownSibling()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header1', 'root', 'header');
        $this->rawLayout->add('item1', 'header1', 'label');

        // do test
        $this->rawLayout->add('item2', 'header1', 'label', [], 'unknown');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The sibling item cannot be the same as the parent item.
     */
    public function testAddWhenParentEqualsToSibling()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header1', 'root', 'header');
        $this->rawLayout->add('item1', 'header1', 'label');
        $this->rawLayout->addAlias('test_header', 'header1');
        $this->rawLayout->addAlias('test_item', 'test_header');

        // do test
        $this->rawLayout->add('item2', 'test_header', 'label', [], 'test_item');
    }

    public function testAddToEnd()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header1', 'root', 'header');
        $this->rawLayout->add('item1', 'header1', 'label');
        $this->rawLayout->add('item2', 'header1', 'label');
        $this->rawLayout->addAlias('test_header', 'header1');

        // do test
        $this->rawLayout->add('item3', 'test_header', 'label', []);
        $this->assertSame(
            ['item1', 'item2', 'item3'],
            array_keys($this->rawLayout->getHierarchy('header1'))
        );
    }

    public function testAddToBegin()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header1', 'root', 'header');
        $this->rawLayout->add('item1', 'header1', 'label');
        $this->rawLayout->add('item2', 'header1', 'label');
        $this->rawLayout->addAlias('test_header', 'header1');

        // do test
        $this->rawLayout->add('item3', 'test_header', 'label', [], null, true);
        $this->assertSame(
            ['item3', 'item1', 'item2'],
            array_keys($this->rawLayout->getHierarchy('header1'))
        );
    }

    public function testAddAfterSibling()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header1', 'root', 'header');
        $this->rawLayout->add('item1', 'header1', 'label');
        $this->rawLayout->add('item2', 'header1', 'label');
        $this->rawLayout->addAlias('test_header', 'header1');
        $this->rawLayout->addAlias('test_item', 'item1');

        // do test
        $this->rawLayout->add('item3', 'test_header', 'label', [], 'test_item');
        $this->assertSame(
            ['item1', 'item3', 'item2'],
            array_keys($this->rawLayout->getHierarchy('header1'))
        );
    }

    public function testAddBeforeSibling()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header1', 'root', 'header');
        $this->rawLayout->add('item1', 'header1', 'label');
        $this->rawLayout->add('item2', 'header1', 'label');
        $this->rawLayout->addAlias('test_header', 'header1');
        $this->rawLayout->addAlias('test_item', 'item2');

        // do test
        $this->rawLayout->add('item3', 'test_header', 'label', [], 'test_item', true);
        $this->assertSame(
            ['item1', 'item3', 'item2'],
            array_keys($this->rawLayout->getHierarchy('header1'))
        );
    }

    public function testRemove()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');
        $this->rawLayout->add('item1', 'header', 'label');
        $this->rawLayout->add('item2', 'header', ContainerType::NAME);
        $this->rawLayout->add('item3', 'item2', 'label');

        // do test
        $this->rawLayout->remove('header');
        $this->assertFalse($this->rawLayout->has('header'));
        $this->assertFalse($this->rawLayout->has('item1'));
        $this->assertFalse($this->rawLayout->has('item2'));
        $this->assertFalse($this->rawLayout->has('item3'));
    }

    public function testRemoveByAlias()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');
        $this->rawLayout->addAlias('test_header', 'header');

        // do test
        $this->rawLayout->remove('test_header');
        $this->assertFalse($this->rawLayout->has('header'));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" item does not exist.
     */
    public function testRemoveUnknown()
    {
        $this->rawLayout->remove('unknown');
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testRemoveWithEmptyId($id)
    {
        $this->rawLayout->remove($id);
    }

    public function testMoveToParent()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header1', 'root', 'header');
        $this->rawLayout->add('container1', 'header1', ContainerType::NAME);
        $this->rawLayout->add('item1', 'container1', 'label');
        $this->rawLayout->add('header2', 'root', 'header');
        $this->rawLayout->add('container2', 'header2', ContainerType::NAME);
        $this->rawLayout->add('item2', 'container2', 'label');

        // do test
        $this->rawLayout->move('container1', 'root');
        $this->assertSame(
            ['root', 'container1'],
            $this->rawLayout->getProperty('container1', RawLayout::PATH)
        );
        $this->assertSame(
            ['root', 'container1', 'item1'],
            $this->rawLayout->getProperty('item1', RawLayout::PATH)
        );
    }

    public function testMoveToParentByAlias()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header1', 'root', 'header');
        $this->rawLayout->add('container1', 'header1', ContainerType::NAME);
        $this->rawLayout->add('item1', 'container1', 'label');
        $this->rawLayout->add('header2', 'root', 'header');
        $this->rawLayout->add('container2', 'header2', ContainerType::NAME);
        $this->rawLayout->add('item2', 'container2', 'label');
        $this->rawLayout->addAlias('test_root', 'root');
        $this->rawLayout->addAlias('test_container1', 'container1');

        // do test
        $this->rawLayout->move('test_container1', 'test_root');
        $this->assertSame(
            ['root', 'container1'],
            $this->rawLayout->getProperty('container1', RawLayout::PATH)
        );
        $this->assertSame(
            ['root', 'container1', 'item1'],
            $this->rawLayout->getProperty('item1', RawLayout::PATH)
        );
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" item does not exist.
     */
    public function testMoveUnknown()
    {
        $this->rawLayout->move('unknown');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" parent item does not exist.
     */
    public function testMoveToUnknownParent()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header1', 'root', 'header');
        $this->rawLayout->add('item1', 'header1', 'label');
        $this->rawLayout->add('header2', 'root', 'header');
        $this->rawLayout->add('item2', 'header2', 'label');

        // do test
        $this->rawLayout->move('item1', 'unknown', 'item2');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" sibling item does not exist.
     */
    public function testMoveToUnknownSibling()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header1', 'root', 'header');
        $this->rawLayout->add('item1', 'header1', 'label');
        $this->rawLayout->add('header2', 'root', 'header');
        $this->rawLayout->add('item2', 'header2', 'label');

        // do test
        $this->rawLayout->move('item1', 'header2', 'unknown');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The parent item cannot be the same as the moving item.
     */
    public function testMoveWhenParentEqualsToMovingItem()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header1', 'root', 'header');
        $this->rawLayout->add('item1', 'header1', 'label');
        $this->rawLayout->add('header2', 'root', 'header');
        $this->rawLayout->add('item2', 'header2', 'label');
        $this->rawLayout->addAlias('test_item', 'item1');

        // do test
        $this->rawLayout->move('item1', 'test_item', 'item2');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The sibling item cannot be the same as the moving item.
     */
    public function testMoveWhenSiblingEqualsToMovingItem()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header1', 'root', 'header');
        $this->rawLayout->add('item1', 'header1', 'label');
        $this->rawLayout->add('header2', 'root', 'header');
        $this->rawLayout->add('item2', 'header2', 'label');
        $this->rawLayout->addAlias('test_item', 'item1');

        // do test
        $this->rawLayout->move('item1', 'header2', 'test_item');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The sibling item cannot be the same as the parent item.
     */
    public function testMoveWhenParentEqualsToSibling()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header1', 'root', 'header');
        $this->rawLayout->add('item1', 'header1', 'label');
        $this->rawLayout->add('header2', 'root', 'header');
        $this->rawLayout->add('item2', 'header2', 'label');
        $this->rawLayout->addAlias('test_header', 'header2');
        $this->rawLayout->addAlias('test_item', 'test_header');

        // do test
        $this->rawLayout->move('item1', 'test_header', 'test_item');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage At least one parent or sibling item must be specified.
     */
    public function testMoveWithoutParentAndSibling()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header1', 'root', 'header');
        $this->rawLayout->add('item1', 'header1', 'label');

        // do test
        $this->rawLayout->move('item1');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The parent item (path: root/header1/item1) cannot be a child of the moving item (path: root/header1).
     */
    // @codingStandardsIgnoreEnd
    public function testMoveParentToChild()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header1', 'root', 'header');
        $this->rawLayout->add('item1', 'header1', 'label');
        $this->rawLayout->addAlias('test_header', 'header1');
        $this->rawLayout->addAlias('test_item', 'item1');

        // do test
        $this->rawLayout->move('test_header', 'test_item');
    }

    public function testMoveInsideTheSameParent()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header1', 'root', 'header');
        $this->rawLayout->add('item1', 'header1', 'label');
        $this->rawLayout->add('item2', 'header1', 'label');
        $this->rawLayout->addAlias('test_item', 'item1');

        // do test
        $this->rawLayout->move('test_item', null, 'item2');
        $this->assertSame(
            ['root', 'header1', 'item1'],
            $this->rawLayout->getProperty('item1', RawLayout::PATH)
        );
        $this->assertSame(
            ['item2' => [], 'item1' => []],
            $this->rawLayout->getHierarchy('header1')
        );
    }

    public function testMoveInsideTheSameParentAndWithParentIdSpecified()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header1', 'root', 'header');
        $this->rawLayout->add('item1', 'header1', 'label');
        $this->rawLayout->add('item2', 'header1', 'label');
        $this->rawLayout->addAlias('test_header', 'header1');
        $this->rawLayout->addAlias('test_item', 'item1');

        // do test
        $this->rawLayout->move('test_item', 'test_header', 'item2');
        $this->assertSame(
            ['root', 'header1', 'item1'],
            $this->rawLayout->getProperty('item1', RawLayout::PATH)
        );
        $this->assertSame(
            ['item2' => [], 'item1' => []],
            $this->rawLayout->getHierarchy('header1')
        );
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testMoveWithEmptyId($id)
    {
        $this->rawLayout->move($id);
    }

    public function testHasProperty()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');

        // do test
        $this->assertTrue($this->rawLayout->hasProperty('header', RawLayout::PATH));
        $this->assertFalse($this->rawLayout->hasProperty('header', 'unknown'));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" item does not exist.
     */
    public function testHasPropertyForUnknownItem()
    {
        $this->rawLayout->hasProperty('unknown', RawLayout::PATH);
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testHasPropertyWithEmptyId($id)
    {
        $this->rawLayout->hasProperty($id, RawLayout::PATH);
    }

    public function testGetProperty()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');

        // do test
        $this->assertEquals(['root', 'header'], $this->rawLayout->getProperty('header', RawLayout::PATH));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The "header" item does not have "unknown" property.
     */
    public function testGetPropertyForUnknownProperty()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');

        // do test
        $this->rawLayout->getProperty('header', 'unknown');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" item does not exist.
     */
    public function testGetPropertyForUnknownItem()
    {
        $this->rawLayout->getProperty('unknown', RawLayout::PATH);
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testGetPropertyWithEmptyId($id)
    {
        $this->rawLayout->getProperty($id, RawLayout::PATH);
    }

    public function testSetProperty()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');

        // do test
        $this->rawLayout->setProperty('header', 'some_property', 123);
        $this->assertEquals(123, $this->rawLayout->getProperty('header', 'some_property'));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" item does not exist.
     */
    public function testSetPropertyForUnknownItem()
    {
        $this->rawLayout->setProperty('unknown', 'some_property', 123);
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testSetPropertyWithEmptyId($id)
    {
        $this->rawLayout->setProperty($id, 'some_property', 123);
    }

    public function testHasAlias()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');
        $this->rawLayout->addAlias('test_header', 'header');

        // do test
        $this->assertTrue($this->rawLayout->hasAlias('test_header'));
        $this->assertFalse($this->rawLayout->hasAlias('header'));
        $this->assertFalse($this->rawLayout->hasAlias('unknown'));
    }

    public function testAddAlias()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');
        $this->rawLayout->addAlias('test_header', 'header');

        // do test
        $this->assertTrue($this->rawLayout->hasAlias('test_header'));
        $this->assertEquals('header', $this->rawLayout->resolveId('test_header'));
    }

    public function testAddAliasWhenAliasIsAddedForAnotherAlias()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');
        $this->rawLayout->addAlias('test_header', 'header');

        // do test
        $this->rawLayout->addAlias('another_header', 'test_header');
        $this->assertTrue($this->rawLayout->hasAlias('another_header'));
        $this->assertEquals('header', $this->rawLayout->resolveId('another_header'));
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item alias must not be empty.
     */
    public function testAddAliasWithEmptyAlias($alias)
    {
        $this->rawLayout->addAlias($alias, 'root');
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testAddAliasWithEmptyId($id)
    {
        $this->rawLayout->addAlias('test', $id);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid "alias" argument type. Expected "string", "integer" given.
     */
    public function testAddAliasWithNotStringAlias()
    {
        $this->rawLayout->addAlias(123, 'root');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid "id" argument type. Expected "string", "integer" given.
     */
    public function testAddAliasWithNotStringId()
    {
        $this->rawLayout->addAlias('test', 123);
    }

    /**
     * @dataProvider invalidIdDataProvider
     */
    public function testAddAliasWithInvalidAlias($alias)
    {
        $this->expectException('\Oro\Component\Layout\Exception\InvalidArgumentException');
        $this->expectExceptionMessage(
            sprintf(
                'The "%s" string cannot be used as the item alias because it contains illegal characters. '
                . 'The valid alias should start with a letter and only contain '
                . 'letters, numbers, underscores ("_"), hyphens ("-") and colons (":").',
                $alias
            )
        );
        $this->rawLayout->addAlias($alias, 'root');
    }

    /**
     * @dataProvider invalidIdDataProvider
     */
    public function testAddAliasWithInvalidId($id)
    {
        $this->expectException('\Oro\Component\Layout\Exception\InvalidArgumentException');
        $this->expectExceptionMessage(
            sprintf(
                'The "%s" string cannot be used as the item id because it contains illegal characters. '
                . 'The valid item id should start with a letter and only contain '
                . 'letters, numbers, underscores ("_"), hyphens ("-") and colons (":").',
                $id
            )
        );
        $this->rawLayout->addAlias('test', $id);
    }

    public function testAddAliasDuplicate()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->addAlias('test', 'root');

        // do test
        $this->rawLayout->addAlias('test', 'root');
        $this->assertTrue($this->rawLayout->hasAlias('test'));
        $this->assertEquals('root', $this->rawLayout->resolveId('test'));
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\AliasAlreadyExistsException
     * @expectedExceptionMessage The "test" sting cannot be used as an alias for "header" item because it is already used for "root" item.
     */
    // @codingStandardsIgnoreEnd
    public function testAddAliasRedefine()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');
        $this->rawLayout->addAlias('test', 'root');

        // do test
        $this->rawLayout->addAlias('test', 'header');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The "root" sting cannot be used as an alias for "root" item because an alias cannot be equal to the item id.
     */
    // @codingStandardsIgnoreEnd
    public function testAddAliasWhenAliasEqualsId()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');

        // do test
        $this->rawLayout->addAlias('root', 'root');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The "header" sting cannot be used as an alias for "root" item because another item with the same id exists.
     */
    // @codingStandardsIgnoreEnd
    public function testAddAliasWhenAliasEqualsIdOfAnotherItem()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');

        // do test
        $this->rawLayout->addAlias('header', 'root');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "root" item does not exist.
     */
    public function testAddAliasForUnknownItem()
    {
        $this->rawLayout->addAlias('header', 'root');
    }

    public function testRemoveAlias()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');
        $this->rawLayout->addAlias('test_header', 'header');

        // do test
        $this->rawLayout->removeAlias('test_header');
        $this->assertFalse($this->rawLayout->hasAlias('test_header'));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\AliasNotFoundException
     * @expectedExceptionMessage The "unknown" item alias does not exist.
     */
    public function testRemoveUnknownAlias()
    {
        $this->rawLayout->removeAlias('unknown');
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item alias must not be empty.
     */
    public function testRemoveAliasWithEmptyAlias($alias)
    {
        $this->rawLayout->removeAlias($alias);
    }

    public function testGetAliases()
    {
        $this->rawLayout->add('test_id', null, 'root');
        $this->rawLayout->addAlias('test_alias', 'test_id');
        $this->rawLayout->addAlias('another_alias', 'test_alias');
        $this->assertEquals(
            ['test_alias', 'another_alias'],
            $this->rawLayout->getAliases('test_id')
        );
        $this->assertEquals(
            [],
            $this->rawLayout->getAliases('unknown')
        );
    }

    public function testSetBlockTheme()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');

        // do test
        $this->rawLayout->setBlockTheme(
            'root',
            ['MyBundle:Layout:theme1.html.twig', 'MyBundle:Layout:theme2.html.twig']
        );
        $this->rawLayout->setBlockTheme(
            'root',
            'MyBundle:Layout:theme3.html.twig'
        );
        $this->rawLayout->setBlockTheme(
            'header',
            'MyBundle:Layout:header_theme1.html.twig'
        );
        $this->rawLayout->setBlockTheme(
            'header',
            ['MyBundle:Layout:header_theme2.html.twig', 'MyBundle:Layout:header_theme3.html.twig']
        );

        $blockThemes = $this->rawLayout->getBlockThemes();
        $this->assertSame(
            [
                'root'   => [
                    'MyBundle:Layout:theme1.html.twig',
                    'MyBundle:Layout:theme2.html.twig',
                    'MyBundle:Layout:theme3.html.twig'
                ],
                'header' => [
                    'MyBundle:Layout:header_theme1.html.twig',
                    'MyBundle:Layout:header_theme2.html.twig',
                    'MyBundle:Layout:header_theme3.html.twig'
                ]
            ],
            $blockThemes
        );
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" item does not exist.
     */
    public function testSetBlockThemeForUnknownItem()
    {
        $this->rawLayout->setBlockTheme('unknown', 'MyBundle:Layout:theme1.html.twig');
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testSetBlockThemeWithEmptyId($id)
    {
        $this->rawLayout->setBlockTheme($id, 'MyBundle:Layout:theme1.html.twig');
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The theme must not be empty.
     */
    public function testSetBlockThemeWithEmptyTheme($theme)
    {
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->setBlockTheme('root', $theme);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The theme must not be empty.
     */
    public function testSetBlockThemeWithEmptyThemes()
    {
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->setBlockTheme('root', []);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid "themes" argument type. Expected "string or array of strings", "integer" given.
     */
    public function testSetBlockThemeWithInvalidThemeType()
    {
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->setBlockTheme('root', 123);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The theme must not be empty.
     */
    public function testSetFormThemeWithEmptyThemes()
    {
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->setFormTheme([]);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid "themes" argument type. Expected "string or array of strings", "integer" given.
     */
    public function testSetFormThemeWithInvalidThemeType()
    {
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->setFormTheme(123);
    }

    public function testSetFormTheme()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');

        // do test
        $this->rawLayout->setFormTheme(
            ['MyBundle:Layout:theme1.html.twig', 'MyBundle:Layout:theme2.html.twig']
        );
        $this->rawLayout->setFormTheme(
            'MyBundle:Layout:theme3.html.twig'
        );

        $formThemes = $this->rawLayout->getFormThemes();
        $this->assertSame(
            [
                'MyBundle:Layout:theme1.html.twig',
                'MyBundle:Layout:theme2.html.twig',
                'MyBundle:Layout:theme3.html.twig'
            ],
            $formThemes
        );
    }

    public function testGetHierarchy()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');
        $this->rawLayout->add('item1', 'root', 'label');
        $this->rawLayout->add('item2', 'header', 'label');

        // do test
        $this->assertEquals(
            [
                'header' => [
                    'item2' => []
                ],
                'item1'  => []
            ],
            $this->rawLayout->getHierarchy('root')
        );
        $this->assertEquals(
            [
                'item2' => []
            ],
            $this->rawLayout->getHierarchy('header')
        );
        $this->assertEquals(
            [],
            $this->rawLayout->getHierarchy('item2')
        );
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" item does not exist.
     */
    public function testGetHierarchyForUnknownItem()
    {
        $this->rawLayout->getHierarchy('unknown');
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testGetHierarchyWithEmptyId($id)
    {
        $this->rawLayout->getHierarchy($id);
    }

    public function testGetHierarchyIterator()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');
        $this->rawLayout->add('item1', 'root', 'label');
        $this->rawLayout->add('item2', 'header', 'label');

        // do test
        $this->assertSame(
            [
                'header' => 'header',
                'item2'  => 'item2',
                'item1'  => 'item1',
            ],
            iterator_to_array($this->rawLayout->getHierarchyIterator('root'))
        );
        $this->assertSame(
            [
                'item2' => 'item2',
            ],
            iterator_to_array($this->rawLayout->getHierarchyIterator('header'))
        );
        $this->assertSame(
            [],
            iterator_to_array($this->rawLayout->getHierarchyIterator('item2'))
        );
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" item does not exist.
     */
    public function testGetHierarchyIteratorForUnknownItem()
    {
        $this->rawLayout->getHierarchyIterator('unknown');
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testGetHierarchyIteratorWithEmptyId($id)
    {
        $this->rawLayout->getHierarchyIterator($id);
    }

    public function emptyStringDataProvider()
    {
        return [
            [null],
            ['']
        ];
    }

    public function invalidIdDataProvider()
    {
        return [
            ['-test'],
            ['_test'],
            ['1test'],
            ['?test'],
            ['test?'],
            ['\ntest'],
            ['test\n']
        ];
    }
}
