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
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RawLayoutTest extends TestCase
{
    private RawLayout $rawLayout;

    #[\Override]
    protected function setUp(): void
    {
        $this->rawLayout = new RawLayout();
    }

    public function testIsEmpty(): void
    {
        $this->assertTrue($this->rawLayout->isEmpty());

        $this->rawLayout->add('root', null, 'root');
        $this->assertFalse($this->rawLayout->isEmpty());
    }

    public function testClear(): void
    {
        $this->rawLayout->add('root', null, 'root');

        $this->rawLayout->clear();
        $this->assertTrue($this->rawLayout->isEmpty());
    }

    public function testGetRootId(): void
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');

        // do test
        $this->assertEquals('root', $this->rawLayout->getRootId());
    }

    public function testGetParentId(): void
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');

        // do test
        $this->assertEquals('root', $this->rawLayout->getParentId('header'));
    }

    public function testGetParentIdByAlias(): void
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');
        $this->rawLayout->addAlias('header_alias', 'header');

        // do test
        $this->assertEquals('root', $this->rawLayout->getParentId('header_alias'));
    }

    public function testGetParentIdForRootItem(): void
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');

        // do test
        $this->assertEquals(null, $this->rawLayout->getParentId('root'));
    }

    public function testGetParentIdForUnknownItem(): void
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
    public function testGetParentIdWithEmptyId(?string $id): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item id must not be empty.');

        $this->rawLayout->getParentId($id);
    }

    public function testResolveId(): void
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

    public function testHas(): void
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
    public function testAddWithEmptyId(?string $id): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item id must not be empty.');

        $this->rawLayout->add($id, null, 'root');
    }

    public function testAddWithNotStringId(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Invalid "id" argument type. Expected "string", "integer" given.');

        $this->rawLayout->add(123, null, 'root');
    }

    /**
     * @dataProvider invalidIdDataProvider
     */
    public function testAddWithInvalidId(string $id): void
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

    public function testAddDuplicate(): void
    {
        $this->expectException(ItemAlreadyExistsException::class);
        $this->expectExceptionMessage(
            'The "root" item already exists. Remove existing item before add the new item with the same id.'
        );

        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('root', null, 'root');
    }

    public function testRedefineRoot(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'The "another_root" item cannot be the root item because another root item ("root") already exists.'
        );

        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('another_root', null, 'root');
    }

    public function testAddToUnknownSibling(): void
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

    public function testAddWhenParentEqualsToSibling(): void
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

    public function testAddToEnd(): void
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

    public function testAddToBegin(): void
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

    public function testAddAfterSibling(): void
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

    public function testAddBeforeSibling(): void
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

    public function testRemove(): void
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

    public function testRemoveByAlias(): void
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');
        $this->rawLayout->addAlias('test_header', 'header');

        // do test
        $this->rawLayout->remove('test_header');
        $this->assertFalse($this->rawLayout->has('header'));
    }

    public function testRemoveUnknown(): void
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The "unknown" item does not exist.');

        $this->rawLayout->remove('unknown');
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testRemoveWithEmptyId(?string $id): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item id must not be empty.');

        $this->rawLayout->remove($id);
    }

    public function testMoveToParent(): void
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

    public function testMoveToParentByAlias(): void
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

    public function testMoveUnknown(): void
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The "unknown" item does not exist.');

        $this->rawLayout->move('unknown');
    }

    public function testMoveToUnknownParent(): void
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

    public function testMoveToUnknownSibling(): void
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

    public function testMoveWhenParentEqualsToMovingItem(): void
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

    public function testMoveWhenSiblingEqualsToMovingItem(): void
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

    public function testMoveWhenParentEqualsToSibling(): void
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

    public function testMoveWithoutParentAndSibling(): void
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

    public function testMoveParentToChild(): void
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

    public function testMoveInsideTheSameParent(): void
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

    public function testMoveInsideTheSameParentAndWithParentIdSpecified(): void
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
    public function testMoveWithEmptyId(?string $id): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item id must not be empty.');

        $this->rawLayout->move($id);
    }

    public function testHasProperty(): void
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');

        // do test
        $this->assertTrue($this->rawLayout->hasProperty('header', RawLayout::PATH));
        $this->assertFalse($this->rawLayout->hasProperty('header', 'unknown'));
    }

    public function testHasPropertyForUnknownItem(): void
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The "unknown" item does not exist.');

        $this->rawLayout->hasProperty('unknown', RawLayout::PATH);
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testHasPropertyWithEmptyId(?string $id): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item id must not be empty.');

        $this->rawLayout->hasProperty($id, RawLayout::PATH);
    }

    public function testGetProperty(): void
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');

        // do test
        $this->assertEquals(['root', 'header'], $this->rawLayout->getProperty('header', RawLayout::PATH));
    }

    public function testGetPropertyForUnknownProperty(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The "header" item does not have "unknown" property.');

        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');

        // do test
        $this->rawLayout->getProperty('header', 'unknown');
    }

    public function testGetPropertyForUnknownItem(): void
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The "unknown" item does not exist.');

        $this->rawLayout->getProperty('unknown', RawLayout::PATH);
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testGetPropertyWithEmptyId(?string $id): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item id must not be empty.');

        $this->rawLayout->getProperty($id, RawLayout::PATH);
    }

    public function testSetProperty(): void
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');

        // do test
        $this->rawLayout->setProperty('header', 'some_property', 123);
        $this->assertEquals(123, $this->rawLayout->getProperty('header', 'some_property'));
    }

    public function testSetPropertyForUnknownItem(): void
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The "unknown" item does not exist.');

        $this->rawLayout->setProperty('unknown', 'some_property', 123);
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testSetPropertyWithEmptyId(?string $id): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item id must not be empty.');

        $this->rawLayout->setProperty($id, 'some_property', 123);
    }

    public function testHasAlias(): void
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

    public function testAddAlias(): void
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');
        $this->rawLayout->addAlias('test_header', 'header');

        // do test
        $this->assertTrue($this->rawLayout->hasAlias('test_header'));
        $this->assertEquals('header', $this->rawLayout->resolveId('test_header'));
    }

    public function testAddAliasWhenAliasIsAddedForAnotherAlias(): void
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
    public function testAddAliasWithEmptyAlias(?string $alias): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item alias must not be empty.');

        $this->rawLayout->addAlias($alias, 'root');
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testAddAliasWithEmptyId(?string $id): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item id must not be empty.');

        $this->rawLayout->addAlias('test', $id);
    }

    public function testAddAliasWithNotStringAlias(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Invalid "alias" argument type. Expected "string", "integer" given.');

        $this->rawLayout->addAlias(123, 'root');
    }

    public function testAddAliasWithNotStringId(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Invalid "id" argument type. Expected "string", "integer" given.');

        $this->rawLayout->addAlias('test', 123);
    }

    /**
     * @dataProvider invalidIdDataProvider
     */
    public function testAddAliasWithInvalidAlias(string $alias): void
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
    public function testAddAliasWithInvalidId(string $id): void
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

    public function testAddAliasDuplicate(): void
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->addAlias('test', 'root');

        // do test
        $this->rawLayout->addAlias('test', 'root');
        $this->assertTrue($this->rawLayout->hasAlias('test'));
        $this->assertEquals('root', $this->rawLayout->resolveId('test'));
    }

    public function testAddAliasRedefine(): void
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

    public function testAddAliasWhenAliasEqualsId(): void
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

    public function testAddAliasWhenAliasEqualsIdOfAnotherItem(): void
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

    public function testAddAliasForUnknownItem(): void
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The "root" item does not exist.');

        $this->rawLayout->addAlias('header', 'root');
    }

    public function testRemoveAlias(): void
    {
        // prepare test data
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');
        $this->rawLayout->addAlias('test_header', 'header');

        // do test
        $this->rawLayout->removeAlias('test_header');
        $this->assertFalse($this->rawLayout->hasAlias('test_header'));
    }

    public function testRemoveUnknownAlias(): void
    {
        $this->expectException(AliasNotFoundException::class);
        $this->expectExceptionMessage('The "unknown" item alias does not exist.');

        $this->rawLayout->removeAlias('unknown');
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testRemoveAliasWithEmptyAlias(?string $alias): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item alias must not be empty.');

        $this->rawLayout->removeAlias($alias);
    }

    public function testGetAliases(): void
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

    public function testSetBlockTheme(): void
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

    public function testSetBlockThemeForUnknownItem(): void
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The "unknown" item does not exist.');

        $this->rawLayout->setBlockTheme('unknown', '@My/Layout/theme1.html.twig');
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testSetBlockThemeWithEmptyId(?string $id): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item id must not be empty.');

        $this->rawLayout->setBlockTheme($id, '@My/Layout/theme1.html.twig');
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testSetBlockThemeWithEmptyTheme(?string $theme): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The theme must not be empty.');

        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->setBlockTheme('root', $theme);
    }

    public function testSetBlockThemeWithEmptyThemes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The theme must not be empty.');

        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->setBlockTheme('root', []);
    }

    public function testSetBlockThemeWithInvalidThemeType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid "themes" argument type. Expected "string or array of strings", "integer" given.'
        );

        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->setBlockTheme('root', 123);
    }

    public function testSetFormThemeWithEmptyThemes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The theme must not be empty.');

        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->setFormTheme([]);
    }

    public function testSetFormThemeWithInvalidThemeType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid "themes" argument type. Expected "string or array of strings", "integer" given.'
        );

        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->setFormTheme(123);
    }

    public function testSetFormTheme(): void
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

    public function testGetHierarchy(): void
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

    public function testGetHierarchyForUnknownItem(): void
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The "unknown" item does not exist.');

        $this->rawLayout->getHierarchy('unknown');
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testGetHierarchyWithEmptyId(?string $id): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The item id must not be empty.');

        $this->rawLayout->getHierarchy($id);
    }

    public function testGetHierarchyIterator(): void
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

    public function testGetHierarchyIteratorForUnknownItem(): void
    {
        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessage('The "unknown" item does not exist.');

        $this->rawLayout->getHierarchyIterator('unknown');
    }

    /**
     * @dataProvider emptyStringDataProvider
     */
    public function testGetHierarchyIteratorWithEmptyId(?string $id): void
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
