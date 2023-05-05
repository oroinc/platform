<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\Exception\AliasAlreadyExistsException;
use Oro\Component\Layout\Exception\AliasNotFoundException;
use Oro\Component\Layout\Exception\InvalidArgumentException;
use Oro\Component\Layout\Exception\ItemAlreadyExistsException;
use Oro\Component\Layout\Exception\ItemNotFoundException;
use Oro\Component\Layout\Exception\LogicException;
use Oro\Component\Layout\Exception\UnexpectedTypeException;
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
    private RawLayout $rawLayout;

    protected function setUp(): void
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

    public function testGetParentIdForUnknownItem()
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The "unknown" item does not exist.');

        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');

        // do test
        $this->rawLayout->getParentId('unknown');
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testGetParentIdWithEmptyId(?string $id)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item id must not be empty.');

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
     * @dataProvider emptyStringDataProvider
     */
    public function testAddWithEmptyId(?string $id)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item id must not be empty.');

        $this->rawLayout->add($id, null, 'root');
    }

    public function testAddWithNotStringId()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Invalid "id" argument type. Expected "string", "integer" given.');

        $this->rawLayout->add(123, null, 'root');
    }

    /**
     * @dataProvider invalidIdDataProvider
     */
    public function testAddWithInvalidId(string $id)
    {
        $this->expectException(InvalidArgumentException::class);
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

    public function testAddDuplicate()
    {
        $this->expectException(ItemAlreadyExistsException::class);
        $this->expectExceptionMessage(
            'The "root" item already exists. Remove existing item before add the new item with the same id.'
        );

        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('root', null, 'root');
    }

    public function testRedefineRoot()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'The "another_root" item cannot be the root item because another root item ("root") already exists.'
        );

        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('another_root', null, 'root');
    }

    public function testAddToUnknownSibling()
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The "unknown" sibling item does not exist.');

        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header1', 'root', 'header');
        $this->rawLayout->add('item1', 'header1', 'label');

        // do test
        $this->rawLayout->add('item2', 'header1', 'label', [], 'unknown');
    }

    public function testAddWhenParentEqualsToSibling()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The sibling item cannot be the same as the parent item.');

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

    public function testRemoveUnknown()
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The "unknown" item does not exist.');

        $this->rawLayout->remove('unknown');
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testRemoveWithEmptyId(?string $id)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item id must not be empty.');

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

    public function testMoveUnknown()
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The "unknown" item does not exist.');

        $this->rawLayout->move('unknown');
    }

    public function testMoveToUnknownParent()
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The "unknown" parent item does not exist.');

        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header1', 'root', 'header');
        $this->rawLayout->add('item1', 'header1', 'label');
        $this->rawLayout->add('header2', 'root', 'header');
        $this->rawLayout->add('item2', 'header2', 'label');

        // do test
        $this->rawLayout->move('item1', 'unknown', 'item2');
    }

    public function testMoveToUnknownSibling()
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The "unknown" sibling item does not exist.');

        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header1', 'root', 'header');
        $this->rawLayout->add('item1', 'header1', 'label');
        $this->rawLayout->add('header2', 'root', 'header');
        $this->rawLayout->add('item2', 'header2', 'label');

        // do test
        $this->rawLayout->move('item1', 'header2', 'unknown');
    }

    public function testMoveWhenParentEqualsToMovingItem()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The parent item cannot be the same as the moving item.');

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

    public function testMoveWhenSiblingEqualsToMovingItem()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The sibling item cannot be the same as the moving item.');

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

    public function testMoveWhenParentEqualsToSibling()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The sibling item cannot be the same as the parent item.');

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

    public function testMoveWithoutParentAndSibling()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('At least one parent or sibling item must be specified.');

        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header1', 'root', 'header');
        $this->rawLayout->add('item1', 'header1', 'label');

        // do test
        $this->rawLayout->move('item1');
    }

    public function testMoveParentToChild()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'The parent item (path: root/header1/item1) cannot be a child of the moving item (path: root/header1).'
        );

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
     * @dataProvider emptyStringDataProvider
     */
    public function testMoveWithEmptyId(?string $id)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item id must not be empty.');

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

    public function testHasPropertyForUnknownItem()
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The "unknown" item does not exist.');

        $this->rawLayout->hasProperty('unknown', RawLayout::PATH);
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testHasPropertyWithEmptyId(?string $id)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item id must not be empty.');

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

    public function testGetPropertyForUnknownProperty()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The "header" item does not have "unknown" property.');

        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');

        // do test
        $this->rawLayout->getProperty('header', 'unknown');
    }

    public function testGetPropertyForUnknownItem()
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The "unknown" item does not exist.');

        $this->rawLayout->getProperty('unknown', RawLayout::PATH);
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testGetPropertyWithEmptyId(?string $id)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item id must not be empty.');

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

    public function testSetPropertyForUnknownItem()
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The "unknown" item does not exist.');

        $this->rawLayout->setProperty('unknown', 'some_property', 123);
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testSetPropertyWithEmptyId(?string $id)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item id must not be empty.');

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
     * @dataProvider emptyStringDataProvider
     */
    public function testAddAliasWithEmptyAlias(?string $alias)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item alias must not be empty.');

        $this->rawLayout->addAlias($alias, 'root');
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testAddAliasWithEmptyId(?string $id)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item id must not be empty.');

        $this->rawLayout->addAlias('test', $id);
    }

    public function testAddAliasWithNotStringAlias()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Invalid "alias" argument type. Expected "string", "integer" given.');

        $this->rawLayout->addAlias(123, 'root');
    }

    public function testAddAliasWithNotStringId()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Invalid "id" argument type. Expected "string", "integer" given.');

        $this->rawLayout->addAlias('test', 123);
    }

    /**
     * @dataProvider invalidIdDataProvider
     */
    public function testAddAliasWithInvalidAlias(string $alias)
    {
        $this->expectException(InvalidArgumentException::class);
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
    public function testAddAliasWithInvalidId(string $id)
    {
        $this->expectException(InvalidArgumentException::class);
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

    public function testAddAliasRedefine()
    {
        $this->expectException(AliasAlreadyExistsException::class);
        $this->expectExceptionMessage(
            'The "test" string cannot be used as an alias for "header" item'
            . ' because it is already used for "root" item.'
        );

        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');
        $this->rawLayout->addAlias('test', 'root');

        // do test
        $this->rawLayout->addAlias('test', 'header');
    }

    public function testAddAliasWhenAliasEqualsId()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'The "root" string cannot be used as an alias for "root" item'
            . ' because an alias cannot be equal to the item id.'
        );

        // prepare test data
        $this->rawLayout->add('root', null, 'root');

        // do test
        $this->rawLayout->addAlias('root', 'root');
    }

    public function testAddAliasWhenAliasEqualsIdOfAnotherItem()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'The "header" string cannot be used as an alias for "root" item because'
            . ' another item with the same id exists.'
        );

        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');

        // do test
        $this->rawLayout->addAlias('header', 'root');
    }

    public function testAddAliasForUnknownItem()
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The "root" item does not exist.');

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

    public function testRemoveUnknownAlias()
    {
        $this->expectException(AliasNotFoundException::class);
        $this->expectExceptionMessage('The "unknown" item alias does not exist.');

        $this->rawLayout->removeAlias('unknown');
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testRemoveAliasWithEmptyAlias(?string $alias)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item alias must not be empty.');

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
            ['@My/Layout/theme1.html.twig', '@My/Layout/theme2.html.twig']
        );
        $this->rawLayout->setBlockTheme(
            'root',
            '@My/Layout/theme3.html.twig'
        );
        $this->rawLayout->setBlockTheme(
            'header',
            '@My/Layout/header_theme1.html.twig'
        );
        $this->rawLayout->setBlockTheme(
            'header',
            ['@My/Layout/header_theme2.html.twig', '@My/Layout/header_theme3.html.twig']
        );

        $blockThemes = $this->rawLayout->getBlockThemes();
        $this->assertSame(
            [
                'root'   => [
                    '@My/Layout/theme1.html.twig',
                    '@My/Layout/theme2.html.twig',
                    '@My/Layout/theme3.html.twig'
                ],
                'header' => [
                    '@My/Layout/header_theme1.html.twig',
                    '@My/Layout/header_theme2.html.twig',
                    '@My/Layout/header_theme3.html.twig'
                ]
            ],
            $blockThemes
        );
    }

    public function testSetBlockThemeForUnknownItem()
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The "unknown" item does not exist.');

        $this->rawLayout->setBlockTheme('unknown', '@My/Layout/theme1.html.twig');
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testSetBlockThemeWithEmptyId(?string $id)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item id must not be empty.');

        $this->rawLayout->setBlockTheme($id, '@My/Layout/theme1.html.twig');
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testSetBlockThemeWithEmptyTheme(?string $theme)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The theme must not be empty.');

        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->setBlockTheme('root', $theme);
    }

    public function testSetBlockThemeWithEmptyThemes()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The theme must not be empty.');

        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->setBlockTheme('root', []);
    }

    public function testSetBlockThemeWithInvalidThemeType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid "themes" argument type. Expected "string or array of strings", "integer" given.'
        );

        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->setBlockTheme('root', 123);
    }

    public function testSetFormThemeWithEmptyThemes()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The theme must not be empty.');

        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->setFormTheme([]);
    }

    public function testSetFormThemeWithInvalidThemeType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid "themes" argument type. Expected "string or array of strings", "integer" given.'
        );

        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->setFormTheme(123);
    }

    public function testSetFormTheme()
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');

        // do test
        $this->rawLayout->setFormTheme(
            ['@My/Layout/theme1.html.twig', '@My/Layout/theme2.html.twig']
        );
        $this->rawLayout->setFormTheme(
            '@My/Layout/theme3.html.twig'
        );

        $formThemes = $this->rawLayout->getFormThemes();
        $this->assertSame(
            [
                '@My/Layout/theme1.html.twig',
                '@My/Layout/theme2.html.twig',
                '@My/Layout/theme3.html.twig'
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

    public function testGetHierarchyForUnknownItem()
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The "unknown" item does not exist.');

        $this->rawLayout->getHierarchy('unknown');
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testGetHierarchyWithEmptyId(?string $id)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item id must not be empty.');

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

    public function testGetHierarchyIteratorForUnknownItem()
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The "unknown" item does not exist.');

        $this->rawLayout->getHierarchyIterator('unknown');
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testGetHierarchyIteratorWithEmptyId(?string $id)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item id must not be empty.');

        $this->rawLayout->getHierarchyIterator($id);
    }

    public function emptyStringDataProvider(): array
    {
        return [
            [null],
            ['']
        ];
    }

    public function invalidIdDataProvider(): array
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
