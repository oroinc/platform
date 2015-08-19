<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block;
use Oro\Component\Layout\BlockTypeHelperInterface;
use Oro\Component\Layout\DataAccessorInterface;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\RawLayout;

class BlockTest extends \PHPUnit_Framework_TestCase
{
    /** @var RawLayout */
    protected $rawLayout;

    /** @var BlockTypeHelperInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $typeHelper;

    /** @var LayoutContext */
    protected $context;

    /** @var DataAccessorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $data;

    /** @var Block */
    protected $block;

    protected function setUp()
    {
        $this->rawLayout  = new RawLayout();
        $this->typeHelper = $this->getMock('Oro\Component\Layout\BlockTypeHelperInterface');
        $this->context    = new LayoutContext();
        $this->data       = $this->getMock('Oro\Component\Layout\DataAccessorInterface');
        $this->block      = new Block(
            $this->rawLayout,
            $this->typeHelper,
            $this->context,
            $this->data
        );
    }

    public function testGetTypeHelper()
    {
        $this->assertSame($this->typeHelper, $this->block->getTypeHelper());
    }

    public function testGetContext()
    {
        $this->assertSame($this->context, $this->block->getContext());
    }

    public function testGetData()
    {
        $this->assertSame($this->data, $this->block->getData());
    }

    public function testInitialize()
    {
        $id = 'test_id';

        $this->block->initialize($id);

        $this->assertEquals($id, $this->block->getId());
    }

    public function testGetTypeName()
    {
        $id   = 'test_id';
        $name = 'test_name';

        $this->rawLayout->add($id, null, $name);

        $this->block->initialize($id);

        $this->assertEquals($name, $this->block->getTypeName());
    }

    public function testGetTypeNameWhenBlockTypeIsAddedAsObject()
    {
        $id   = 'test_id';
        $name = 'test_name';

        $type = $this->getMock('Oro\Component\Layout\BlockTypeInterface');
        $type->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($name));

        $this->rawLayout->add($id, null, $type);

        $this->block->initialize($id);

        $this->assertEquals($name, $this->block->getTypeName());
    }

    public function testGetAliases()
    {
        $id = 'test_id';

        $this->rawLayout->add($id, null, 'test_name');
        $this->rawLayout->addAlias('alias1', $id);
        $this->rawLayout->addAlias('alias2', 'alias1');

        $this->block->initialize($id);

        $this->assertEquals(['alias1', 'alias2'], $this->block->getAliases());
    }

    public function testGetParent()
    {
        $this->rawLayout->add('root', null, 'root');
        $this->rawLayout->add('header', 'root', 'header');
        $this->rawLayout->add('logo', 'header', 'logo');

        $this->block->initialize('logo');
        $this->assertNotNull($this->block->getParent());
        $this->assertEquals('header', $this->block->getParent()->getId());
        $this->assertNotNull($this->block->getParent()->getParent());
        $this->assertEquals('root', $this->block->getParent()->getParent()->getId());
        $this->assertNull($this->block->getParent()->getParent()->getParent());

        $this->block->initialize('header');
        $this->assertNotNull($this->block->getParent());
        $this->assertEquals('root', $this->block->getParent()->getId());
        $this->assertNull($this->block->getParent()->getParent());
    }

    public function testGetOptions()
    {
        $this->rawLayout->add('root', null, 'root', ['root_option1' => 'val1']);
        $this->rawLayout->setProperty(
            'root',
            RawLayout::RESOLVED_OPTIONS,
            ['root_option1' => 'val1', 'id' => 'root']
        );
        $this->rawLayout->add('header', 'root', 'header', ['header_option1' => 'val1']);
        $this->rawLayout->setProperty(
            'header',
            RawLayout::RESOLVED_OPTIONS,
            ['header_option1' => 'val1', 'id' => 'header']
        );

        $this->block->initialize('header');

        $this->assertEquals(
            ['header_option1' => 'val1', 'id' => 'header'],
            $this->block->getOptions()
        );
        $this->assertEquals(
            ['root_option1' => 'val1', 'id' => 'root'],
            $this->block->getParent()->getOptions()
        );
    }
}
