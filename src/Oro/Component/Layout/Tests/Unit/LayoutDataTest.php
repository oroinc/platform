<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\Type\ContainerType;
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

    public function testIsEmpty()
    {
        $this->assertTrue($this->layoutData->isEmpty());

        $this->layoutData->add('root', null, 'root');
        $this->assertFalse($this->layoutData->isEmpty());
    }

    public function testClear()
    {
        $this->layoutData->add('root', null, 'root');

        $this->layoutData->clear();
        $this->assertTrue($this->layoutData->isEmpty());
    }

    public function testGetRootId()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header', 'root', 'header');

        // do test
        $this->assertEquals('root', $this->layoutData->getRootId());
    }

    public function testResolveId()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header', 'root', 'header');
        $this->layoutData->addAlias('test_header', 'header');
        $this->layoutData->addAlias('another_header', 'test_header');

        // do test
        $this->assertEquals('header', $this->layoutData->resolveId('header'));
        $this->assertEquals('header', $this->layoutData->resolveId('test_header'));
        $this->assertEquals('header', $this->layoutData->resolveId('another_header'));
        $this->assertEquals('unknown', $this->layoutData->resolveId('unknown'));
    }

    public function testHas()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header', 'root', 'header');
        $this->layoutData->addAlias('test_header', 'header');
        $this->layoutData->addAlias('another_header', 'test_header');

        // do test
        $this->assertTrue($this->layoutData->has('header'));
        $this->assertTrue($this->layoutData->has('test_header'));
        $this->assertTrue($this->layoutData->has('another_header'));
        $this->assertFalse($this->layoutData->has('unknown'));
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testAddWithEmptyId($id)
    {
        $this->layoutData->add($id, null, 'root');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid "id" argument type. Expected "string", "integer" given.
     */
    public function testAddWithNotStringId()
    {
        $this->layoutData->add(123, null, 'root');
    }

    /**
     * @dataProvider invalidIdDataProvider
     */
    public function testAddWithInvalidId($id)
    {
        $this->setExpectedException(
            '\Oro\Component\Layout\Exception\InvalidArgumentException',
            sprintf(
                'The "%s" string cannot be used as the item id because it contains illegal characters. '
                . 'The valid item id should start with a letter and only contain '
                . 'letters, numbers, underscores ("_"), hyphens ("-") and colons (":").',
                $id
            )
        );
        $this->layoutData->add($id, null, 'root');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemAlreadyExistsException
     * @expectedExceptionMessage The "root" item already exists. Remove existing item before add the new item with the same id.
     */
    // @codingStandardsIgnoreEnd
    public function testAddDuplicate()
    {
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('root', null, 'root');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The "another_root" item cannot be the root item because another root item ("root") already exists.
     */
    // @codingStandardsIgnoreEnd
    public function testRedefineRoot()
    {
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('another_root', null, 'root');
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The block type name must not be empty.
     */
    public function testAddWithEmptyBlockType($blockType)
    {
        $this->layoutData->add('root', null, $blockType);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid "blockType" argument type. Expected "string", "integer" given.
     */
    public function testAddWithNotStringBlockType()
    {
        $this->layoutData->add('root', null, 123);
    }

    /**
     * @dataProvider invalidBlockTypeDataProvider
     */
    public function testAddWithInvalidBlockType($blockType)
    {
        $this->setExpectedException(
            '\Oro\Component\Layout\Exception\InvalidArgumentException',
            sprintf(
                'The "%s" string cannot be used as the name of the block type '
                . 'because it contains illegal characters. '
                . 'The valid block type name should start with a letter and only contain '
                . 'letters, numbers and underscores ("_").',
                $blockType
            )
        );
        $this->layoutData->add('root', null, $blockType);
    }

    public function testRemove()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header', 'root', 'header');
        $this->layoutData->add('item1', 'header', 'label');
        $this->layoutData->add('item2', 'header', ContainerType::NAME);
        $this->layoutData->add('item3', 'item2', 'label');

        // do test
        $this->layoutData->remove('header');
        $this->assertFalse($this->layoutData->has('header'));
        $this->assertFalse($this->layoutData->has('item1'));
        $this->assertFalse($this->layoutData->has('item2'));
        $this->assertFalse($this->layoutData->has('item3'));
    }

    public function testRemoveByAlias()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header', 'root', 'header');
        $this->layoutData->addAlias('test_header', 'header');

        // do test
        $this->layoutData->remove('test_header');
        $this->assertFalse($this->layoutData->has('header'));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" item does not exist.
     */
    public function testRemoveUnknown()
    {
        $this->layoutData->remove('unknown');
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testRemoveWithEmptyId($id)
    {
        $this->layoutData->remove($id);
    }

    public function testMoveToParent()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header1', 'root', 'header');
        $this->layoutData->add('container1', 'header1', ContainerType::NAME);
        $this->layoutData->add('item1', 'container1', 'label');
        $this->layoutData->add('header2', 'root', 'header');
        $this->layoutData->add('container2', 'header2', ContainerType::NAME);
        $this->layoutData->add('item2', 'container2', 'label');

        // do test
        $this->layoutData->move('container1', 'root');
        $this->assertSame(
            ['root', 'container1'],
            $this->layoutData->getProperty('container1', LayoutData::PATH)
        );
        $this->assertSame(
            ['root', 'container1', 'item1'],
            $this->layoutData->getProperty('item1', LayoutData::PATH)
        );
    }

    public function testMoveToParentByAlias()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header1', 'root', 'header');
        $this->layoutData->add('container1', 'header1', ContainerType::NAME);
        $this->layoutData->add('item1', 'container1', 'label');
        $this->layoutData->add('header2', 'root', 'header');
        $this->layoutData->add('container2', 'header2', ContainerType::NAME);
        $this->layoutData->add('item2', 'container2', 'label');
        $this->layoutData->addAlias('test_root', 'root');
        $this->layoutData->addAlias('test_container1', 'container1');

        // do test
        $this->layoutData->move('test_container1', 'test_root');
        $this->assertSame(
            ['root', 'container1'],
            $this->layoutData->getProperty('container1', LayoutData::PATH)
        );
        $this->assertSame(
            ['root', 'container1', 'item1'],
            $this->layoutData->getProperty('item1', LayoutData::PATH)
        );
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" item does not exist.
     */
    public function testMoveUnknown()
    {
        $this->layoutData->move('unknown');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" parent item does not exist.
     */
    public function testMoveToUnknownParent()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header1', 'root', 'header');
        $this->layoutData->add('item1', 'header1', 'label');
        $this->layoutData->add('header2', 'root', 'header');
        $this->layoutData->add('item2', 'header2', 'label');

        // do test
        $this->layoutData->move('item1', 'unknown', 'item2');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" sibling item does not exist.
     */
    public function testMoveToUnknownSibling()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header1', 'root', 'header');
        $this->layoutData->add('item1', 'header1', 'label');
        $this->layoutData->add('header2', 'root', 'header');
        $this->layoutData->add('item2', 'header2', 'label');

        // do test
        $this->layoutData->move('item1', 'header2', 'unknown');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The parent item cannot be the same as the moving item.
     */
    public function testMoveWhenParentEqualsToMovingItem()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header1', 'root', 'header');
        $this->layoutData->add('item1', 'header1', 'label');
        $this->layoutData->add('header2', 'root', 'header');
        $this->layoutData->add('item2', 'header2', 'label');
        $this->layoutData->addAlias('test_item', 'item1');

        // do test
        $this->layoutData->move('item1', 'test_item', 'item2');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The sibling item cannot be the same as the moving item.
     */
    public function testMoveWhenSiblingEqualsToMovingItem()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header1', 'root', 'header');
        $this->layoutData->add('item1', 'header1', 'label');
        $this->layoutData->add('header2', 'root', 'header');
        $this->layoutData->add('item2', 'header2', 'label');
        $this->layoutData->addAlias('test_item', 'item1');

        // do test
        $this->layoutData->move('item1', 'header2', 'test_item');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The sibling item cannot be the same as the parent item.
     */
    public function testMoveWhenParentEqualsToSibling()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header1', 'root', 'header');
        $this->layoutData->add('item1', 'header1', 'label');
        $this->layoutData->add('header2', 'root', 'header');
        $this->layoutData->add('item2', 'header2', 'label');
        $this->layoutData->addAlias('test_header', 'header2');
        $this->layoutData->addAlias('test_item', 'test_header');

        // do test
        $this->layoutData->move('item1', 'test_header', 'test_item');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage At least one parent or sibling item must be specified.
     */
    public function testMoveWithoutParentAndSibling()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header1', 'root', 'header');
        $this->layoutData->add('item1', 'header1', 'label');

        // do test
        $this->layoutData->move('item1');
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
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header1', 'root', 'header');
        $this->layoutData->add('item1', 'header1', 'label');
        $this->layoutData->addAlias('test_header', 'header1');
        $this->layoutData->addAlias('test_item', 'item1');

        // do test
        $this->layoutData->move('test_header', 'test_item');
    }

    public function testMoveInsideTheSameParent()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header1', 'root', 'header');
        $this->layoutData->add('item1', 'header1', 'label');
        $this->layoutData->add('item2', 'header1', 'label');
        $this->layoutData->addAlias('test_item', 'item1');

        // do test
        $this->layoutData->move('test_item', null, 'item2');
        $this->assertSame(
            ['root', 'header1', 'item1'],
            $this->layoutData->getProperty('item1', LayoutData::PATH)
        );
        $this->assertSame(
            ['item2' => [], 'item1' => []],
            $this->layoutData->getHierarchy('header1')
        );
    }

    public function testMoveInsideTheSameParentAndWithParentIdSpecified()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header1', 'root', 'header');
        $this->layoutData->add('item1', 'header1', 'label');
        $this->layoutData->add('item2', 'header1', 'label');
        $this->layoutData->addAlias('test_header', 'header1');
        $this->layoutData->addAlias('test_item', 'item1');

        // do test
        $this->layoutData->move('test_item', 'test_header', 'item2');
        $this->assertSame(
            ['root', 'header1', 'item1'],
            $this->layoutData->getProperty('item1', LayoutData::PATH)
        );
        $this->assertSame(
            ['item2' => [], 'item1' => []],
            $this->layoutData->getHierarchy('header1')
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
        $this->layoutData->move($id);
    }

    public function testHasProperty()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header', 'root', 'header');

        // do test
        $this->assertTrue($this->layoutData->hasProperty('header', LayoutData::PATH));
        $this->assertFalse($this->layoutData->hasProperty('header', 'unknown'));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" item does not exist.
     */
    public function testHasPropertyForUnknownItem()
    {
        $this->layoutData->hasProperty('unknown', LayoutData::PATH);
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testHasPropertyWithEmptyId($id)
    {
        $this->layoutData->hasProperty($id, LayoutData::PATH);
    }

    public function testGetProperty()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header', 'root', 'header');

        // do test
        $this->assertEquals(['root', 'header'], $this->layoutData->getProperty('header', LayoutData::PATH));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The "header" item does not have "unknown" property.
     */
    public function testGetPropertyForUnknownProperty()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header', 'root', 'header');

        // do test
        $this->layoutData->getProperty('header', 'unknown');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" item does not exist.
     */
    public function testGetPropertyForUnknownItem()
    {
        $this->layoutData->getProperty('unknown', LayoutData::PATH);
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testGetPropertyWithEmptyId($id)
    {
        $this->layoutData->getProperty($id, LayoutData::PATH);
    }

    public function testSetProperty()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header', 'root', 'header');

        // do test
        $this->layoutData->setProperty('header', 'some_property', 123);
        $this->assertEquals(123, $this->layoutData->getProperty('header', 'some_property'));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "unknown" item does not exist.
     */
    public function testSetPropertyForUnknownItem()
    {
        $this->layoutData->setProperty('unknown', 'some_property', 123);
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testSetPropertyWithEmptyId($id)
    {
        $this->layoutData->setProperty($id, 'some_property', 123);
    }

    public function testHasAlias()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header', 'root', 'header');
        $this->layoutData->addAlias('test_header', 'header');

        // do test
        $this->assertTrue($this->layoutData->hasAlias('test_header'));
        $this->assertFalse($this->layoutData->hasAlias('header'));
        $this->assertFalse($this->layoutData->hasAlias('unknown'));
    }

    public function testAddAlias()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header', 'root', 'header');
        $this->layoutData->addAlias('test_header', 'header');

        // do test
        $this->assertTrue($this->layoutData->hasAlias('test_header'));
        $this->assertEquals('header', $this->layoutData->resolveId('test_header'));
    }

    public function testAddAliasWhenAliasIsAddedForAnotherAlias()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header', 'root', 'header');
        $this->layoutData->addAlias('test_header', 'header');

        // do test
        $this->layoutData->addAlias('another_header', 'test_header');
        $this->assertTrue($this->layoutData->hasAlias('another_header'));
        $this->assertEquals('header', $this->layoutData->resolveId('another_header'));
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item alias must not be empty.
     */
    public function testAddAliasWithEmptyAlias($alias)
    {
        $this->layoutData->addAlias($alias, 'root');
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testAddAliasWithEmptyId($id)
    {
        $this->layoutData->addAlias('test', $id);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid "alias" argument type. Expected "string", "integer" given.
     */
    public function testAddAliasWithNotStringAlias()
    {
        $this->layoutData->addAlias(123, 'root');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Invalid "id" argument type. Expected "string", "integer" given.
     */
    public function testAddAliasWithNotStringId()
    {
        $this->layoutData->addAlias('test', 123);
    }

    /**
     * @dataProvider invalidIdDataProvider
     */
    public function testAddAliasWithInvalidAlias($alias)
    {
        $this->setExpectedException(
            '\Oro\Component\Layout\Exception\InvalidArgumentException',
            sprintf(
                'The "%s" string cannot be used as the item alias because it contains illegal characters. '
                . 'The valid alias should start with a letter and only contain '
                . 'letters, numbers, underscores ("_"), hyphens ("-") and colons (":").',
                $alias
            )
        );
        $this->layoutData->addAlias($alias, 'root');
    }

    /**
     * @dataProvider invalidIdDataProvider
     */
    public function testAddAliasWithInvalidId($id)
    {
        $this->setExpectedException(
            '\Oro\Component\Layout\Exception\InvalidArgumentException',
            sprintf(
                'The "%s" string cannot be used as the item id because it contains illegal characters. '
                . 'The valid item id should start with a letter and only contain '
                . 'letters, numbers, underscores ("_"), hyphens ("-") and colons (":").',
                $id
            )
        );
        $this->layoutData->addAlias('test', $id);
    }

    public function testAddAliasDuplicate()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->addAlias('test', 'root');

        // do test
        $this->layoutData->addAlias('test', 'root');
        $this->assertTrue($this->layoutData->hasAlias('test'));
        $this->assertEquals('root', $this->layoutData->resolveId('test'));
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
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header', 'root', 'header');
        $this->layoutData->addAlias('test', 'root');

        // do test
        $this->layoutData->addAlias('test', 'header');
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
        $this->layoutData->add('root', null, 'root');

        // do test
        $this->layoutData->addAlias('root', 'root');
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
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header', 'root', 'header');

        // do test
        $this->layoutData->addAlias('header', 'root');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\ItemNotFoundException
     * @expectedExceptionMessage The "root" item does not exist.
     */
    public function testAddAliasForUnknownItem()
    {
        $this->layoutData->addAlias('header', 'root');
    }

    public function testRemoveAlias()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header', 'root', 'header');
        $this->layoutData->addAlias('test_header', 'header');

        // do test
        $this->layoutData->removeAlias('test_header');
        $this->assertFalse($this->layoutData->hasAlias('test_header'));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\AliasNotFoundException
     * @expectedExceptionMessage The "unknown" item alias does not exist.
     */
    public function testRemoveUnknownAlias()
    {
        $this->layoutData->removeAlias('unknown');
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item alias must not be empty.
     */
    public function testRemoveAliasWithEmptyAlias($alias)
    {
        $this->layoutData->removeAlias($alias);
    }

    public function testGetHierarchy()
    {
        // prepare test data
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header', 'root', 'header');
        $this->layoutData->add('item1', 'root', 'label');
        $this->layoutData->add('item2', 'header', 'label');

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
        $this->layoutData->add('root', null, 'root');
        $this->layoutData->add('header', 'root', 'header');
        $this->layoutData->add('item1', 'root', 'label');
        $this->layoutData->add('item2', 'header', 'label');

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
            ['_test'],
            ['1test'],
            ['?test'],
            ['test?'],
            ['\ntest'],
            ['test\n'],
        ];
    }

    public function invalidBlockTypeDataProvider()
    {
        return [
            ['-test'],
            ['_test'],
            ['1test'],
            ['?test'],
            ['test?'],
            ['\ntest'],
            ['test\n'],
            ['test-block'],
            ['test:block'],
        ];
    }
}
