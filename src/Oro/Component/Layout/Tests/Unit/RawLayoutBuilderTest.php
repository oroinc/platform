<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\RawLayout;
use Oro\Component\Layout\RawLayoutBuilder;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type\RootType;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class RawLayoutBuilderTest extends LayoutTestCase
{
    /** @var RawLayoutBuilder */
    protected $rawLayoutBuilder;

    protected function setUp()
    {
        $this->rawLayoutBuilder = new RawLayoutBuilder();
    }

    public function testClear()
    {
        $this->rawLayoutBuilder
            ->add('root', null, 'root');

        $this->assertSame($this->rawLayoutBuilder, $this->rawLayoutBuilder->clear());
        $this->assertTrue($this->rawLayoutBuilder->isEmpty());
    }

    public function testIsEmpty()
    {
        $this->assertTrue($this->rawLayoutBuilder->isEmpty());

        $this->rawLayoutBuilder
            ->add('root', null, 'root');

        $this->assertFalse($this->rawLayoutBuilder->isEmpty());
    }

    public function testResolveId()
    {
        $this->rawLayoutBuilder
            ->add('root', null, 'root')
            ->addAlias('root_alias1', 'root')
            ->addAlias('root_alias2', 'root_alias1');

        $this->assertEquals('root', $this->rawLayoutBuilder->resolveId('root'));
        $this->assertEquals('root', $this->rawLayoutBuilder->resolveId('root_alias1'));
        $this->assertEquals('root', $this->rawLayoutBuilder->resolveId('root_alias2'));
    }

    public function testGetParentId()
    {
        $this->rawLayoutBuilder
            ->add('root', null, 'root')
            ->add('header', 'root', 'header');

        $this->assertNull($this->rawLayoutBuilder->getParentId('root'));
        $this->assertEquals('root', $this->rawLayoutBuilder->getParentId('header'));
    }

    /**
     * @dataProvider isParentForDataProvider
     */
    public function testIsParentFor($expected, $parentId, $id)
    {
        $this->rawLayoutBuilder
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo')
            ->addAlias('root_alias', 'root')
            ->addAlias('header_alias', 'header')
            ->addAlias('logo_alias', 'logo');

        $this->assertEquals($expected, $this->rawLayoutBuilder->isParentFor($parentId, $id));
    }

    public function isParentForDataProvider()
    {
        return [
            [true, 'header', 'logo'],
            [true, 'header_alias', 'logo_alias'],
            [false, 'root', 'logo'],
            [false, 'unknown', 'logo'],
            [false, 'header', 'unknown'],
            [false, 'unknown', 'unknown']
        ];
    }

    public function testHasAlias()
    {
        $this->rawLayoutBuilder
            ->add('root', null, 'root')
            ->addAlias('root_alias1', 'root')
            ->addAlias('root_alias2', 'root_alias1');

        $this->assertFalse($this->rawLayoutBuilder->hasAlias('root'));
        $this->assertTrue($this->rawLayoutBuilder->hasAlias('root_alias1'));
        $this->assertTrue($this->rawLayoutBuilder->hasAlias('root_alias2'));
    }

    public function testGetAliases()
    {
        $this->rawLayoutBuilder
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->addAlias('root_alias1', 'root')
            ->addAlias('root_alias2', 'root_alias1');

        $this->assertEquals(['root_alias1', 'root_alias2'], $this->rawLayoutBuilder->getAliases('root'));
        $this->assertEquals([], $this->rawLayoutBuilder->getAliases('root_alias1'));
        $this->assertEquals([], $this->rawLayoutBuilder->getAliases('root_alias2'));
        $this->assertEquals([], $this->rawLayoutBuilder->getAliases('header'));
        $this->assertEquals([], $this->rawLayoutBuilder->getAliases('unknown'));
    }

    public function testGetType()
    {
        $this->rawLayoutBuilder->add('root', null, 'root');

        $this->assertEquals('root', $this->rawLayoutBuilder->getType('root'));
    }

    public function testGetTypeWithBlockTypeAsAlreadyCreatedBlockTypeObject()
    {
        $this->rawLayoutBuilder->add('root', null, new RootType());

        $this->assertEquals('root', $this->rawLayoutBuilder->getType('root'));
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot get block type for "unknown" item. Reason: The "unknown" item does not exist.
     */
    public function testGetTypeWithException()
    {
        $this->rawLayoutBuilder->getType('unknown');
    }

    public function testAdd()
    {
        $this->rawLayoutBuilder
            ->add('root', null, 'root')
            ->add('header', 'root', 'header')
            ->add('logo', 'header', 'logo', ['title' => 'test']);

        $this->assertTrue($this->rawLayoutBuilder->has('root'));
        $this->assertTrue($this->rawLayoutBuilder->has('header'));
        $this->assertTrue($this->rawLayoutBuilder->has('logo'));
    }

    public function testAddWithBlockTypeAsAlreadyCreatedBlockTypeObject()
    {
        $type = $this->createMock('Oro\Component\Layout\BlockTypeInterface');
        $this->rawLayoutBuilder->add('root', null, $type);
        $this->assertSame(
            $type,
            $this->rawLayoutBuilder->getRawLayout()->getProperty('root', RawLayout::BLOCK_TYPE)
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot add "root" item to the layout. ParentId: . BlockType: . SiblingId: . Reason: The block type name must not be empty.
     */
    // @codingStandardsIgnoreEnd
    public function testAddWithEmptyBlockType($blockType)
    {
        $this->rawLayoutBuilder->add('root', null, $blockType);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot add "root" item to the layout. ParentId: . BlockType: 123. SiblingId: . Reason: Invalid "blockType" argument type. Expected "string or BlockTypeInterface", "integer" given.
     */
    // @codingStandardsIgnoreEnd
    public function testAddWithInvalidBlockType()
    {
        $this->rawLayoutBuilder->add('root', null, 123);
    }

    /**
     * @dataProvider invalidBlockTypeNameDataProvider
     */
    public function testAddWithInvalidBlockTypeName($blockType)
    {
        $this->expectException('\Oro\Component\Layout\Exception\LogicException');
        $this->expectExceptionMessage(
            sprintf(
                'Cannot add "root" item to the layout. ParentId: . BlockType: %1$s. SiblingId: . Reason: '
                . 'The "%1$s" string cannot be used as the name of the block type '
                . 'because it contains illegal characters. '
                . 'The valid block type name should start with a letter and only contain '
                . 'letters, numbers and underscores ("_").',
                $blockType
            )
        );
        $this->rawLayoutBuilder->add('root', null, $blockType);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot add "test" item to the layout. ParentId: root. BlockType: root. SiblingId: . Reason: The "root" item does not exist.
     */
    // @codingStandardsIgnoreEnd
    public function testAddToUnknownParent()
    {
        $this->rawLayoutBuilder
            ->add('test', 'root', 'root');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot remove "root" item from the layout. Reason: The "root" item does not exist.
     */
    public function testRemoveUnknownItem()
    {
        $this->rawLayoutBuilder
            ->remove('root');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot move "root" item. ParentId: destination. SiblingId: . Reason: The "root" item does not exist.
     */
    // @codingStandardsIgnoreEnd
    public function testMoveUnknownItem()
    {
        $this->rawLayoutBuilder
            ->move('root', 'destination');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot add "test" alias for "root" item. Reason: The "root" item does not exist.
     */
    public function testAddAliasForUnknownItem()
    {
        $this->rawLayoutBuilder
            ->addAlias('test', 'root');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot remove "test" alias. Reason: The "test" item alias does not exist.
     */
    public function testRemoveUnknownAlias()
    {
        $this->rawLayoutBuilder
            ->removeAlias('test');
    }

    // @codingStandardsIgnoreStart
    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot set a value for "" option for "root" item. Reason: The option name must not be empty.
     */
    // @codingStandardsIgnoreEnd
    public function testSetOptionWithEmptyName($name)
    {
        $this->rawLayoutBuilder
            ->setOption('root', $name, 123);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot set a value for "test" option for "root" item. Reason: Cannot change already resolved options.
     */
    // @codingStandardsIgnoreEnd
    public function testSetOptionForAlreadyResolvedItem()
    {
        $this->rawLayoutBuilder
            ->add('root', null, 'root');
        $this->rawLayoutBuilder->getRawLayout()->setProperty('root', RawLayout::RESOLVED_OPTIONS, []);

        $this->rawLayoutBuilder
            ->setOption('root', 'test', 123);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot set a value for "test" option for "root" item. Reason: The "root" item does not exist.
     */
    // @codingStandardsIgnoreEnd
    public function testSetOptionForUnknownItem()
    {
        $this->rawLayoutBuilder
            ->setOption('root', 'test', 123);
    }

    // @codingStandardsIgnoreStart
    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot append a value for "" option for "root" item. Reason: The option name must not be empty.
     */
    // @codingStandardsIgnoreEnd
    public function testAppendOptionWithEmptyName($name)
    {
        $this->rawLayoutBuilder
            ->appendOption('root', $name, 123);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot append a value for "test" option for "root" item. Reason: Cannot change already resolved options.
     */
    // @codingStandardsIgnoreEnd
    public function testAppendOptionForAlreadyResolvedItem()
    {
        $this->rawLayoutBuilder
            ->add('root', null, 'root');
        $this->rawLayoutBuilder->getRawLayout()->setProperty('root', RawLayout::RESOLVED_OPTIONS, []);

        $this->rawLayoutBuilder
            ->appendOption('root', 'test', 123);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot append a value for "test" option for "root" item. Reason: The "root" item does not exist.
     */
    // @codingStandardsIgnoreEnd
    public function testAppendOptionForUnknownItem()
    {
        $this->rawLayoutBuilder
            ->appendOption('root', 'test', 123);
    }

    // @codingStandardsIgnoreStart
    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot subtract a value for "" option for "root" item. Reason: The option name must not be empty.
     */
    // @codingStandardsIgnoreEnd
    public function testSubtractOptionWithEmptyName($name)
    {
        $this->rawLayoutBuilder
            ->subtractOption('root', $name, 123);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot subtract a value for "test" option for "root" item. Reason: Cannot change already resolved options.
     */
    // @codingStandardsIgnoreEnd
    public function testSubtractOptionForAlreadyResolvedItem()
    {
        $this->rawLayoutBuilder
            ->add('root', null, 'root');
        $this->rawLayoutBuilder->getRawLayout()->setProperty('root', RawLayout::RESOLVED_OPTIONS, []);

        $this->rawLayoutBuilder
            ->subtractOption('root', 'test', 123);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot subtract a value for "test" option for "root" item. Reason: The "root" item does not exist.
     */
    // @codingStandardsIgnoreEnd
    public function testSubtractOptionForUnknownItem()
    {
        $this->rawLayoutBuilder
            ->subtractOption('root', 'test', 123);
    }

    // @codingStandardsIgnoreStart
    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot replace a value for "" option for "root" item. Reason: The option name must not be empty.
     */
    // @codingStandardsIgnoreEnd
    public function testReplaceOptionWithEmptyName($name)
    {
        $this->rawLayoutBuilder
            ->replaceOption('root', $name, 123, 456);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot replace a value for "test" option for "root" item. Reason: Cannot change already resolved options.
     */
    // @codingStandardsIgnoreEnd
    public function testReplaceOptionForAlreadyResolvedItem()
    {
        $this->rawLayoutBuilder
            ->add('root', null, 'root');
        $this->rawLayoutBuilder->getRawLayout()->setProperty('root', RawLayout::RESOLVED_OPTIONS, []);

        $this->rawLayoutBuilder
            ->replaceOption('root', 'test', 123, 456);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot replace a value for "test" option for "root" item. Reason: The "root" item does not exist.
     */
    // @codingStandardsIgnoreEnd
    public function testReplaceOptionForUnknownItem()
    {
        $this->rawLayoutBuilder
            ->replaceOption('root', 'test', 123, 456);
    }

    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot remove "" option for "root" item. Reason: The option name must not be empty.
     */
    public function testRemoveOptionWithEmptyName($name)
    {
        $this->rawLayoutBuilder
            ->removeOption('root', $name);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot remove "test" option for "root" item. Reason: Cannot change already resolved options.
     */
    // @codingStandardsIgnoreEnd
    public function testRemoveOptionForAlreadyResolvedItem()
    {
        $this->rawLayoutBuilder
            ->add('root', null, 'root');
        $this->rawLayoutBuilder->getRawLayout()->setProperty('root', RawLayout::RESOLVED_OPTIONS, []);

        $this->rawLayoutBuilder
            ->removeOption('root', 'test');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot remove "test" option for "root" item. Reason: The "root" item does not exist.
     */
    public function testRemoveOptionForUnknownItem()
    {
        $this->rawLayoutBuilder
            ->removeOption('root', 'test');
    }

    public function testChangeBlockType()
    {
        $this->rawLayoutBuilder->add('root', null, 'root');

        $this->rawLayoutBuilder->changeBlockType('root', 'my_root');
        $this->assertEquals(
            'my_root',
            $this->rawLayoutBuilder->getRawLayout()->getProperty('root', RawLayout::BLOCK_TYPE)
        );
    }

    public function testChangeBlockTypeAndOptions()
    {
        $this->rawLayoutBuilder->add('root', null, 'root', ['foo' => 'bar']);

        $this->rawLayoutBuilder->changeBlockType(
            'root',
            'my_root',
            function (array $options) {
                $options['new_option'] = 'val';

                return $options;
            }
        );
        $this->assertEquals(
            'my_root',
            $this->rawLayoutBuilder->getRawLayout()->getProperty('root', RawLayout::BLOCK_TYPE)
        );
        $this->assertEquals(
            ['foo' => 'bar', 'new_option' => 'val'],
            $this->rawLayoutBuilder->getRawLayout()->getProperty('root', RawLayout::OPTIONS)
        );
    }

    public function testChangeBlockTypeWithAlreadyCreatedBlockTypeObject()
    {
        $type = $this->createMock('Oro\Component\Layout\BlockTypeInterface');
        $this->rawLayoutBuilder->add('root', null, $type);
        $this->assertSame(
            $type,
            $this->rawLayoutBuilder->getRawLayout()->getProperty('root', RawLayout::BLOCK_TYPE)
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot change block type to "my_root" for "root" item. Reason: Cannot change the block type if options are already resolved.
     */
    // @codingStandardsIgnoreEnd
    public function testChangeBlockTypeForAlreadyResolvedItem()
    {
        $this->rawLayoutBuilder
            ->add('root', null, 'root');
        $this->rawLayoutBuilder->getRawLayout()->setProperty('root', RawLayout::RESOLVED_OPTIONS, []);

        $this->rawLayoutBuilder
            ->changeBlockType('root', 'my_root');
    }

    // @codingStandardsIgnoreStart
    /**
     * @dataProvider             emptyStringDataProvider
     *
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot change block type to "" for "root" item. Reason: The block type name must not be empty.
     */
    // @codingStandardsIgnoreEnd
    public function testChangeBlockTypeWithEmptyBlockType($blockType)
    {
        $this->rawLayoutBuilder->add('root', null, 'root');
        $this->rawLayoutBuilder->changeBlockType('root', $blockType);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot change block type to "123" for "root" item. Reason: Invalid "blockType" argument type. Expected "string or BlockTypeInterface", "integer" given.
     */
    // @codingStandardsIgnoreEnd
    public function testChangeBlockTypeWithInvalidBlockType()
    {
        $this->rawLayoutBuilder->add('root', null, 'root');
        $this->rawLayoutBuilder->changeBlockType('root', 123);
    }

    /**
     * @dataProvider invalidBlockTypeNameDataProvider
     */
    public function testChangeBlockTypeWithInvalidBlockTypeName($blockType)
    {
        $this->expectException('\Oro\Component\Layout\Exception\LogicException');
        $this->expectExceptionMessage(
            sprintf(
                'Cannot change block type to "%1$s" for "root" item. Reason: '
                . 'The "%1$s" string cannot be used as the name of the block type '
                . 'because it contains illegal characters. '
                . 'The valid block type name should start with a letter and only contain '
                . 'letters, numbers and underscores ("_").',
                $blockType
            )
        );
        $this->rawLayoutBuilder->add('root', null, 'root');
        $this->rawLayoutBuilder->changeBlockType('root', $blockType);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot change block type to "my_root" for "root" item. Reason: Invalid "optionsCallback" argument type. Expected "callable", "integer" given.
     */
    // @codingStandardsIgnoreEnd
    public function testChangeBlockTypeWithInvalidOptionCallback()
    {
        $this->rawLayoutBuilder->add('root', null, 'root');
        $this->rawLayoutBuilder->changeBlockType('root', 'my_root', 123);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot set theme(s) for "root" item. Reason: The "root" item does not exist.
     */
    public function testSetBlockThemeForUnknownItem()
    {
        $this->rawLayoutBuilder
            ->setBlockTheme('MyBundle:Layout:my_theme.html.twig', 'root');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot set theme(s) for "" item. Reason: The root item does not exist.
     */
    public function testSetRootBlockThemeForUnknownItem()
    {
        $this->rawLayoutBuilder
            ->setBlockTheme('MyBundle:Layout:my_theme.html.twig');
    }

    public function testGetOptions()
    {
        $this->rawLayoutBuilder
            ->add('root', null, 'root')
            ->add('logo', 'root', 'logo', ['title' => 'test']);

        $this->assertSame(
            [],
            $this->rawLayoutBuilder->getOptions('root')
        );
        $this->assertSame(
            ['title' => 'test'],
            $this->rawLayoutBuilder->getOptions('logo')
        );
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot get options for "root" item. Reason: The "root" item does not exist.
     */
    public function testGetOptionsForUnknownItem()
    {
        $this->rawLayoutBuilder
            ->getOptions('root');
    }

    public function emptyStringDataProvider()
    {
        return [
            [null],
            ['']
        ];
    }

    public function invalidBlockTypeNameDataProvider()
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
            ['test:block']
        ];
    }
}
