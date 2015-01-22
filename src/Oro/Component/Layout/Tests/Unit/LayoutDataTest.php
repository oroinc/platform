<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\LayoutData;

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
     * @dataProvider emptyIdDataProvider
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage The item id must not be empty.
     */
    public function testAddItemWithEmptyId($id)
    {
        $this->layoutData->addItem($id, null, 'root');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
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

    public function emptyIdDataProvider()
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
}
