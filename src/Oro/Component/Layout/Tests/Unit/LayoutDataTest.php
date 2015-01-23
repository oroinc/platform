<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\LayoutData;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class LayoutDataTest extends \PHPUnit_Framework_TestCase
{
    /** @var LayoutData */
    protected $layoutData;

    protected function setUp()
    {
        $this->layoutData = new LayoutData();
    }

    public function testGetRootItemId()
    {
        // prepare test data
        $this->layoutData->addItem('root', null, 'root');
        $this->layoutData->addItem('header', 'root', 'header');

        // do test
        $this->assertEquals('root', $this->layoutData->getRootItemId());
    }

    public function testResolveItemId()
    {
        // prepare test data
        $this->layoutData->addItem('root', null, 'root');
        $this->layoutData->addItem('header', 'root', 'header');
        $this->layoutData->addItemAlias('test_header', 'header');
        $this->layoutData->addItemAlias('another_header', 'test_header');

        // do test
        $this->assertEquals('header', $this->layoutData->resolveItemId('header'));
        $this->assertEquals('header', $this->layoutData->resolveItemId('test_header'));
        $this->assertEquals('header', $this->layoutData->resolveItemId('another_header'));
        $this->assertEquals('unknown', $this->layoutData->resolveItemId('unknown'));
    }

    public function testHasItem()
    {
        // prepare test data
        $this->layoutData->addItem('root', null, 'root');
        $this->layoutData->addItem('header', 'root', 'header');
        $this->layoutData->addItemAlias('test_header', 'header');
        $this->layoutData->addItemAlias('another_header', 'test_header');

        // do test
        $this->assertTrue($this->layoutData->hasItem('header'));
        $this->assertTrue($this->layoutData->hasItem('test_header'));
        $this->assertTrue($this->layoutData->hasItem('another_header'));
        $this->assertFalse($this->layoutData->hasItem('unknown'));
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testAddItemWithEmptyId($id)
    {
        $this->layoutData->addItem($id, null, 'root');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid "id" argument type. Expected "string", "integer" given.
     */
    public function testAddItemWithNotStringId()
    {
        $this->layoutData->addItem(123, null, 'root');
    }

    /**
     * @dataProvider invalidIdDataProvider
     */
    public function testAddItemWithInvalidId($id)
    {
        $this->setExpectedException(
            '\Oro\Component\Layout\Exception\InvalidArgumentException',
            sprintf(
                'The "%s" string cannot be used as the item id because it contains illegal characters. '
                . 'The valid item id should start with a letter, digit or underscore and only contain '
                . 'letters, digits, numbers, underscores ("_"), hyphens ("-") and colons (":").',
                $id
            )
        );
        $this->layoutData->addItem($id, null, 'root');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemAlreadyExistsException
     * @expectedExceptionMessage The "root" item already exists. Remove existing item before add the new item with the same id.
     */
    // @codingStandardsIgnoreEnd
    public function testAddItemDuplicate()
    {
        $this->layoutData->addItem('root', null, 'root');
        $this->layoutData->addItem('root', null, 'root');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The "another_root" item cannot be the root item because another root item ("root") already exists.
     */
    // @codingStandardsIgnoreEnd
    public function testAddItemRedefineRootItem()
    {
        $this->layoutData->addItem('root', null, 'root');
        $this->layoutData->addItem('another_root', null, 'root');
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The block type for "root" item must not be empty.
     */
    public function testAddItemWithEmptyBlockType($blockType)
    {
        $this->layoutData->addItem('root', null, $blockType);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid "blockType" argument type. Expected "string", "integer" given.
     */
    public function testAddItemWithNotStringBlockType()
    {
        $this->layoutData->addItem('root', null, 123);
    }

    /**
     * @dataProvider invalidBlockTypeDataProvider
     */
    public function testAddItemWithInvalidBlockType($blockType)
    {
        $this->setExpectedException(
            '\Oro\Component\Layout\Exception\InvalidArgumentException',
            sprintf(
                'The "%s" string cannot be used as the name of the block type '
                . 'because it contains illegal characters. '
                . 'The valid block type name must only contain letters, numbers, and "_".',
                $blockType
            )
        );
        $this->layoutData->addItem('root', null, $blockType);
    }

    public function testRemoveItem()
    {
        // prepare test data
        $this->layoutData->addItem('root', null, 'root');
        $this->layoutData->addItem('header', 'root', 'header');
        $this->layoutData->addItem('item1', 'header', 'label');
        $this->layoutData->addItem('item2', 'header', 'container');
        $this->layoutData->addItem('item3', 'item2', 'label');

        // do test
        $this->layoutData->removeItem('header');
        $this->assertFalse($this->layoutData->hasItem('header'));
        $this->assertFalse($this->layoutData->hasItem('item1'));
        $this->assertFalse($this->layoutData->hasItem('item2'));
        $this->assertFalse($this->layoutData->hasItem('item3'));
    }

    public function testRemoveItemByAlias()
    {
        // prepare test data
        $this->layoutData->addItem('root', null, 'root');
        $this->layoutData->addItem('header', 'root', 'header');
        $this->layoutData->addItemAlias('test_header', 'header');

        // do test
        $this->layoutData->removeItem('test_header');
        $this->assertFalse($this->layoutData->hasItem('header'));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" item does not exist.
     */
    public function testRemoveUnknownItem()
    {
        $this->layoutData->removeItem('unknown');
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testRemoveItemWithEmptyId($id)
    {
        $this->layoutData->removeItem($id);
    }

    public function testHasItemProperty()
    {
        // prepare test data
        $this->layoutData->addItem('root', null, 'root');
        $this->layoutData->addItem('header', 'root', 'header');

        // do test
        $this->assertTrue($this->layoutData->hasItemProperty('header', LayoutData::PATH));
        $this->assertFalse($this->layoutData->hasItemProperty('header', 'unknown'));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" item does not exist.
     */
    public function testHasItemPropertyForUnknownItem()
    {
        $this->layoutData->hasItemProperty('unknown', LayoutData::PATH);
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testHasItemPropertyWithEmptyId($id)
    {
        $this->layoutData->hasItemProperty($id, LayoutData::PATH);
    }

    public function testGetItemProperty()
    {
        // prepare test data
        $this->layoutData->addItem('root', null, 'root');
        $this->layoutData->addItem('header', 'root', 'header');

        // do test
        $this->assertEquals(['root', 'header'], $this->layoutData->getItemProperty('header', LayoutData::PATH));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The "header" item does not have "unknown" property.
     */
    public function testGetItemPropertyForUnknownProperty()
    {
        // prepare test data
        $this->layoutData->addItem('root', null, 'root');
        $this->layoutData->addItem('header', 'root', 'header');

        // do test
        $this->layoutData->getItemProperty('header', 'unknown');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" item does not exist.
     */
    public function testGetItemPropertyForUnknownItem()
    {
        $this->layoutData->getItemProperty('unknown', LayoutData::PATH);
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testGetItemPropertyWithEmptyId($id)
    {
        $this->layoutData->getItemProperty($id, LayoutData::PATH);
    }

    public function testSetItemProperty()
    {
        // prepare test data
        $this->layoutData->addItem('root', null, 'root');
        $this->layoutData->addItem('header', 'root', 'header');

        // do test
        $this->layoutData->setItemProperty('header', 'some_property', 123);
        $this->assertEquals(123, $this->layoutData->getItemProperty('header', 'some_property'));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" item does not exist.
     */
    public function testSetItemPropertyForUnknownItem()
    {
        $this->layoutData->setItemProperty('unknown', 'some_property', 123);
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testSetItemPropertyWithEmptyId($id)
    {
        $this->layoutData->setItemProperty($id, 'some_property', 123);
    }

    public function testHasItemAlias()
    {
        // prepare test data
        $this->layoutData->addItem('root', null, 'root');
        $this->layoutData->addItem('header', 'root', 'header');
        $this->layoutData->addItemAlias('test_header', 'header');

        // do test
        $this->assertTrue($this->layoutData->hasItemAlias('test_header'));
        $this->assertFalse($this->layoutData->hasItemAlias('header'));
        $this->assertFalse($this->layoutData->hasItemAlias('unknown'));
    }

    public function testAddItemAlias()
    {
        // prepare test data
        $this->layoutData->addItem('root', null, 'root');
        $this->layoutData->addItem('header', 'root', 'header');
        $this->layoutData->addItemAlias('test_header', 'header');

        // do test
        $this->assertTrue($this->layoutData->hasItemAlias('test_header'));
        $this->assertEquals('header', $this->layoutData->resolveItemId('test_header'));
    }

    public function testAddItemAliasWhenAliasIsAddedForAnotherAlias()
    {
        // prepare test data
        $this->layoutData->addItem('root', null, 'root');
        $this->layoutData->addItem('header', 'root', 'header');
        $this->layoutData->addItemAlias('test_header', 'header');

        // do test
        $this->layoutData->addItemAlias('another_header', 'test_header');
        $this->assertTrue($this->layoutData->hasItemAlias('another_header'));
        $this->assertEquals('header', $this->layoutData->resolveItemId('another_header'));
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item alias must not be empty.
     */
    public function testAddItemAliasWithEmptyAlias($alias)
    {
        $this->layoutData->addItemAlias($alias, 'root');
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testAddItemAliasWithEmptyId($id)
    {
        $this->layoutData->addItemAlias('test', $id);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid "alias" argument type. Expected "string", "integer" given.
     */
    public function testAddItemAliasWithNotStringAlias()
    {
        $this->layoutData->addItemAlias(123, 'root');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid "id" argument type. Expected "string", "integer" given.
     */
    public function testAddItemAliasWithNotStringId()
    {
        $this->layoutData->addItemAlias('test', 123);
    }

    /**
     * @dataProvider invalidIdDataProvider
     */
    public function testAddItemAliasWithInvalidAlias($alias)
    {
        $this->setExpectedException(
            '\Oro\Component\Layout\Exception\InvalidArgumentException',
            sprintf(
                'The "%s" string cannot be used as the item alias because it contains illegal characters. '
                . 'The valid alias should start with a letter, digit or underscore and only contain '
                . 'letters, digits, numbers, underscores ("_"), hyphens ("-") and colons (":").',
                $alias
            )
        );
        $this->layoutData->addItemAlias($alias, 'root');
    }

    /**
     * @dataProvider invalidIdDataProvider
     */
    public function testAddItemAliasWithInvalidId($id)
    {
        $this->setExpectedException(
            '\Oro\Component\Layout\Exception\InvalidArgumentException',
            sprintf(
                'The "%s" string cannot be used as the item id because it contains illegal characters. '
                . 'The valid item id should start with a letter, digit or underscore and only contain '
                . 'letters, digits, numbers, underscores ("_"), hyphens ("-") and colons (":").',
                $id
            )
        );
        $this->layoutData->addItemAlias('test', $id);
    }

    public function testAddItemAliasDuplicate()
    {
        // prepare test data
        $this->layoutData->addItem('root', null, 'root');
        $this->layoutData->addItemAlias('test', 'root');

        // do test
        $this->layoutData->addItemAlias('test', 'root');
        $this->assertTrue($this->layoutData->hasItemAlias('test'));
        $this->assertEquals('root', $this->layoutData->resolveItemId('test'));
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\AliasAlreadyExistsException
     * @expectedExceptionMessage The "test" sting cannot be used as an alias for "header" item because it is already used for "root" item.
     */
    // @codingStandardsIgnoreEnd
    public function testAddItemAliasRedefine()
    {
        // prepare test data
        $this->layoutData->addItem('root', null, 'root');
        $this->layoutData->addItem('header', 'root', 'header');
        $this->layoutData->addItemAlias('test', 'root');

        // do test
        $this->layoutData->addItemAlias('test', 'header');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The "root" sting cannot be used as an alias for "root" item because an alias cannot be equal to the item id.
     */
    // @codingStandardsIgnoreEnd
    public function testAddItemAliasWhenAliasEqualsId()
    {
        // prepare test data
        $this->layoutData->addItem('root', null, 'root');

        // do test
        $this->layoutData->addItemAlias('root', 'root');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The "header" sting cannot be used as an alias for "root" item because another item with the same id exists.
     */
    // @codingStandardsIgnoreEnd
    public function testAddItemAliasWhenAliasEqualsIdOfAnotherItem()
    {
        // prepare test data
        $this->layoutData->addItem('root', null, 'root');
        $this->layoutData->addItem('header', 'root', 'header');

        // do test
        $this->layoutData->addItemAlias('header', 'root');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "root" item does not exist.
     */
    public function testAddItemAliasForUnknownItem()
    {
        $this->layoutData->addItemAlias('header', 'root');
    }

    public function testRemoveItemAlias()
    {
        // prepare test data
        $this->layoutData->addItem('root', null, 'root');
        $this->layoutData->addItem('header', 'root', 'header');
        $this->layoutData->addItemAlias('test_header', 'header');

        // do test
        $this->layoutData->removeItemAlias('test_header');
        $this->assertFalse($this->layoutData->hasItemAlias('test_header'));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\AliasNotFoundException
     * @expectedExceptionMessage The "unknown" item alias does not exist.
     */
    public function testRemoveUnknownAlias()
    {
        $this->layoutData->removeItemAlias('unknown');
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item alias must not be empty.
     */
    public function testRemoveItemAliasWithEmptyAlias($alias)
    {
        $this->layoutData->removeItemAlias($alias);
    }

    public function testGetHierarchy()
    {
        // prepare test data
        $this->layoutData->addItem('root', null, 'root');
        $this->layoutData->addItem('header', 'root', 'header');
        $this->layoutData->addItem('item1', 'root', 'label');
        $this->layoutData->addItem('item2', 'header', 'label');

        // do test
        $this->assertEquals(
            [
                'header' => [
                    'item2' => []
                ],
                'item1'  => []
            ],
            $this->layoutData->getHierarchy('root')
        );
        $this->assertEquals(
            [
                'item2' => []
            ],
            $this->layoutData->getHierarchy('header')
        );
        $this->assertEquals(
            [],
            $this->layoutData->getHierarchy('item2')
        );
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" item does not exist.
     */
    public function testGetHierarchyForUnknownItem()
    {
        $this->layoutData->getHierarchy('unknown');
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testGetHierarchyWithEmptyId($id)
    {
        $this->layoutData->getHierarchy($id);
    }

    public function testGetHierarchyIterator()
    {
        // prepare test data
        $this->layoutData->addItem('root', null, 'root');
        $this->layoutData->addItem('header', 'root', 'header');
        $this->layoutData->addItem('item1', 'root', 'label');
        $this->layoutData->addItem('item2', 'header', 'label');

        // do test
        $this->assertSame(
            [
                'header' => 'header',
                'item2'  => 'item2',
                'item1'  => 'item1',
            ],
            iterator_to_array($this->layoutData->getHierarchyIterator('root'))
        );
        $this->assertSame(
            [
                'item2' => 'item2',
            ],
            iterator_to_array($this->layoutData->getHierarchyIterator('header'))
        );
        $this->assertSame(
            [],
            iterator_to_array($this->layoutData->getHierarchyIterator('item2'))
        );
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" item does not exist.
     */
    public function testGetHierarchyIteratorForUnknownItem()
    {
        $this->layoutData->getHierarchyIterator('unknown');
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testGetHierarchyIteratorWithEmptyId($id)
    {
        $this->layoutData->getHierarchyIterator($id);
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
            ['test?']
        ];
    }

    public function invalidBlockTypeDataProvider()
    {
        return [
            ['test-block'],
            ['test?']
        ];
    }
}
