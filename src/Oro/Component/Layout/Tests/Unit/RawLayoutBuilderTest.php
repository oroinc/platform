<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\RawLayout;
use Oro\Component\Layout\RawLayoutBuilder;

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

        $this->rawLayoutBuilder->clear();
        $this->assertTrue($this->rawLayoutBuilder->isEmpty());
    }

    public function testIsEmpty()
    {
        $this->assertTrue($this->rawLayoutBuilder->isEmpty());

        $this->rawLayoutBuilder
            ->add('root', null, 'root');

        $this->assertFalse($this->rawLayoutBuilder->isEmpty());
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
}
